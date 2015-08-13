<?php

class WPBDP_Themes_Admin {

    private $api;

    function __construct( &$api ) {
        $this->api = $api;

        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_wpbdp-theme-set', array( &$this, 'ajax_set_active_theme' ) );
    }

    function admin_menu( $slug ) {
        add_submenu_page( $slug,
                          _x( 'Themes', 'themes', 'WPBDM' ),
                          _x( 'Themes', 'themes', 'WPBDM' ),
                          'administrator',
                          'wpbdp-themes',
                          array( &$this, 'dispatch' ) );
    }

    function enqueue_scripts() {
        global $wpbdp;
        global $pagenow;

        $debug_on = $wpbdp->is_debug_on();

        wp_enqueue_style( 'wpbdp-admin-themes',
                          WPBDP_URL . 'admin/css/themes' . ( ! $debug_on ? '.min' : '' ) . '.css' );
        wp_enqueue_script( 'wpbdp-admin-themes',
                           WPBDP_URL . 'admin/js/themes' . ( ! $debug_on ? '.min' : '' ) . '.js' );
    }

    function ajax_set_active_theme() {
        $theme_id = isset( $_POST['theme_id'] ) ? $_POST['theme_id'] : '';
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

        if ( ! current_user_can( 'administrator' ) || ! $theme_id || ! $nonce || ! wp_verify_nonce( $nonce, 'activate theme ' . $theme_id ) )
            exit();

        $res = new WPBDP_Ajax_Response();

        if ( ! $this->api->set_active_theme( $theme_id ) )
            $res->send_error( sprintf( _x( 'Could not change the active theme to "%s".', 'themes', 'WPBDM' ), $theme_id ) );

        $res->set_message( sprintf( _x( 'Active theme changed to "%s".', 'themes', 'WPBDM' ), $theme_id ) );
        $res->send();
    }

    function dispatch() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : '';

        switch ( $action ) {
            case 'theme-install':
                return $this->theme_install();
                break;
            case 'theme-selection':
            default:
                return $this->theme_selection();
                break;
        }
    }

    function theme_selection() {
        $themes = $this->api->get_installed_themes();

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes.tpl.php',
                                array( 'themes' => $themes,
                                       'active_theme' => $this->api->get_active_theme() ) );
    }

    function theme_install() {
        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
        $theme_file = false;

        if ( wp_verify_nonce( $nonce, 'upload theme zip' ) ) {
            if ( isset( $_FILES['themezip'] ) && UPLOAD_ERR_OK == $_FILES['themezip']['error'] &&
                 is_uploaded_file( $_FILES['themezip']['tmp_name'] ) ) {
                $theme_file = $_FILES['themezip'];
                $dest = wp_normalize_path( untrailingslashit( get_temp_dir() ) . DIRECTORY_SEPARATOR . $theme_file['name'] );

                if ( ! move_uploaded_file( $theme_file['tmp_name'], $dest ) ) {
                    wpbdp_admin_message( sprintf( _x( 'Could not move "%s" to a temporary directory.', 'themes', 'WPBDM' ),
                                                  $theme_file['name'] ),
                                         'error' );
                } else {
                    $res = $this->api->install_theme( $dest );
                    
                    if ( is_wp_error( $res ) ) {
                        wpbdp_admin_message( $res->get_error_message(), 'error' );
                    } else {
                        wpbdp_admin_message( _x( 'Theme installed successfully.', 'themes', 'WPBDM' ) );
                        return $this->theme_selection();
                    }
                }
            } else {
                wpbdp_admin_message( _x( 'Please upload a valid theme file.', 'themes', 'WPBDM' ), 'error' );
            }
        }

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-install.tpl.php',
                                array() );
    }

}
