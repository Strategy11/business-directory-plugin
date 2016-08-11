<?php
//set_site_transient( 'update_plugins', null );


/**
 * @since 3.4.2
 */
class WPBDP_Licensing {

    const STORE_URL = 'http://businessdirectoryplugin.com/';
    //const STORE_URL = 'http://192.168.13.37/';

    private $modules = array();

    public function __construct() {
        add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );
        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
        add_action( 'wpbdp_register_settings', array( &$this, 'register_settings' ) );
        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );

        add_action( 'wp_ajax_wpbdp-activate-license', array( &$this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_wpbdp-deactivate-license', array( &$this, 'ajax_deactivate_license' ) );
        add_action( 'wp_ajax_wpbdp-license-expired-warning-dismiss', array( &$this, 'ajax_dismiss_license_warning' ) );

        add_action( 'wpbdp_license_check', array( &$this, 'license_check' ) );

        add_filter( 'wpbdp_settings_group_tab_css', array( &$this, 'licenses_tab_css' ), 10, 2 );

        if ( ! wp_next_scheduled( 'wpbdp_license_check' ) ) {
            wp_schedule_event( time(), 'daily', 'wpbdp_license_check' );
        }

        if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
            require_once ( WPBDP_PATH . 'vendors/edd/EDD_SL_Plugin_Updater.php' );
        }
    }

    function register_settings( &$settings ) {
        if ( ! $this->modules )
            return;

        $g = $settings->add_group( 'licenses',
                                   _x( 'Licenses', 'settings', 'WPBDM' ) );
        $s = $settings->add_section( $g,
                                     'licenses/keys',
                                     _x( 'Premium Modules', 'settings', 'WPBDM' ) );

        foreach ( $this->modules as $id => $data ) {
            $settings->add_setting( $s,
                                    'license-key-' . $id,
                                    $data['name'],
                                    'license_key',
                                    '',
                                    '',
                                    null,
                                    array( &$this, '_validate_license_setting' ) );
        }
    }

    function admin_menu( $menu ) {
        if ( ! current_user_can( 'administrator' ) || ! $this->modules  )
            return;

        add_submenu_page( 'wpbdp_admin',
                          _x( 'Licenses', 'settings', 'WPBDM' ),
                          _x( 'Licenses', 'settings', 'WPBDM' ),
                          'administrator',
                          'wpbdp-licenses',
                          '__return_false' );
        global $submenu;

        foreach ( $submenu as $menu_id => &$m ) {
            if ( $menu == $menu_id  ) {
                foreach ( $m as &$i ) {
                    if ( 'wpbdp-licenses' == $i[2] ) {
                        $i[2] = admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=licenses' );
                        break;
                    }
                }

                break;
            }
        }
    }

    function _validate_license_setting( $setting, $new_value = '', $old_value = '' ) {
        $module = str_replace( 'license-key-', '', $setting->name );

        if ( $new_value !== $old_value )
            delete_option( 'wpbdp-license-status-' . $module );

        return $new_value;
    }

    function licenses_tab_css( $css = '', $group ) {
        if ( 'licenses' !== $group->slug )
            return $css;

        foreach ( $this->modules as $module => $data ) {
            if ( 'valid' != $data['license_status'] )
                return $css . ' group-error';
        }
    }

    function activate_license( $module ) {
        if ( ! in_array( $module, array_keys( $this->modules ), true ) )
            return new WP_Error( 'invalid-module', _x( 'Invalid module ID', 'licensing', 'WPBDM' ), $module );

        $key = trim( get_option( 'wpbdp-license-key-' . $module, '' ) );

        if ( ! $key )
            return new WP_Error( 'no-license-provided', _x( 'No license key provided', 'licensing', 'WPBDM' ) );

        $module_data = $this->modules[ $module ];

        $request = array(
            'edd_action' => 'activate_license',
            'license' => $key,
            'item_name' => urlencode( $module_data['name'] ),
            'url' => home_url()
        );

        // Call the licensing server.
        $response = wp_remote_get( add_query_arg( $request, self::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $response ) )
            return new WP_Error( 'request-failed', _x( 'Could not contact licensing server', 'licensing', 'WPBDM' ) );

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) || 'valid' !== $license_data->license )
            return new WP_Error( 'invalid-license', _x( 'License key is invalid', 'licensing', 'WPBDM' ) );

        update_option( 'wpbdp-license-status-' . $module, $license_data->license );

        return array( 'activations_left' => $license_data->activations_left, 'expires' => $license_data->expires );
    }

    function deactivate_license( $module ) {
        if ( ! in_array( $module, array_keys( $this->modules ), true ) )
            return new WP_Error( 'invalid-module', _x( 'Invalid module ID', 'licensing', 'WPBDM' ), $module );

        delete_option( 'wpbdp-license-status-' . $module );

        $key = trim( get_option( 'wpbdp-license-key-' . $module, '' ) );
        $module_data = $this->modules[ $module ];

        $request = array(
            'edd_action' => 'deactivate_license',
            'license' => $key,
            'item_name' => urlencode( $module_data['name'] ),
            'url' => home_url()
        );

        // Call the licensing server.
        $response = wp_remote_get( add_query_arg( $request, self::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $response ) )
            return new WP_Error( 'request-failed', _x( 'Could not contact licensing server', 'licensing', 'WPBDM' ) );

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        delete_option( 'wpbdp-license-status-' . $module );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) )
            return new WP_Error( 'invalid-license', _x( 'License key is invalid', 'licensing', 'WPBDM' ) );

        if ( 'deactivated' !== $license_data->license )
            return new WP_Error( 'deactivation-failed', _x( 'Deactivation failed', 'licensing', 'WPBDM' ) );

        return true;
    }

    function sort_modules_by_name( $x, $y ) {
        return strncasecmp( $x['name'], $y['name'], 4 );
    }

    function register_module( $name = '', $module = '', $version = '' ) {
        $module = trim( $module );
        $name = trim( $name );
        $module_name = trim( str_replace( '.php', '', basename( $module ) ) );
        $version = trim( $version );

        if ( ! $module || !$module_name || ! $version )
            return false;

        $this->modules[ $module_name ] = array( 'license' => get_option( 'wpbdp-license-key-' . $module_name, '' ),
                                                'license_status' => get_option( 'wpbdp-license-status-' . $module_name, 'invalid' ),
                                                'id' => $module_name,
                                                'file' => $module,
                                                'name' => $name ? $name : $module_name,
                                                'version' => $version );

        if ( ! $this->modules[ $module_name ]['license'] )
            $this->modules[ $module_name ]['license_status'] = 'invalid';

        // Keep modules sorted by name.
        uasort( $this->modules, array( &$this, 'sort_modules_by_name' ) );
        return in_array( $this->modules[ $module_name ]['license_status'], array( 'valid', 'expired' ), true );
    }

    public function admin_init() {
        //delete_transient( 'wpbdp-license-check-data' ); do_action( 'wpbdp_license_check' );
        foreach ( $this->modules as $module => $data ) {
            new EDD_SL_Plugin_Updater( self::STORE_URL,
                                       $data['file'],
                                       array( 'version' => $data['version'],
                                              'license' => $data['license'],
                                              'item_name' => $data['name'],
                                              'author' => 'D. Rodenbaugh' ) );
        }
    }

    public function admin_notices() {
        $invalid = array();
        $expired = array();

        foreach ( $this->modules as $module => $data ) {
            switch ( $data['license_status'] ) {
                case 'valid':
                    break;
                case 'expired':
                    $expired[] = $data;
                    break;
                default:
                    $invalid[] = $data;
                    break;
            }
        }

        if ( $invalid ) {
            echo '<div class="error"><p>';
            echo '<b>' . _x( 'Business Directory - License Key Required', 'licensing', 'WPBDM' ) . '</b><br />';
            echo str_replace( '<a>',
                              '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=licenses' ) ) . '">',
                              _x( 'The following premium modules will not work until a valid license key is provided. Go to <a>Manage Options - Licenses</a> to enter your license information.',
                                  'licensing',
                                  'WPBDM' ) );
            echo '<br /><br />';

            foreach ( $invalid as $d )
                echo '&#149; ' . $d['name'] . ' ' . $d['version'] . '<br />';

            echo '</p></div>';
        }

        // Expired licenses.
        if ( $expired ) {
            $check_data = get_transient( 'wpbdp-license-check-data' );

            foreach ( $expired as $d ) {
                if ( $check_data && is_array( $check_data['warning-dismissed'] ) && in_array( $d['id'], $check_data['warning-dismissed'], true ) )
                    continue;

                echo '<div class="error wpbdp-license-expired-warning">';
                echo '<p>';
                echo '<b>'. _x( 'Business Directory - License Key Expired', 'licensing', 'WPBDM' ) . '</b><br />';
                printf( _x( 'The license key for <span class="module-name">%s %s</span> has expired. The module will continue to work but you will not receive any more updates until the license is renewed.',
                            'licensing',
                            'WPBDM' ), $d['name'], $d['version'] );
                echo '<br /><br />';
                echo '<a href="#" class="dismiss button" data-module="' . esc_attr( $d['id'] ) . '" data-nonce="' . wp_create_nonce( 'dismiss warning' ) . '">' . _x( 'Remind me later', 'licensing', 'WPBDM' ) . '</a> ';
                $url = add_query_arg( array( 'item_name' => urlencode( $d['name'] ), 'edd_license_key' => urlencode( $d['license'] ) ), 'http://businessdirectoryplugin.com/checkout/' );
                echo '<a href="' . esc_url( $url ) . '" target="_blank" class="button-primary">' . _x( 'Renew License Key', 'licensing', 'WPBDM' ) . '</a>';
                echo '</p></div>';
            }
        }
    }

    function license_check() {
        if ( ! $this->modules )
            return;

        wpbdp_log( 'Performing (scheduled) license check.' );
        $data = get_transient( 'wpbdp-license-check-data' );

        if ( ! $data ) {
            $data = array( 'date' => current_time('mysql'), 'warning-dismissed' => false );

            foreach ( $this->modules as $module ) {
                if ( null == ( $status = $this->check_module_license( $module['id'] ) ) )
                    continue;

                    if ( ! isset( $data[ $status ] ) )
                        $data[ $status ] = array();

                    $data[ $status ][ $module['id'] ] = $module['license'];
                    update_option( 'wpbdp-license-status-' . $module['id'], $status );
            }

            set_transient( 'wpbdp-license-check-data', $data, 1 * WEEK_IN_SECONDS );
        }
    }

    function check_module_license( $module ) {
        $data = isset( $this->modules[ $module ] ) ? $this->modules[ $module ] : null;

        if ( ! $data || ! isset( $data['license'] ) || ! $data['license'] )
            return null;

        $request = array( 'edd_action' => 'check_license',
                          'license' => $data['license'],
                          'item_name' => urlencode( $data['name'] ),
                          'url' => home_url() );
        $response = wp_remote_get( add_query_arg( $request, self::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

        if ( is_wp_error( $response ) )
            return null;

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) )
            return null;

        return $license_data->license;
        //return ( 'valid' == $license_data->license ? true : false );
    }

    function ajax_activate_license() {
        $module = isset( $_POST['module'] ) ? trim( $_POST['module'] ) : '';
        $nonce = isset( $_POST['nonce'] ) ? trim( $_POST['nonce'] ) : '';
        $key = isset( $_POST['key'] ) ? trim( $_POST['key'] ) : '';

        if ( ! $module || ! $nonce || ! wp_verify_nonce( $nonce, 'license activation' ) )
            die();

        update_option( 'wpbdp-license-key-' . $module, $key );
        $result = $this->activate_license( $module, $key );

        $response = new WPBDP_Ajax_Response();

        if ( is_wp_error( $result ) )
            $response->send_error( sprintf( _x( 'Could not activate license: %s.', 'licensing', 'WPBDM' ), $result->get_error_message() ) );

        $response->set_message( _x( 'License activated', 'licensing', 'WPBDM' ) );
        $response->send();
    }

    function ajax_deactivate_license() {
        $module = isset( $_POST['module'] ) ? trim( $_POST['module'] ) : '';
        $nonce = isset( $_POST['nonce'] ) ? trim( $_POST['nonce'] ) : '';

        if ( ! $module || ! $nonce || ! wp_verify_nonce( $nonce, 'license activation' ) )
            die();

        $result = $this->deactivate_license( $module );

        $response = new WPBDP_Ajax_Response();

        if ( is_wp_error( $result ) )
            $response->send_error( sprintf( _x( 'Could not deactivate license: %s.', 'licensing', 'WPBDM' ), $result->get_error_message() ) );

        $response->set_message( _x( 'License deactivated', 'licensing', 'WPBDM' ) );
        $response->send();
    }

    function ajax_dismiss_license_warning() {
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $module = isset( $_POST['module'] ) ? $_POST['module'] : '';

        $res = new WPBDP_Ajax_Response();

        if ( ! wp_verify_nonce( $nonce, 'dismiss warning' ) )
            $res->send_error();

        $data = get_transient( 'wpbdp-license-check-data' );

        if ( ! is_array( $data['warning-dismissed'] ) )
            $data['warning-dismissed'] = array();

        if ( ! in_array( $module, $data['warning-dismissed'], true ) )
            $data['warning-dismissed'][] = $module;

        set_transient( 'wpbdp-license-check-data', $data, 1 * WEEK_IN_SECONDS );

        $res->send();
    }

}

/**
 * @since 3.4.2
 */
function wpbdp_licensing_register_module( $name, $file_, $version ) {
    global $wpbdp;
    return $wpbdp->licensing->register_module( $name, $file_, $version );
}
