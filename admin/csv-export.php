<?php
require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

/**
 * CSV Export admin pages.
 * @since 3.2
 */
class WPBDP_Admin_CSVExport {
    
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-csv-export', array( &$this, 'ajax_csv_export' ) );
    }
    
    public function enqueue_scripts( $hook ) {
        if ( $hook != 'directory-admin_page_wpbdp-csv-export' )
            return;
        
        wp_enqueue_script( 'wpbdp-admin-export-js', WPBDP_URL . 'admin/resources/export.js', array( 'wpbdp-admin-js', 'jquery-ui-dialog' ) );
        wp_enqueue_style( 'wpbdp-admin-export-css', WPBDP_URL . 'admin/resources/export.css', array( 'wp-jquery-ui-dialog' ) );
    }
    
    public function dispatch() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/csv-export.tpl.php' );        
    }
    
    public function ajax_csv_export() {
        if ( !isset( $_REQUEST['state'] ) ) {
            $export = new WPBDP_CSVExporter( array_merge( $_REQUEST['settings'], array() ) );
        } else {
            $export = WPBDP_CSVExporter::from_state( unserialize( base64_decode( $_REQUEST['state'] ) ) );
            
            if ( isset( $_REQUEST['cleanup'] ) && $_REQUEST['cleanup'] == 1 ) {
                $export->cleanup();
            } else {
                $export->advance();
            }  
        }
        
        $state = $export->get_state();
        
        $response = array();
        $response['error'] = '';
        $response['state'] = base64_encode( serialize( $state ) );
        $response['count'] = count( $state['listings'] );
        $response['exported'] = $state['exported'];
        $response['filesize'] = size_format( $state['filesize'] );
        $response['isDone'] = $state['done'];
        $response['fileurl'] = $state['done'] ? $export->get_file_url() : '';        
        $response['filename'] = $state['done'] ? basename( $export->get_file_url() ) : '';
        
        echo json_encode( $response );
        
        die();
    }

}


require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * CSV export.
 * @since 3.2
 */
class WPBDP_CSVExporter {
    
    const BATCH_SIZE = 20;

    private $settings = array(
        'csv-file-separator' => ',',
        'images-separator' => ';',
        'category-separator' => ';',

        'test-import' => false,
        'export-images' => false,
        'include-users' => false,

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

        // Setup columns.
        $fields = wpbdp_get_form_fields();
        foreach ( $fields as &$f ) {
            $this->columns[ $f->get_short_name() ] = &$f;
        }

        if ( $this->settings['export-images'] )
            $this->columns['images'] = 'images';

        if ( $this->settings['include-users'] )
            $this->columns['username'] = 'username';

        if ( $this->settings['include-sticky-status'] )
            $this->columns['featured_level'] = 'featured_level';

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

                    if ( !mkdir( $this->workingdir ) )
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
    
    public function &from_state( $state ) {
        $export = new self( $state['settings'], trailingslashit( $state['workingdir'] ), (array) $state['listings'] );
        $export->exported = abs( intval( $state['exported'] ) );
        
        // Setup columns.
        $shortnames = wpbdp_formfields_api()->get_short_names();
        foreach ( $state['columns'] as $fshortname ) {
            $field_id = array_search( $fshortname );
            if ( $field_id === FALSE )
                throw new Exception( 'Invalid field shortname.' );

            $this->columns[ $fshortname ] = wpbdp_get_form_field( $field_id );
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
            'filesize' => filesize( $this->workingdir . ( file_exists( $this->workingdir . 'export.zip' ) ? 'export.zip' : 'export.csv' ) ),
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
        
        $csvfile = fopen( $this->workingdir . 'export.csv', 'a' );
        
        // Write header as first line.
        if ( $this->exported == 0 ) {
            fwrite( $csvfile, $this->header() . "\n" );
        }
        
        $nextlistings = array_slice( $this->listings, $this->exported, self::BATCH_SIZE );
        
        foreach ( $nextlistings as $listing_id ) {
            if ( $data = $this->extract_data( $listing_id ) )
                fwrite( $csvfile, implode( $this->settings['csv-file-separator'], $data ) . "\n" );

            $this->exported++;
        }
        
        fclose( $csvfile );
        
        if ( $this->is_done() ) {
            if ( file_exists( $this->workingdir . 'images.zip' ) ) {
                @unlink( $this->workingdir . 'export.zip' );
                $zip = new PclZip( $this->workingdir . 'export.zip' );
                
                $files = array();
                $files[] = $this->workingdir . 'export.csv';
                $files[] = $this->workingdir . 'images.zip';
                
                $zip->create( implode( ',', $files ) , PCLZIP_OPT_REMOVE_ALL_PATH );
                
                @unlink( $this->workingdir . 'export.csv' );
                @unlink( $this->workingdir . 'images.zip' );                
            }
        }
    }
    
    public function is_done() {
        return $this->exported == count( $this->listings );
    }
    
    public function get_file_url() {
        $uploaddir = wp_upload_dir();
        $urldir = trailingslashit( untrailingslashit( $uploaddir['baseurl'] ) . '/' . ltrim( str_replace( DIRECTORY_SEPARATOR, '/', str_replace( $uploaddir['basedir'], '', $this->workingdir ) ), '/' ) );
        
        if ( file_exists( $this->workingdir . 'export.zip' ) )
            return $urldir . 'export.zip';
        else
            return $urldir . 'export.csv';
        
        return $urldir . file_exists( $this->workingdir . 'export.zip' ) ? 'export.zip' : 'export.csv';
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
        $post = get_post( $post_id );

        if ( !$post || $post->post_type != WPBDP_POST_TYPE )
            return false;
    
        $listings_api = wpbdp_listings_api();
        $upgrades_api = wpbdp_listing_upgrades_api();
    
        $data = array();
    
        foreach ( $this->columns as $colname => &$col ) {
            $association = is_object( $col ) ? $col->get_association() : $col;            
            $value = '';
    
            switch( $association ) {
                /* Special columns. */
                case 'images':
                    $upload_dir = wp_upload_dir();
                    $listing_images = array();
    
                    if ( $images = $listings_api->get_images( $post->ID ) ) {
                        foreach ( $images as &$img ) {
                            $img_metadata = wp_get_attachment_metadata( $img->ID );
                            
                            if ( !isset( $img_metadata['file'] ) )
                                continue;
    
                            $img_path = realpath( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $img_metadata['file'] );
    
                            if ( !is_readable( $img_path ) )
                                continue;
                            
                            $this->images_archive = !isset( $this->images_archive ) ? new PclZip( $this->workingdir . 'images.zip' ) : $this->images_archive;
                            $this->images_archive->add( $img_path, PCLZIP_OPT_REMOVE_ALL_PATH );
                            
                            $listing_images[] = basename( $img_path );
                        }
                    }
    
                    if ( $listing_images )
                        $value = '"' . implode( $this->settings['images-separator'], $listing_images ) . '"';
    
                    break;
    
                case 'username':
                    $value = get_the_author_meta( 'user_login', $post->post_author );
                    break;
    
                case 'featured_level':
                    $listing_level = $upgrades_api->get_listing_level( $post->ID );
                    $value = $listing_level->id;
                    break;
    
                case 'expires_on':
                    $terms = wp_get_post_terms( $post->ID,
                                                WPBDP_CATEGORY_TAX,
                                                'fields=ids' );
                    $expiration_dates = array();
    
                    foreach ( $terms as $term_id ) {
                        if ( $fee = $listings_api->get_listing_fee_for_category( $post->ID, $term_id ) ) {
                            $expiration_dates[] = $fee->expires_on;
                        } else {
                            $expiration_dates[] = '';
                        }
                    }
    
                    $value = implode( '/', $expiration_dates );
    
                    break;
    
                /* Standard associations. */    
                case 'category':
                case 'tags':
                    $terms = wp_get_post_terms( $post->ID,
                                                $col->get_association() == 'tags' ? WPBDP_TAGS_TAX : WPBDP_CATEGORY_TAX,
                                                'fields=names' );
                    if ( $terms )
                        $value = implode( '"' . $this->settings['category-separator']  . '"',  $terms );
                    break;
                case 'meta':
                default:
                    $value = $col->plain_value( $post->ID );
    
                    break;
            }
    
            $data[ $colname ] = '"' . str_replace( '"', '""', $value ) . '"';
        }
    
        return $data;
    }

}