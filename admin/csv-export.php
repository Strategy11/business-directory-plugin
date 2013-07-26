<?php
/**
 * CSV Export admin pages.
 * @since 3.2
 */
class WPBDP_Admin_CSVExport {

    public static function menu_callback() {
        return self::setup_export();
    }

    public static function download() {
        $settings = array_merge( $_POST['settings'], array() );

        $exporter = new WPBDP_CSVExporter( $settings );
        if ( $exporter->export() ) {
            $zipfile = $exporter->has_images() ? $exporter->zip_file() : '';

            header( 'Content-Description: File Transfer' );

            if ( $zipfile ) {
                header( 'Content-Type: application/zip;', true );
                header( 'Content-Disposition: attachment; filename=' . basename( $zipfile ), true );
            } else {
                header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
                header( 'Content-Disposition: attachment; filename=' . 'export.csv' );
            }

            header( 'Content-Transfer-Encoding: binary' );
            header( 'Expires: 0' );
            header( 'Cache-Control: must-revalidate' );
            header( 'Pragma: no-cache' );
            // header('Content-Length: ' . filesize($file));

            if ( $zipfile )
                readfile( $zipfile );
            else
                readfile( $exporter->csv_file() );
        }

        $exporter->cleanup();
        
        die();
    }

    /*
     * Views
     */

    private static function setup_export() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/csv-export.tpl.php' );
    }

}


require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

/**
 * CSV export.
 * @since 3.2
 */
class WPBDP_CSVExporter {

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
    private $exported = array();
    private $images = array();

    public function __construct( $settings ) {
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
        $temp_name = tempnam( function_exists( 'sys_get_temp_dir' ) ? sys_get_temp_dir() : getenv( 'TMP' ), 'wpbdp_' );
        
        if ( file_exists( $temp_name ) )
            unlink( $temp_name );

        mkdir( $temp_name );
        if ( !is_dir( $temp_name ) )
            exit;

        $this->workingdir = trailingslashit( $temp_name );
        @mkdir( $this->workingdir . 'images' );
    }

    public function export() {
        $csv_file = fopen( $this->workingdir . 'export.csv', 'wb' );

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

        $posts = get_posts( array(
            'post_status' => $post_status,
            'posts_per_page' => -1,
            'post_type' => WPBDP_POST_TYPE
        ) );

        fwrite( $csv_file, $this->header() );
        fwrite( $csv_file, PHP_EOL );

        foreach ( $posts as &$post ) {
            $data = $this->extract_data( $post );
            fwrite( $csv_file, implode( $this->settings['csv-file-separator'], $data ) );
            fwrite( $csv_file, PHP_EOL );

            $this->exported[] = $post->ID;
        }

        fclose( $csv_file );

        return true;
    }

    public function cleanup() {
    }

    public function csv_file() {
        return $this->workingdir . 'export.csv';
    }

    public function images_zip() {
        if ( file_exists( $this->workingdir . 'images.zip' ) )
            return $this->workingdir . 'images.zip';

        if ( !$this->images )
            return '';

        require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

        $zip = new PclZip( $this->workingdir . 'images.zip' );
        $zip->add( $this->workingdir . 'images/', PCLZIP_OPT_REMOVE_ALL_PATH );

        return $this->workingdir . 'images.zip';
    }

    public function zip_file() {
        if ( file_exists( $this->workingdir . 'export.zip' ) )
            return $this->workingdir . 'export.zip';

        $images_zip = $this->images_zip();
        $csv_file = $this->workingdir . 'export.csv';

        if ( !file_exists( $csv_file ) )
            return '';

        require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

        $files = $csv_file . ( $images_zip ? ',' . $images_zip : '' );

        $zip = new PclZip( $this->workingdir . 'export.zip' );
        $zip->create( $files, PCLZIP_OPT_REMOVE_ALL_PATH );

        return $this->workingdir . 'export.zip';        
    }

    public function has_images() {
        return count( $this->images ) > 0;
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

    private function extract_data( &$post ) { // XXX: maybe change to 'post_id' so it is more lightweight
        if ( !$post )
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

                            $newpath = $this->workingdir . 'images' . DIRECTORY_SEPARATOR . $img->ID . '_' . basename( $img_path );
                            if ( copy( $img_path, $newpath ) )
                                $listing_images[] = basename( $newpath );
                        }

                        $this->images = array_merge( $this->images, $listing_images );
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

        //         'images-separator' => ';',
        // 'category-separator' => ';',

        // Export user? images? dates?

        return $data;
    }

}