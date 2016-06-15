<?php
/**
 * @since 4.0
 */
class WPBDP_Themes_Admin {

    private $api;
    private $updater;


    function __construct( &$api ) {
        $this->api = $api;

        require_once( WPBDP_PATH . 'core/helpers/class-themes-updater.php' );
        $this->updater = new WPBDP_Themes_Updater( $this->api );

        add_action( 'wp_ajax_wpbdp-themes-activate-license', array( &$this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_wpbdp-themes-deactivate-license', array( &$this, 'ajax_deactivate_license' ) );


        add_filter( 'wpbdp_admin_menu_badge_number', array( &$this, 'admin_menu_badge_count' ) );
        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );
        add_filter( 'wpbdp_admin_menu_reorder', array( &$this, 'admin_menu_move_themes_up' ) );

        add_action( 'wpbdp_admin_notices', array( &$this, 'pre_themes_templates_warning' ) );
        add_action( 'wpbdp_admin_notices', array( &$this, 'warn_about_licenses' ) );

        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

        add_action( 'wpbdp_action_set-active-theme', array( &$this, 'set_active_theme' ) );
        add_action( 'wpbdp_action_delete-theme', array( &$this, 'delete_theme' ) );
        add_action( 'wpbdp_action_upload-theme', array( &$this, 'upload_theme' ) );
        add_action( 'wpbdp_action_create-theme-suggested-fields', array( &$this, 'create_suggested_fields' ) );

        add_action( 'wpbdp-admin-themes-extra', array( &$this, 'enter_license_key_row' ) );
    }

    function admin_menu( $slug ) {
        $count = $this->updater->get_updates_count();

        if ( $count )
            $count_html = '<span class="update-plugins"><span class="plugin-count">' . number_format_i18n( $count ) . '</span></span>';
        else
            $count_html = '';

        add_submenu_page( $slug,
                          _x( 'Directory Themes', 'themes', 'WPBDM' ),
                          sprintf( _x( 'Directory Themes %s', 'themes', 'WPBDM' ), $count_html ),
                          'administrator',
                          'wpbdp-themes',
                          array( &$this, 'dispatch' ) );
    }

    function admin_menu_badge_count( $cnt = 0 ) {
        return ( (int) $cnt ) + $this->updater->get_updates_count();
    }

    function admin_menu_move_themes_up( $menu ) {
        $themes_key = false;

        foreach ( $menu as $k => $i ) {
            if ( 'wpbdp-themes' === $i[2] ) {
                $themes_key = $k;
                break;
            }
        }

        if ( false === $themes_key )
            return $menu;

        $themes = $menu[ $themes_key ];
        unset( $menu[ $themes_key ] );
        $menu = array_merge( array( $menu[0], $themes ), array_slice( $menu, 1 ) );

        return $menu;
    }

    function pre_themes_templates_warning() {
        $pre_themes_templates = array( 'businessdirectory-excerpt',
                                       'businessdirectory-listing',
                                       'businessdirectory-listings',
                                       'businessdirectory-main-page' );
        $overridden = array();

        foreach ( $pre_themes_templates as $t ) {
            if ( $f = wpbdp_locate_template( $t, true, false ) )
                $overridden[ $t ] = str_replace( WP_CONTENT_DIR, '', $f );
        }

        if ( ! $overridden )
            return;

        $msg  =  '';
        $msg .= '<strong>' . _x( 'Business Directory Plugin - Your template overrides need to be reviewed!', 'admin themes', 'WPBDM' ) . '</strong>';
        $msg .= '<br />';
        $msg .= _x( 'Starting with version 4.0, Business Directory is using a new theming system that is not compatible with the templates used in previous versions.', 'admin themes', 'WPBDM' );
        $msg .= '<br />';
        $msg .= _x( 'Because of this, your template overrides below have been disabled. You should <a>review our documentation on customization</a> in order adjust your templates.', 'admin themes', 'WBPDM' );
        $msg .= '<br /><br />';

        foreach ( $overridden as $t => $relpath ) {
            $msg .= '&#149; <tt>' . $relpath . '</tt><br />';
        }

        wpbdp_admin_message( $msg, 'error' );
    }

    function warn_about_licenses() {
        global $pagenow;

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp-themes' != $_GET['page'] )
            return;

        $themes = $this->api->get_installed_themes();
        $licenses_needed = false;

        foreach ( $themes as $t ) {
            if ( ! $t->can_be_activated ) {
                $licenses_needed = true;
                break;
            }
        }

        if ( ! $licenses_needed )
            return;

        $msg = _x( 'You need to <a>activate your theme\'s license key</a> before you can activate the theme. <a>Click here</a> to do that.',
                   'admin themes',
                   'WPBDM' );
        wpbdp_admin_message( $msg, 'error' );
    }

    function enqueue_scripts( $hook ) {
        global $wpbdp;
        global $pagenow;

        if ( 'admin.php' != $pagenow || ! isset( $_GET['page'] ) || 'wpbdp-themes' != $_GET['page'] )
            return;

        $debug_on = $wpbdp->is_debug_on();

        wp_enqueue_style( 'wpbdp-admin-themes',
                          WPBDP_URL . 'admin/css/themes' . ( ! $debug_on ? '.min' : '' ) . '.css' );
        wp_enqueue_script( 'wpbdp-admin-themes',
                           WPBDP_URL . 'admin/js/themes' . ( ! $debug_on ? '.min' : '' ) . '.js' );
    }

    function set_active_theme() {
        $theme_id = isset( $_POST['theme_id'] ) ? $_POST['theme_id'] : '';

        if ( ! current_user_can( 'administrator' ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'activate theme ' . $theme_id ) )
            wp_die();

        if ( ! $this->api->set_active_theme( $theme_id ) )
            wp_die( sprintf( _x( 'Could not change the active theme to "%s".', 'themes', 'WPBDM' ), $theme_id ) );

//        $this->api->try_active_theme();
//        wpbdp_debug_e( $theme_id );

        wp_redirect( admin_url( 'admin.php?page=wpbdp-themes&message=1' ) );
        exit;
    }

    function create_suggested_fields() {
        if ( ! current_user_can( 'administrator' ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'create_suggested_fields' ) )
            wp_die();

        $missing = $this->api->missing_suggested_fields();

        global $wpbdp;
        $wpbdp->formfields->create_default_fields( $missing );

        wp_safe_redirect( admin_url( 'admin.php?page=wpbdp_admin_formfields&action=updatetags' ) );
        exit;
    }

    function dispatch() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : ( isset( $_GET['v'] ) ? $_GET['v'] : '' );

        switch ( $action ) {
            case 'theme-install':
                return $this->theme_install();
                break;
            case 'delete-theme':
                return $this->theme_delete_confirm();
                break;
            case 'licenses':
                return $this->theme_licenses();
                break;
            case 'theme-selection':
            default:
                return $this->theme_selection();
                break;
        }
    }

    function theme_selection() {
        $msg = isset( $_GET['message'] ) ? $_GET['message'] : '';

        switch ( $msg ) {
            case 1:
                wpbdp_admin_message( sprintf( _x( 'Active theme changed to "%s".', 'themes', 'WPBDM' ), $this->api->get_active_theme() ) );

                if ( $missing_fields = $this->api->missing_suggested_fields( 'label' ) ) {
                    $msg  = sprintf( _x( '%s requires that you tag your existing fields to match some places we want to put your data on the theme. Below are fields we think are missing.', 'themes', 'WPBDM' ), $this->api->get_active_theme() );
                    $msg .= '<br />';

                    foreach ( $missing_fields as $mf )
                        $msg .= '<span class="tag">' . $mf . '</span>';

                    $msg .= '<br /><br />';
                    $msg .= sprintf( '<a href="%s" class="button button-primary">%s</a>',
                                     admin_url( 'admin.php?page=wpbdp_admin_formfields&action=updatetags' ),
                                     _x( 'Map My Fields', 'themes', 'WPBDM' ) );

                    wpbdp_admin_message( $msg, 'error' );
                }

                break;
            case 2:
                wpbdp_admin_message( _x( 'Suggested fields created successfully.', 'themes', 'WPBDM' ) );
                break;
            case 3:
                wpbdp_admin_message( _x( 'Theme installed successfully.', 'themes', 'WPBDM' ) );
                break;
            case 4:
                wpbdp_admin_message( _x( 'Theme was deleted sucessfully.', 'themes', 'WPBDM' ) );
                break;
            case 5:
                wpbdp_admin_message( _x( 'Could not delete theme directory. Check permissions.', 'themes', 'WPBDM' ), 'error' );
                break;
            default:
                break;
        }

        $themes = $this->api->get_installed_themes();
        $active_theme = $this->api->get_active_theme();

        // Make sure the current theme is always first.
        $current = $themes[ $active_theme ];
        unset( $themes[ $active_theme ] );
        array_unshift( $themes, $current );

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes.tpl.php',
                                array( 'themes' => $themes,
                                       'active_theme' => $active_theme ) );
    }

    function upload_theme() {
        if ( ! current_user_can( 'administrator' ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'upload theme zip' ) )
            wp_die();

        $theme_file = isset( $_FILES['themezip'] ) ? $_FILES['themezip'] : false;

        if ( ! $theme_file || ! is_uploaded_file( $theme_file['tmp_name'] ) || UPLOAD_ERR_OK != $_FILES['themezip']['error'] ) {
            wpbdp_admin_message( _x( 'Please upload a valid theme file.', 'themes', 'WPBDM' ), 'error' );
            return;
        }

        $dest = wp_normalize_path( untrailingslashit( get_temp_dir() ) . DIRECTORY_SEPARATOR . $theme_file['name'] );

        if ( ! move_uploaded_file( $theme_file['tmp_name'], $dest ) ) {
            wpbdp_admin_message( sprintf( _x( 'Could not move "%s" to a temporary directory.', 'themes', 'WPBDM' ),
                                          $theme_file['name'] ),
                                 'error' );
            return;
        }

        $res = $this->api->install_theme( $dest );

        if ( is_wp_error( $res ) ) {
            wpbdp_admin_message( $res->get_error_message(), 'error' );
            return;
        }

        wp_redirect( admin_url( 'admin.php?page=wpbdp-themes&message=3' ) );
        exit;
    }

    function theme_install() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-install.tpl.php',
                                array() );
    }

    function theme_delete_confirm() {
        $theme_id = $_REQUEST['theme_id'];
        $theme = $this->api->get_theme( $theme_id );

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-delete-confirm.tpl.php',
                                array( 'theme' => $theme ) );
    }

    function delete_theme() {
        if ( ! isset( $_POST['dodelete'] ) || 1 != $_POST['dodelete'] )
            return;

        // Cancel. Return to themes page.
        if ( empty( $_POST['delete-theme'] ) ) {
            wp_redirect( admin_url( 'admin.php?page=wpbdp-themes' ) );
            exit;
        }

        $theme_id = isset( $_POST['theme_id'] ) ? $_POST['theme_id'] : '';
        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';

        if ( ! current_user_can( 'administrator' ) || ! wp_verify_nonce( $nonce, 'delete theme ' . $theme_id ) )
            wp_die();

        $active_theme = $this->api->get_active_theme();
        $theme = $this->api->get_theme( $theme_id );

        if ( in_array( $theme_id, array( 'default', 'no_theme', $active_theme ), true ) || ! $theme )
            wp_die();

        $theme = $this->api->get_theme( $theme_id );
        $path = rtrim( $theme->path, '/\\' );
        $removed = false;

        if ( is_link( $path ) ) {
            $removed = unlink( $path );
        } elseif ( is_dir( $path ) ) {
            $removed = WPBDP_FS::rmdir( $path );
        }

        if ( $removed )
            wp_redirect( admin_url( 'admin.php?page=wpbdp-themes&message=4&deleted=' . $theme_id ) );
        else
            wp_redirect( admin_url( 'admin.php?page=wpbdp-themes&message=5&deleted=' . $theme_id ) );

        exit;
    }

    function ajax_activate_license() {
        if ( ! current_user_can( 'administrator' ) )
            die();

        $nonce = $_POST['nonce'];
        $theme = $_POST['theme'];
        $license = trim( $_POST['license'] );

        if ( ! wp_verify_nonce( $nonce, 'activate ' . $theme ) )
            die();

        // Try to activate license.
        $info = $this->api->get_theme( $theme );
        if ( ! $info )
            die();

        $edd_name = ! empty ( $info->edd_name ) ? $info->edd_name : $info->name;
        if ( ! $edd_name )
            die();

        // Try to activate theme.
        $error = false;
        $request_vars = array(
            'edd_action' => 'activate_license',
            'license' => $license,
            'item_name' => urlencode( $edd_name ),
            'url' => home_url()
        );
        $request = wp_remote_get( add_query_arg( $request_vars, 'http://businessdirectoryplugin.com/' ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $request ) )
            $error = _x( 'Could not contact licensing server', 'licensing', 'WPBDM' );

        if ( ! $error  ) {
            $request_result = json_decode( wp_remote_retrieve_body( $request ) );

            if ( ! is_object( $request_result ) || ! $request_result || ! isset( $request_result->license ) || 'valid' !== $request_result->license )
                $error = _x( 'License key is invalid', 'licensing', 'WPBDM' );
        }

        $response = new WPBDP_Ajax_Response();
        if ( $error )
            return $response->send_error( sprintf( _x( 'Could not activate license: %s.', 'licensing', 'WPBDM' ), $error ) );

        // Store license details.
        $theme_licenses = get_option( 'wpbdp-themes-licenses', array() );
        if ( ! is_array( $theme_licenses ) )
            $theme_licenses = array();

        $theme_licenses[ $theme ] = array( 'license' => $license,
                                           'status' => 'valid',
                                           'updated' => time() );

        update_option( 'wpbdp-themes-licenses', $theme_licenses );

        $response->set_message( _x( 'License activated', 'licensing', 'WPBDM' ) );
        $response->send();
    }

    function ajax_deactivate_license() {
        if ( ! current_user_can( 'administrator' ) )
            die();

        $nonce = $_POST['nonce'];
        $theme = $_POST['theme'];

        if ( ! wp_verify_nonce( $nonce, 'deactivate ' . $theme ) )
            die();

        // Try to activate license.
        $info = $this->api->get_theme( $theme );
        if ( ! $info )
            die();

        $edd_name = ! empty ( $info->edd_name ) ? $info->edd_name : $info->name;
        if ( ! $edd_name )
            die();

        if ( empty( $info->license_key ) )
            die();

        // Try to deactivate the key.
        $error = false;
        $request_vars = array(
            'edd_action' => 'deactivate_license',
            'license' => $info->license_key,
            'item_name' => urlencode( $edd_name ),
            'url' => home_url()
        );
        $request = wp_remote_get( add_query_arg( $request_vars, 'http://businessdirectoryplugin.com/' ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $request ) )
            $error = _x( 'Could not contact licensing server', 'licensing', 'WPBDM' );

        if ( ! $error  ) {
            $request_result = json_decode( wp_remote_retrieve_body( $request ) );

            if ( ! is_object( $request_result ) || ! $request_result || ! isset( $request_result->success ) || ! $request_result->success )
                $error = _x( 'Invalid response from server', 'licensing', 'WPBDM' );
        }

        $response = new WPBDP_Ajax_Response();
//        if ( $error )
//            return $response->send_error( sprintf( _x( 'Could not deactivate license: %s.', 'licensing', 'WPBDM' ), $error ) );

        // Store license details.
        $theme_licenses = get_option( 'wpbdp-themes-licenses', array() );
        if ( ! is_array( $theme_licenses ) )
            $theme_licenses = array();

        if ( isset( $theme_licenses[ $theme ] ) )
            unset( $theme_licenses[ $theme ] );

        update_option( 'wpbdp-themes-licenses', $theme_licenses );

        $response->set_message( _x( 'License deactivated', 'licensing', 'WPBDM' ) );
        $response->send();
    }

    function theme_licenses() {
        $themes = $this->api->get_installed_themes();

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/themes-licenses.tpl.php',
                                array( 'themes' => $themes ) );
    }

    function enter_license_key_row( $theme ) {
        if ( $theme->can_be_activated )
            return;

        echo '<div class="wpbdp-theme-license-required-row">';
        echo str_replace( '<a>', '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp-themes&v=licenses' ) ) .  '">', _x( 'Activate your <a>license key</a> to use this theme.', 'themes', 'WPBDM' ) );
        echo '</div>';
    }

}
