<?php

/**
 * CSV export.
 * @since 3.2
 */
class WPBDP_CSVExporter {

    const BATCH_SIZE = 20;

    private $settings = array(
        'target-os' => 'windows',
        'csv-file-separator' => ',',
        'images-separator' => ';',
        'category-separator' => ';',

        'test-import' => false,
        'export-images' => false,
        'include-users' => false,

        'generate-sequence-ids' => false,

        'listing_status' => 'all'
    );

    private $workingdir = '';

    private $columns = array();
    private $listings = array(); // Listing IDs to be exported.
    private $exported = 0; // # of already exported listings.
    private $images = array();

    public function __construct( $settings, $workingdir=null, $listings=array() ) {
        global $wpdb;

        $this->settings = array_merge( $this->settings, $settings );

        if ( ! in_array( $this->settings['target-os'], array( 'windows', 'macos' ), true ) ) {
            $this->settings['target-os'] = 'windows';
        }

        if ( $this->settings['target-os'] == 'macos' ) {
            $this->settings['csv-file-separator'] = "\t";
        }

        // Setup columns.
        if ( $this->settings['generate-sequence-ids'] )
            $this->columns['sequence_id'] = 'sequence_id';

        $fields = wpbdp_get_form_fields( array( 'field_type' => '-ratings' ) );
        foreach ( $fields as &$f ) {
            $this->columns[ $f->get_short_name() ] = $f;
        }

        if ( $this->settings['export-images'] )
            $this->columns['images'] = 'images';

        if ( $this->settings['include-users'] )
            $this->columns['username'] = 'username';

        $this->columns['fee_id'] = 'fee_id';

        if ( $this->settings['include-expiration-date'] )
            $this->columns['expires_on'] = 'expires_on';

        // Setup working directory.
        if ( !$workingdir ) {
            $direrror = '';

            $upload_dir = wp_upload_dir();

            if ( !$upload_dir['error'] ) {
                $csvexportsdir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-exports';
                if ( is_dir( $csvexportsdir ) || mkdir( $csvexportsdir ) ) {
                    $this->workingdir = rtrim( $csvexportsdir . DIRECTORY_SEPARATOR . uniqid(), DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

                    if ( !mkdir( $this->workingdir, 0777 ) )
                        $direrror = _x( 'Could not create a temporary directory for handling this CSV export.', 'admin csv-export', 'WPBDM' );
                } else {
                    $direrror = _x( 'Could not create wpbdp-csv-exports directory.', 'admin csv-export', 'WPBDM' );
                }
            }

            if ( $direrror )
                throw new Exception( sprintf( _x( 'Error while creating a temporary directory for CSV export: %s', 'admin csv-export', 'WPBDM' ), $direrror ) );
        } else {
            $this->workingdir = $workingdir;
        }

        if ( $listings ) {
            $this->listings = $listings;
        } else {
            switch ( $this->settings['listing_status'] ) {
                case 'publish+draft':
                    $post_status = array( 'publish', 'draft', 'pending' );
                    break;
                case 'publish':
                    $post_status = 'publish';
                    break;
                case 'all':
                default:
                    $post_status = array( 'publish', 'draft', 'pending', 'future', 'trash' );
                    break;
            }

            $this->listings = get_posts( array(
                'post_status' => $post_status,
                'posts_per_page' => -1,
                'post_type' => WPBDP_POST_TYPE,
                'fields' => 'ids'
            ) );
        }
    }

    public static function &from_state( $state ) {
        $export = new self( $state['settings'], trailingslashit( $state['workingdir'] ), (array) $state['listings'] );
        $export->exported = abs( intval( $state['exported'] ) );

        // Setup columns.
        $shortnames = wpbdp_formfields_api()->get_short_names();

        foreach ( $state['columns'] as $fshortname ) {
            if ( in_array( $fshortname, array( 'images', 'username', 'expires_on', 'sequence_id', 'fee_id' ) ) ) {
                $export->columns[ $fshortname ] = $fshortname;
                continue;
            }

            $field_id = array_search( $fshortname, $shortnames );

            if ( $field_id === FALSE )
                throw new Exception( 'Invalid field shortname.' );

            $export->columns[ $fshortname ] = wpbdp_get_form_field( $field_id );
        }

        return $export;
    }

    public function get_state() {
        return array(
            'settings' => $this->settings,
            'columns' => array_keys( $this->columns ),
            'workingdir' => $this->workingdir,
            'listings' => $this->listings,
            'exported' => $this->exported,
            'filesize' => file_exists( $this->get_file_path() ) ?  filesize( $this->get_file_path() ) : 0,
            'done' => $this->is_done()
        );
    }

    public function cleanup() {
        $upload_dir = wp_upload_dir();

        wpbdp_rrmdir( $this->workingdir );

        if ( !$upload_dir['error'] ) {
            $csvexportsdir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-exports';
            $contents = wpbdp_scandir( $csvexportsdir );

            if ( !$contents )
                wpbdp_rrmdir( $csvexportsdir );
        }
    }

    public function advance() {
        if ( $this->is_done() )
            return;

        $csvfile = $this->get_csvfile( $this->workingdir . 'export.csv' );

        // Write header as first line.
        if ( $this->exported === 0 ) {
            fwrite( $csvfile, $this->prepare_header( $this->header() ) );
        }

        $nextlistings = array_slice( $this->listings, $this->exported, self::BATCH_SIZE );

        foreach ( $nextlistings as $listing_id ) {
            if ( $data = $this->extract_data( $listing_id ) ) {
                $content = implode( $this->settings['csv-file-separator'], $data );
                fwrite( $csvfile, $this->prepare_content( $content ) );
            }

            $this->exported++;
        }

        fclose( $csvfile );

        if ( $this->is_done() ) {
            if ( file_exists( $this->workingdir . 'images.zip' ) ) {
                @unlink( $this->workingdir . 'export.zip' );
                $zip = $this->get_pclzip_instance( $this->workingdir . 'export.zip' );

                $files = array();
                $files[] = $this->workingdir . 'export.csv';
                $files[] = $this->workingdir . 'images.zip';

                $zip->create( implode( ',', $files ) , PCLZIP_OPT_REMOVE_ALL_PATH );

                @unlink( $this->workingdir . 'export.csv' );
                @unlink( $this->workingdir . 'images.zip' );
            }
        }
    }

    protected function get_csvfile( $path ) {
        return fopen( $path, 'a' );
    }

    protected function get_pclzip_instance( $path ) {
        if ( ! class_exists( 'PclZip' ) ) {
            define( 'PCLZIP_TEMPORARY_DIR', $this->workingdir );
            require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
        }

        return new PclZip( $path );
    }
    public function is_done() {
        return $this->exported == count( $this->listings );
    }

    private function prepare_header( $header ) {
        if ( $this->settings['target-os'] === 'windows' ) {
            $bom = "\xEF\xBB\xBF"; /* UTF-8 BOM */
        } else if ( $this->settings['target-os'] === 'macos' ) {
            $bom = "\xFF\xFE"; /* UTF-16LE BOM */
        }

        return $bom . $this->prepare_content( $header );
    }

    private function prepare_content( $content ) {
        if ( $this->settings['target-os'] === 'windows' ) {
            $encoded_content = $content . "\n";
        } else if ( $this->settings['target-os'] === 'macos' ) {
            $encoded_content = iconv( 'UTF-8', 'UTF-16LE', $content . "\n" );
        }

        return $encoded_content;
    }

    public function get_file_path() {
        if ( file_exists( $this->workingdir . 'export.zip' ) )
            return $this->workingdir . 'export.zip';
        else
            return $this->workingdir . 'export.csv';
    }

    public function get_file_url() {
        $uploaddir = wp_upload_dir();
        $urldir = trailingslashit( untrailingslashit( $uploaddir['baseurl'] ) . '/' . ltrim( str_replace( DIRECTORY_SEPARATOR, '/', str_replace( $uploaddir['basedir'], '', $this->workingdir ) ), '/' ) );

        if ( file_exists( $this->workingdir . 'export.zip' ) )
            return $urldir . 'export.zip';
        else
            return $urldir . 'export.csv';
    }

    private function header( $echo=false ) {
        $out = '';

        foreach ( $this->columns as $colname => &$col ) {
            $out .= $colname;
            $out .= $this->settings['csv-file-separator'];
        }

        $out = substr( $out, 0, -1 );

        if ( $echo )
            echo $out;

        return $out;
    }

    private function extract_data( $post_id ) {
        $listing = wpbdp_get_listing( $post_id );

        if ( ! $listing ) {
            return false;
        }

        $data = array();

        foreach ( $this->columns as $column_name => $column_obj ) {
            $value = '';

            switch ( $column_name ) {
            case 'sequence_id':
                $value = $listing->get_sequence_id();
                break;
            case 'username':
                $value = $listing->get_author_meta( 'login' );
                break;
            case 'images':
                $images = array();

                if ( $image_ids = $listing->get_images( 'ids' ) ) {
                    $upload_dir = wp_upload_dir();

                    foreach ( $image_ids as $image_id ) {
                        $img_meta = wp_get_attachment_metadata( $image_id );

                        if ( empty( $img_meta['file'] ) ) {
                            continue;
                        }

                        $img_path = realpath( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $img_meta['file'] );

                        if ( ! is_readable( $img_path ) ) {
                            continue;
                        }

                        $this->images_archive = ( ! isset( $this->images_archive ) ) ? $this->get_pclzip_instance( $this->workingdir . 'images.zip' ) : $this->images_archive;
                        if ( $success = $this->images_archive->add( $img_path, PCLZIP_OPT_REMOVE_ALL_PATH ) ) {
                            $images[] = basename( $img_path );
                        }
                    }
                }

                $value = implode( $this->settings['images-separator'], $images );
                break;
            case 'fee_id':
                $plan = $listing->get_fee_plan();

                if ( isset( $plan->fee_id ) ) {
                    $value = $plan->fee_id;
                }

                break;
            case 'expires_on':
            case 'expiration_date':
                $plan = $listing->get_fee_plan();

                if ( isset( $plan->expiration_date ) ) {
                    $value = $plan->expiration_date;
                }

                break;
            default:
                if ( is_object( $column_obj ) ) {
                    $field = $column_obj;

                    switch ( $field->get_association() ) {
                    case 'category':
                    case 'tags':
                        $value = wp_get_post_terms( $listing->get_id(), ( 'tags' == $field->get_association() ? WPBDP_TAGS_TAX : WPBDP_CATEGORY_TAX ), 'fields=names' );
                        $value = array_map( 'html_entity_decode', $value );
                        $value = implode( $this->settings['category-separator'], $value );
                        break;
                    case 'meta':
                    default:
                        $value = $field->csv_value( $listing->get_id() );
                        break;
                    }
                }

                break;
            }

            if ( ! is_string( $value ) && ! is_array( $value ) ) {
                $value = strval( $value );
            }

            $data[ $column_name ] = '"' . str_replace( '"', '""', $value ) . '"';
        }

        return $data;
    }
}
