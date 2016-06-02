<?php
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );

@ini_set( 'auto_detect_line_endings', true );

/**
 * Replaces `WPBDP_CSVImporter` (from 2.1) and adds support for sequential imports.
 * @since 3.5.8
 */
class WPBDP_CSV_Import {

    private static $PERSISTENT = array( 'settings', 'header', 'total_lines', 'processed_lines', 'current_line', 'imported', 'rejected', 'errors', 'done' );

    private $state_id = '';
    private $working_dir = '';

    private $state_file = '';
    private $csv_file = '';
    private $images_dir = '';

    private $settings = array();

    private $header = array();
    private $fields = array();

    private $total_lines = 0;
    private $processed_lines = 0;
    private $current_line = 0;

    private $imported = 0;
    private $rejected = 0;
    private $errors = array();
    private $done = false;


    public function __construct( $state_id = '', $csv_file = '', $images_file = '', $settings = array() ) {
        $defaults = array(
            'allow-partial-imports' => true,
            'csv-file-separator' => ',',
            'images-separator' => ';',
            'category-separator' => ';',
            'create-missing-categories' => true,

            'assign-listings-to-user' => true,
            'default-user' => '0',
            'post-status' => 'publish',
            'disable-email-notifications' => true,
            'append-images' => true,

            'test-import' => false,

            'batch-size' => 40
        );

        if ( $state_id ) {
            $this->restore_state( $state_id );
        } else {
            if ( ! is_readable( $csv_file ) )
                throw new Exception('Invalid CSV file.');

            $this->setup_working_dir( $csv_file, $images_file );

            if ( ! array_key_exists( 'assign-listings-to-user', $settings ) )
                $settings['assign-listings-to-user'] = false;

            if ( ! array_key_exists( 'disable-email-notifications', $settings ) )
                $settings['disable-email-notifications'] = false;

            if ( ! array_key_exists( 'append-images', $settings ) )
                $settings['append-images'] = false;

            $this->settings = wp_parse_args( $settings, $defaults );

            $file = new SplFileObject( $this->csv_file );
            $file->seek( PHP_INT_MAX );
            $this->total_lines = absint( $file->key() );
            $file = null;
        }

        if ( ! $this->header )
            $this->read_header();
    }

    public function do_work() {
        if ( $this->done )
            return;

        $file = new SplFileObject( $this->csv_file );
        $file->seek( $this->current_line );

        $n = 0;
        while ( $n < $this->settings['batch-size'] ) {
            if ( $file->eof() ) {
                $this->done = true;
                break;
            }

            $line = $this->get_current_line( $file );

            // We can't use fgetcsv() directly due to https://bugs.php.net/bug.php?id=46569.
            $line_data = str_getcsv( $line, $this->settings['csv-file-separator'] );

            $file->next();
            $n++;
            $this->current_line = $file->key();
            $this->processed_lines++;

            if ( ! $line_data || ( count( $line_data ) == 1 && empty( $line_data[0] ) ) )
                continue;

            list( $listing_data, $errors ) = $this->sanitize_and_validate_row( $line_data );

            if ( $errors ) {
                foreach ( $errors as $e )
                    $this->errors[] = array( 'line' => $this->current_line, 'content' => $line, 'error' => $e );

                $this->rejected++;
                continue;
            }

            $result = $this->import_row( $listing_data );
            @set_time_limit( 2 );

            if ( is_wp_error( $result ) ) {
                foreach ( $result->get_error_messages() as $e )
                    $this->errors[] = array( 'line' => $this->current_line, 'content' => $line, 'error' => $e );

                $this->rejected++;
                continue;
            }

            $this->imported++;
        }

        $file = null;
        $this->state_persist();
    }

    private function get_current_line( $file ) {
        $line = $file->current();

        if ( empty( $line ) ) {
            return '';
        }

        $encoding = wpbdp_detect_encoding( $line );

        if ( 'UTF-8' != $encoding ) {
            $converted_line = iconv( $encoding, 'UTF-8', $line );
        } else {
            $converted_line = $line;
        }

        // Some code to circumvent limitations in str_getcsv() while PHP #46569 is fixed.
        return str_replace( '\n', "\n", $converted_line );
    }

    public function get_import_id() {
        return $this->state_id;
    }

    public function get_import_rows_count() {
        return max( 0, $this->total_lines - 1 );
    }

    public function get_imported_rows_count() {
        return $this->imported;
    }

    public function get_rejected_rows_count() {
        return $this->rejected;
    }

    public function get_setting( $k ) {
        if ( isset( $this->settings[ $k ] ) )
            return $this->settings[ $k ];

        return null;
    }

    public function get_settings() {
        return $this->settings;
    }

    public function get_errors() {
        return $this->errors;
    }

    public function get_progress( $format = 'n' ) {
        $total = $this->get_import_rows_count();
        $done = min( $total, $this->processed_lines );

        switch ( $format ) {
            case '%': // As a percentage.
                return round( 100 * $this->get_progress( 'f' ) );
                break;
            case 'f': // As a fraction.
                return round( $done / $total, 3 );
                break;
            case 'n': // As # of items read.
                return $done;
                break;
            case 'r': // As # of items remaining.
                return max( 0, $total - $done );
                break;
        }
    }

    public function in_test_mode() {
        return (bool) $this->settings['test-import'];
    }

    public function done() {
        return $this->done;
    }

    public function cleanup() {
        wpbdp_rrmdir( $this->working_dir );
    }

    private function restore_state( $state_id ) {
        $upload_dir = wp_upload_dir();

        if ( $upload_dir['error'] )
            throw new Exception();

        $csv_imports_dir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-imports' . DIRECTORY_SEPARATOR . $state_id;

        // TODO: validate $state_id is really an uniqid() string and does not contain other chars (maybe someone is 
        // trying to access parts that it shouldn't in the FS).
        if ( ! is_dir( $csv_imports_dir ) )
            throw new Exception( 'Invalid state ID' );

        $this->working_dir = $csv_imports_dir;
        $this->state_id = basename( $this->working_dir );
        $this->csv_file = $this->working_dir . DIRECTORY_SEPARATOR . 'data.csv';
        $this->images_dir = is_dir(  $this->working_dir . DIRECTORY_SEPARATOR . 'images' ) ? $this->working_dir . DIRECTORY_SEPARATOR . 'images' : '';

        $state_file = $this->working_dir . DIRECTORY_SEPARATOR . 'import.state';
        $this->state_file = $state_file;

        $this->state_load();
    }

    private function setup_working_dir( $csv_file, $images_file = '' ) {
        $upload_dir = wp_upload_dir();

        if ( $upload_dir['error'] )
            throw new Exception();

        $csv_imports_dir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-imports';
        if ( is_dir( $csv_imports_dir ) || mkdir( $csv_imports_dir ) ) {
            $working_dir = rtrim( $csv_imports_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . uniqid();

            if ( is_dir( $working_dir ) || mkdir( $working_dir, 0777 ) ) {
                $this->working_dir = $working_dir;
            }
        }

        if ( ! $this->working_dir )
            throw new Exception( 'Could not set working dir' );

        if ( ! copy( $csv_file, $this->working_dir . DIRECTORY_SEPARATOR . 'data.csv' ) )
            throw new Exception( 'Could not copy CSV file to working directory' );

        if ( $images_file && file_exists( $images_file ) ) {
            $dest = $this->working_dir . DIRECTORY_SEPARATOR . 'images.zip';
            if ( ! copy( $images_file, $dest ) ) // XXX: maybe move?
                throw new Exception( 'Could not copy images ZIP file to working directory' );

            require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
            $zip = new PclZip( $dest );
            if ( $files = $zip->extract( PCLZIP_OPT_PATH, $this->working_dir . DIRECTORY_SEPARATOR . 'images', PCLZIP_OPT_REMOVE_ALL_PATH ) ) {
                $this->images_dir = $this->working_dir . DIRECTORY_SEPARATOR . 'images';

                @unlink( $dest );
            } else {
                throw new Exception( 'Images ZIP file could not be uncompressed' );
            }
        }

        $this->state_id = basename( $this->working_dir );
        $this->csv_file = $this->working_dir . DIRECTORY_SEPARATOR . 'data.csv';
        $this->state_file = $this->working_dir . DIRECTORY_SEPARATOR . 'import.state';

        $this->state_persist();
    }

    private function read_header() {
        $file = new SplFileObject( $this->csv_file );

        $header_line = $this->remove_bom( $file->current() );

        $this->set_header( str_getcsv( $header_line, $this->settings['csv-file-separator'] ) );
        $file->next();
        $this->current_line = $file->key();
        $file = null;

        $this->state_persist();
    }

    private function set_header( $header ) {
        if ( ! $header || ( count( $header ) == 1 && is_null( $header[0] ) ) ) {
            throw new Exception('Invalid header');
        }

        $required_fields = wpbdp_get_form_fields( 'validators=required' );
        $fields_in_header = array_map( 'trim', $header );

        foreach ( $required_fields as $rf ) {
            if ( ! in_array( $rf->get_short_name(), $fields_in_header, true ) )
                throw new Exception( sprintf( 'Required header column "%s" missing', $rf->get_short_name() ) );
        }

        $this->header = array();

        global $wpbdp;
        $short_names = $wpbdp->formfields->get_short_names();
        foreach ( $fields_in_header as $short_name ) {
            $field_id = 0;

            $key = array_search( $short_name, $short_names, true );

            if ( false === $key )
                $field_id = 0;

            if ( $f = wpbdp_get_form_field( $key) )
                $field_id = $f->get_id();

            $this->header[] = array( 'short_name' => $short_name, 'field_id' => $field_id );
        }
    }

    private function state_load() {
        if ( ! file_exists( $this->state_file ) )
            return;

        if ( ! is_readable( $this->state_file ) )
            throw new Exception('XXX');

        $state = unserialize( file_get_contents( $this->state_file ) );

        foreach ( self::$PERSISTENT as $key )
            $this->{$key} = $state[ $key ];
    }

    private function state_persist() {
        $state = array();
        $state['settings'] = $this->settings;
        $state['header'] = $this->header;
        $state['current_line'] = $this->current_line;
        $state['imported'] = $this->imported;
        $state['errors'] = $this->errors;
        $state['done'] = $this->done;

        foreach( self::$PERSISTENT as $key )
            $state[ $key ] = $this->{$key};

        if ( false === file_put_contents( $this->state_file, serialize( $state ) ) )
            throw new Exception('Could not write persistent data');
    }

    private function import_row( $data ) {
        global $wpdb;
        global $wpbdp;

        if ( $this->settings['test-import'] )
            return;

        extract( $data );

        $state = (object) array( 'fields' => array(),
                                 'images' => array(),
                                 'categories' => array() );
        $errors = array();

        // Create categories.
        foreach( $categories as &$c ) {
            if ( $c['term_id'] ) {
                $state->categories[] = intval( $c['term_id'] );
                continue;
            }

            if ( $t = term_exists( str_replace( '&', '&amp;', $c['name'] ), WPBDP_CATEGORY_TAX ) ) {
                $c['term_id'] = $t['term_id'];
            } else {
                if ( $t = wp_insert_term( str_replace( '&amp;', '&', $c['name'] ), WPBDP_CATEGORY_TAX ) ) {
                    $c['term_id'] = $t['term_id'];
                } else {
                    $errors[] = sprintf( _x( 'Could not create listing category "%s"', 'admin csv-import', 'WPBDM'), $c['name'] );
                }
            }

            if ( $c['term_id'] )
                $state->categories[] = intval( $c['term_id'] );
        }

        $listing_id = 0;

        // Support sequence_id.
        if ( $meta['sequence_id'] ) {
            $listing_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                                                                  '_wpbdp[import_sequence_id]', $meta['sequence_id'] ) ) );
            if ( WPBDP_POST_TYPE != get_post_type( $listing_id ) )
                $listing_id = 0;
        }

        // Handle fields.
        foreach ( $fields as $field_id => $field_data ) {
            $f = wpbdp_get_form_field( $field_id );

            if ( 'image' != $f->get_field_type_id() )
                continue;

            $img = trim( $field_data );

            if ( ! $img )
                continue;

            $media_id = $this->upload_image( $img );
            $fields[ $field_id ] = $media_id ? $media_id : '';
        }

        $state->fields = $fields;

        // Handle images.
        foreach ( $data['images'] as $filename ) {
            if ( $img_id = $this->upload_image( $filename ) )
                $state->images[] = $img_id;
        }

        // Insert or update listing.
        if ( $listing_id ) {
            $listing = WPBDP_Listing::get( $listing_id );
            $listing->update( $state, array( 'append-images' => $this->settings['append-images'] ) );
            $listing->set_post_status( wpbdp_get_option( 'edit-post-status' ) );
        } else {
            $listing = WPBDP_Listing::create( $state );
            $listing->set_field_values( $state->fields );
            $listing->set_images( $state->images );
            $listing->set_categories( $state->categories );
            $listing->set_post_status( $this->settings['post-status'] );
            $listing->save();
        }

        // Set username.
        if ( $u = get_user_by( 'login', $meta['username'] ) )
            wp_update_post( array( 'ID' => $listing->get_id(), 'post_author' => $u->ID ) );

        // Set featured level.
        if ( $meta['featured_level'] ) {
            if ( $l = $wpbdp->listings->upgrades->get( $meta['featured_level'] ) )
                $wpbdp->listings->upgrades->set_sticky( $listing->get_id(), $l->id );
        }

        // Create permalink.
        $post = get_post( $listing->get_id() );
        wp_update_post( array('ID' => $post->ID,
                              'post_name' => wp_unique_post_slug( sanitize_title( $post->post_title ),
                                                                  $post->ID,
                                                                  $post->post_status,
                                                                  $post->post_type,
                                                                  $post->post_parent ) ) );

        // Update expiration dates.
        foreach ( $categories as $c ) {
            if ( ! $c['expires_on'] )
                continue;

            $wpdb->update( $wpdb->prefix . 'wpbdp_listing_fees',
                           array( 'expires_on' => $c['expires_on'] ),
                           array( 'category_id' => $c['term_id'],
                                  'listing_id' => $listing->get_id() ) );
        }

        // Update sequence_id.
        if ( $meta['sequence_id'] )
            update_post_meta( $listing->get_id(), '_wpbdp[import_sequence_id]', $meta['sequence_id'] );

        if ( $errors ) {
            $error = new WP_Error();

            foreach ( $errors as $e )
                $error->add( 'listing-add-error', $e );

            return $error;
        }

        return $listing->get_id();
    }

    private function sanitize_and_validate_row( $data ) {
        global $wpbdp;

        $errors = array();

        $categories = array();
        $fields = array();
        $images = array();
        $expires_on = array();

        $meta = array();
        $meta['sequence_id'] = 0;
        $meta['username'] = '';
        $meta['featured_level'] = '';

        if ( $this->settings['assign-listings-to-user'] && $this->settings['default-user'] ) {
            if ( $u = get_user_by( 'id', $this->settings['default-user'] ) )
                $meta['username'] = $u->user_login;
        }

        foreach ( $this->header as $i => $col_info ) {
            $column = $col_info['short_name'];
            $field = $col_info['field_id'] ? wpbdp_get_form_field( $col_info['field_id'] ) : null;
            $value = stripslashes( trim( isset( $data[ $i ] ) ? $data[ $i ] : '' ) );

            switch( $column ) {
                case 'image':
                case 'images':
                    $file_names = explode( $this->settings['images-separator'], $value );

                    foreach ( $file_names as $f ) {
                        $f = trim( $f );

                        if ( $f )
                            $images[] = $f;
                    }

                    break;

                case 'username':
                    if ( $this->settings['assign-listings-to-user'] && $value ) {
                        if ( ! username_exists( $value ) ) {
                            $errors[] = sprintf( _x( 'Username "%s" does not exist', 'admin csv-import', 'WPBDM' ), $value );
                        } else {
                            $meta['username'] = $value;
                        }
                    }

                    break;

                case 'featured_level':
                    $meta['featured_level'] = $value;

                    break;

                case 'expires_on':
                    $dates = explode( '/', $value );

                    foreach ( $dates as $d )
                        $expires_on[] = $d;

                    break;

                case 'sequence_id':
                    $meta['sequence_id'] = absint( $value );

                    break;

                default:
                    if ( ! $field ) {
                        break;
                    }

                    if ( $field->is_required() && $field->is_empty_value( $value ) ) {
                        $errors[] = sprintf( _x( 'Missing required field: %s', 'admin csv-import', 'WPBDM' ), $column );
                        break;
                    }

                    if ( 'category' == $field->get_association() ) {
                        $csv_categories = array_map( 'trim', explode( $this->settings['category-separator'], $value ) );

                        foreach ( $csv_categories as $csv_category_ ) {
                            $csv_category = $csv_category_;
                            $csv_category = strip_tags( str_replace( "\n", "-", $csv_category ) );
                            $csv_category = str_replace( array( '"', "'" ), '', $csv_category );
                            $csv_category = str_replace( '&', '&amp;', $csv_category );

                            if ( ! $csv_category )
                                continue;

                            if ( $term = term_exists( $csv_category, WPBDP_CATEGORY_TAX ) ) {
                                $categories[] = array( 'name' => $csv_category, 'term_id' => $term['term_id'], 'expires_on' => '' );
                            } else {
                                if ( ! $this->settings['create-missing-categories'] ) {
                                    $errors[] = sprintf( _x( 'Listing category "%s" does not exist', 'admin csv-import', 'WPBDM' ), $csv_category );
                                    continue;
                                }

                                if ( $this->settings['test-import'] )
                                    continue;

                                $categories[] = array( 'name' => $csv_category, 'term_id' => 0, 'expires_on' => '' );
                            }
                        }
                    }/* else if ( 'tags' == $field->get_association() ) {
                        $tags = array_map( 'trim', explode( $this->settings['category-separator'], $value ) );
                        $fields[ $field->get_id() ] = $tags;
                    }*/ else {
                        $fields[ $field->get_id() ] = $field->convert_csv_input( $value, $this->settings );
                    }

                    break;
            }
        }

        if ( $categories && $expires_on ) {
            foreach ( $categories as $i => &$category_data ) {
                if ( ! empty( $expires_on[ $i ] ) )
                    $category_data['expires_on'] = $expires_on[ $i ];
            }
        }

        return array( compact( 'categories', 'fields', 'images', 'meta' ), $errors );
    }

    private function upload_image( $filename ) {
        $filepath = $this->images_dir . DIRECTORY_SEPARATOR . $filename;
        if ( ! $this->images_dir || ! file_exists( $filepath ) )
            return false;

        // Make a copy of the file because wpbdp_media_upload() moves the original file.
        copy( $filepath, $filepath . '.backup' );
        $media_id = wpbdp_media_upload( $filepath, true, true );
        rename( $filepath . '.backup', $filepath );

        return $media_id;
    }

    private function remove_bom( $str ) {
        if ( substr( $str, 0, 3 ) == pack( "CCC", 0xef, 0xbb, 0xbf ) )
            $str = substr( $str, 3 );

        return $str;
    }

}
