<?php
require_once( WPBDP_PATH . 'core/class-csv-import.php' );

/**
 * CSV Import admin pages.
 * @since 2.1
 */
class WPBDP_CSVImportAdmin {

    function __construct() {
        global $wpbdp;

        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-csv-import', array( &$this, 'ajax_csv_import' ) );
        add_action( 'wp_ajax_wpbdp-autocomplete-user', array( &$this, 'ajax_autocomplete_user' ) );
    }

    function enqueue_scripts() {
        global $plugin_page;

        if ( 'wpbdp-csv-import' != $plugin_page )
            return;

        wp_enqueue_script( 'wpbdp-admin-import-js', WPBDP_URL . 'admin/js/csv-import.js', array( 'wpbdp-admin-js', 'jquery-ui-autocomplete' ) );
        wp_enqueue_style( 'wpbdp-admin-import-css', WPBDP_URL . 'admin/css/csv-import.css' );
    }

    function ajax_csv_import() {
        global $wpbdp;

        if ( ! current_user_can( 'administrator' ) )
            die();

        $import_id = ! empty( $_POST['import_id'] ) ? $_POST['import_id'] : 0;

        if ( ! $import_id )
            die();

        $res = new WPBDP_Ajax_Response();

        try {
            $import = new WPBDP_CSV_Import( $import_id );
        } catch ( Exception $e ) {
            if ( $import )
                $import->cleanup();
            $res->send_error( $e->getMessage() );
        }

        if ( ! empty ( $_POST['cleanup'] ) ) {
            $import->cleanup();
            $res->send();
        }

        $wpbdp->_importing_csv = true;
        $wpbdp->_importing_csv_no_email = (bool) $import->get_setting( 'disable-email-notifications' );

        $import->do_work();

        unset( $wpbdp->_importing_csv ); unset( $wpbdp->_importing_csv_no_email );

        $res->add( 'done', $import->done() );
        $res->add( 'progress', $import->get_progress( 'n' ) );
        $res->add( 'total', $import->get_import_rows_count() );
        $res->add( 'imported', $import->get_imported_rows_count() );
        $res->add( 'rejected', $import->get_rejected_rows_count() );

        if ( $import->done() ) {
            $res->add( 'warnings', $import->get_errors() );
            $import->cleanup();
        }

        $res->send();
    }

    public function ajax_autocomplete_user() {
        $users = get_users( array( 'search' => "*{$_REQUEST['term']}*" ) );

        foreach ( $users as $user ) {
            $return[] = array(
                'label' => "{$user->display_name} ({$user->user_login})",
                'value' => $user->ID,
            );
        }

        wp_die( wp_json_encode( $return ) );
    }

    function dispatch() {
        $action = wpbdp_getv( $_REQUEST, 'action' );

        switch ( $action ) {
            case 'example-csv':
                $this->example_csv();
                break;
            case 'do-import':
                $this->import();
                break;
            default:
                $this->import_settings();
                break;
        }
    }

    private function example_data_for_field( $field=null, $shortname=null ) {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ( $field ) {
            if ( $field->get_association() == 'title' ) {
                return sprintf(_x('Business %s', 'admin csv-import', 'WPBDM'), $letters[rand(0,strlen($letters)-1)]);
            } elseif ( $field->get_association() == 'category') {
                if ( $terms = get_terms(WPBDP_CATEGORY_TAX, 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }
            } elseif ($field->get_association() == 'tags') {
                if ( $terms = get_terms(WPBDP_TAGS_TAX, 'number=5&hide_empty=0') ) {
                    return $terms[array_rand($terms)]->name;
                } else {
                    return '';
                }                
            } elseif ( $field->has_validator( 'url' ) ) {
                return get_site_url();
            } elseif ( $field->has_validator( 'email' ) ) {
                return get_option( 'admin_email' );
            } elseif ( $field->has_validator('integer_number') ) {
                return rand(0, 100);
            } elseif ( $field->has_validator( 'decimal_number' ) ) {
                return rand(0, 100) / 100.0;
            } elseif ( $field->has_validator( 'date_' ) ) {
                return date( 'd/m/Y' );
            } elseif ( $field->get_field_type()->get_id() == 'multiselect' || $field->get_field_type()->get_id() == 'checkbox' ) {
                if ( $field->data( 'options' ) ) {
                    $options = $field->data( 'options' );
                    return $options[array_rand($options)];
                }
                
                return '';
            }
        }

        if ($shortname == 'user') {
            $users = get_users();
            return $users[array_rand($users)]->user_login;
        }

        return _x('Whatever', 'admin csv-import', 'WPBDM');
    }

    private function example_csv() {
        echo wpbdp_admin_header(_x('Example CSV Import File', 'admin csv-import', 'WPBDM'), null, array(
            array(_x('← Return to "CSV Import"', 'admin csv-import', 'WPBDM'), esc_url(remove_query_arg('action')))
        ));

        $posts = get_posts(array(
            'post_type' => WPBDP_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 10
        ));

        //echo sprintf('<input type="button" value="%s" />', _x('Copy CSV', 'admin csv-import', 'WPBDM'));
        echo '<textarea class="wpbdp-csv-import-example" rows="30">';

        $fields_api = wpbdp_formfields_api();

        $short_names = $fields_api->get_short_names();

        foreach ($short_names as $name) {
            echo $name . ',';
        }
        echo 'username';
        echo "\n";

        if (count($posts) >= 5) {
            foreach ($posts as $post) {
                foreach (array_keys($short_names) as $field_id) {
                    $field = $fields_api->get_field( $field_id );
                    $value = $field->plain_value( $post->ID );

                    echo str_replace( ',', ';', $value );
                    echo ',';
                }
                echo get_the_author_meta('user_login', $post->post_author);

                echo "\n";
            }
        } else {
            for ($i = 0; $i < 5; $i++) {
                foreach ($short_names as $field_id => $shortname) {
                    $field = $fields_api->get_field( $field_id );
                    echo sprintf( '"%s"', $this->example_data_for_field( $field, $shortname ) );
                    echo ',';
                }

                echo sprintf( '"%s"', $this->example_data_for_field( null, 'user' ) );
                echo "\n";
            }
            
        }

        echo '</textarea>';

        echo wpbdp_admin_footer();
    }

    private function get_imports_dir() {
        $upload_dir = wp_upload_dir();

        if ( $upload_dir['error'] )
            return false;

        $imports_dir = rtrim( $upload_dir['basedir'], DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'wpbdp-csv-imports';
        return $imports_dir;
    }

    private function find_uploaded_files() {
        $base_dir = $this->get_imports_dir();

        $res = array( 'images' => array(), 'csv' => array() );

        if ( is_dir( $base_dir ) ) {
            $files = wpbdp_scandir( $base_dir );

            foreach ( $files as $f_ ) {
                $f = $base_dir . DIRECTORY_SEPARATOR . $f_;

                if ( ! is_file( $f ) || ! is_readable( $f ) )
                    continue;

                switch ( strtolower( substr( $f, -4 ) ) ) {
                    case '.csv':
                        $res['csv'][] = $f;
                        break;
                    case '.zip':
                        $res['images'][] = $f;
                        break;
                    default:
                        break;
                }
            }
        }

        return $res;
    }

    private function import_settings() {
        $import_dir = $this->get_imports_dir();

        if ( $import_dir && ! is_dir( $import_dir ) )
            @mkdir( $import_dir, 0777 );

        $files = array();

        if ( ! $import_dir || ! is_dir( $import_dir ) || ! is_writable( $import_dir ) ) {
            wpbdp_admin_message( sprintf( __( 'A valid temporary directory with write permissions is required for CSV imports to function properly. Your server is using "%s" but this path does not seem to be writable. Please consult with your host.',
                                              'csv import',
                                              'WPBDM' ),
                                         $import_dir ) );
        }

        $files = $this->find_uploaded_files();

        // Retrieve last used settings to use as defaults.
        $defaults = get_user_option( 'wpbdp-csv-import-settings' );
        if ( ! $defaults || ! is_array( $defaults ) )
            $defaults = array();

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/csv-import.tpl.php',
                                array( 'files' => $files,
                                       'defaults' => $defaults ) );
    }

    private function import() {
        $sources = array();
        $csv_file = '';
        $zip_file = '';

        // CSV file.
        if ( ! empty( $_POST['csv-file-local'] ) ) {
            $csv_file = $this->get_imports_dir() . DIRECTORY_SEPARATOR . basename( $_POST['csv-file-local'] );
            $sources[] = basename( $csv_file );
        }

        if ( ! $csv_file && ! empty( $_FILES['csv-file'] ) ) {
            if ( ! $_FILES['csv-file']['error'] && is_uploaded_file( $_FILES['csv-file']['tmp_name'] ) ) {
                $sources[] = $_FILES['csv-file']['name'];
                $csv_file = $_FILES['csv-file']['tmp_name'];
            } elseif ( UPLOAD_ERR_NO_FILE != $_FILES['csv-file']['error'] ) {
                wpbdp_admin_message( _x( 'There was an error uploading the CSV file.', 'admin csv-import', 'WPBDM' ), 'error' );
                return $this->import_settings();
            }
        }

        if ( ! $csv_file ) {
            wpbdp_admin_message( _x( 'Please upload or select a CSV file.', 'admin csv-import', 'WPBDM' ), 'error' );
            return $this->import_settings();
        }

        // Images file.
        if ( ! empty( $_POST['images-file-local'] ) ) {
            $zip_file = $this->get_imports_dir() . DIRECTORY_SEPARATOR . basename( $_POST['images-file-local'] );
            $sources[] = basename( $zip_file );
        }

        if ( ! $zip_file && ! empty( $_FILES['images-file'] ) ) {
            if ( UPLOAD_ERR_NO_FILE == $_FILES['images-file']['error'] ) {
            } else if ( ! is_uploaded_file( $_FILES['images-file']['tmp_name'] ) ) {
                wpbdp_admin_message( _x( 'There was an error uploading the images ZIP file.', 'admin csv-import', 'WPBDM' ), 'error' );
                return $this->import_settings();
            }

            $zip_file = $_FILES['images-file']['tmp_name'];
            $sources[] = $_FILES['images-file']['name'];
        }

        // Store settings to use as defaults next time.
        update_user_option( get_current_user_id(), 'wpbdp-csv-import-settings', $_POST['settings'], false );

        $import = null;
        try {
            $import = new WPBDP_CSV_Import( '',
                                            $csv_file,
                                            $zip_file,
                                            array_merge( $_POST['settings'], array( 'test-import' => ! empty( $_POST['test-import'] ) ) ) );
        } catch ( Exception $e ) {
            if ( $import )
                $import->cleanup();

            $error  = _x( 'An error was detected while validating the CSV file for import. Please fix this before proceeding.', 'admin csv-import', 'WPBDM' );
            $error .= '<br />';
            $error .= '<b>' . $e->getMessage() . '</b>';

            wpbdp_admin_message( $error, 'error' );
            return $this->import_settings();
        }

        if ( $import->in_test_mode() )
            wpbdp_admin_message( _x( 'Import is in "test mode". Nothing will be inserted into the database.', 'admin csv-import', 'WPBDM' ) );

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/csv-import-progress.tpl.php',
                                array( 'import' => $import,
                                       'sources' => $sources ) );
    }

}



