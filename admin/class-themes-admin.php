<?php
/**
 * @since themes-release
 */
class WPBDP_Themes_Admin {

    private $api;

    function __construct( &$api ) {
        $this->api = $api;

        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );
        add_filter( 'wpbdp_admin_menu_reorder', array( &$this, 'admin_menu_move_themes_up' ) );

        add_action( 'wpbdp_admin_notices', array( &$this, 'theme_fields_check' ) );

        add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

        add_action( 'wpbdp_action_set-active-theme', array( &$this, 'set_active_theme' ) );
        add_action( 'wpbdp_action_delete-theme', array( &$this, 'delete_theme' ) );
        add_action( 'wpbdp_action_upload-theme', array( &$this, 'upload_theme' ) );
        add_action( 'wpbdp_action_create-theme-suggested-fields', array( &$this, 'create_suggested_fields' ) );
    }

    function admin_menu( $slug ) {
        add_submenu_page( $slug,
                          _x( 'Directory Themes', 'themes', 'WPBDM' ),
                          _x( 'Directory Themes', 'themes', 'WPBDM' ),
                          'administrator',
                          'wpbdp-themes',
                          array( &$this, 'dispatch' ) );
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

    function theme_fields_check() {
        if ( ! isset( $_GET['theme-activated'] ) || 1 != $_GET['theme-activated'] )
            return;

        $theme_fields = $this->api->get_active_theme_data( 'suggested_fields' );
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

        wp_safe_redirect( admin_url( 'admin.php?page=wpbdp-themes&message=2' ) );
        exit;
    }

    function dispatch() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : '';

        switch ( $action ) {
            case 'theme-install':
                return $this->theme_install();
                break;
            case 'delete-theme':
                return $this->theme_delete_confirm();
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
                    $msg  = sprintf( _x( 'For better results, "%s" theme suggests the following form fields to be created.', 'themes', 'WPBDM' ), $this->api->get_active_theme() );
                    $msg .= '<br />';

                    foreach ( $missing_fields as $mf )
                        $msg .= '<span class="tag">' . $mf . '</span>';

                    $msg .= '<br /><br />';
                    $msg .= sprintf( '<a href="#" class="button button-secondary" id="dismiss-suggested-fields-warning">%s</a>', _x( 'Dismiss this warning', 'themes', 'WPBDM' ) );
                    $msg .= sprintf( '<a href="%s" class="button button-primary next-to-secondary">%s</a>',
                                     wp_nonce_url( admin_url( 'admin.php?page=wpbdp-themes&wpbdp-action=create-theme-suggested-fields' ), 'create_suggested_fields' ),
                                     _x( 'Create suggested fields', 'themes', 'WPBDM' ) );

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

}
