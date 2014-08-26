<?php
//set_site_transient( 'update_plugins', null );

/**
 * @since 3.4.2
 */
class WPBDP_Licensing {

    const STORE_URL = 'http://localhost:8080';

    private $modules = array();

    public function __construct() {
        add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );
        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
        add_action( 'wpbdp_register_settings', array( &$this, 'register_settings' ) );

        add_action( 'wp_ajax_wpbdp-activate-license', array( &$this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_wpbdp-deactivate-license', array( &$this, 'ajax_deactivate_license' ) );

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

    function _validate_license_setting( $setting, $new_value = '', $old_value = '' ) {
        $module = str_replace( 'license-key-', '', $setting->name );

        if ( $new_value !== $old_value )
            delete_option( 'wpbdp-license-status-' . $module );

        return $new_value;
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

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) )
            return new WP_Error( 'invalid-license', _x( 'License key is invalid', 'licensing', 'WPBDM' ) );

        if ( 'deactivated' !== $license_data->license )
            return new WP_Error( 'deactivation-failed', _x( 'Deactivation failed', 'licensing', 'WPBDM' ) );

        delete_option( 'wpbdp-license-status-' . $module );
        return true;
    }

    function register_module( $name = '', $module = '', $version = '' ) {
        $module = trim( $module );
        $name = trim( $name );
        $module_name = trim( str_replace( '.php', '', basename( $module ) ) );
        $version = trim( $version );

        if ( ! $module || !$module_name || ! $version )
            return false;

        $this->modules[ $module_name ] = array( 'license' => get_option( 'wpbdp-license-key-' . $module_name, '' ),
                                                'valid_license' => ( 'valid' == get_option( 'wpbdp-license-status-' . $module_name ) ? true : false ),
                                                'id' => $module_name,
                                                'file' => $module,
                                                'name' => $name ? $name : $module_name,
                                                'version' => $version );

        return $this->modules[ $module_name ]['valid_license'];
    }

    public function admin_init() {
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
        $invalid_licenses = array();

        foreach ( $this->modules as $module => $data ) {
            if ( $data['valid_license'] )
                continue;

            $invalid_licenses[] = array( 'id' => $data['id'], 'name' => $data['name'], 'version' => $data['version'] );
        }

        if ( ! $invalid_licenses )
            return;

        echo '<div class="error"><p>';
        echo '<b>' . _x( 'Business Directory - License Key Required', 'licensing', 'WPBDM' ) . '</b><br />';
        echo str_replace( '<a>',
                          '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=licenses' ) ) . '">',
                          _x( 'The following premium modules will not work until a valid license key is provided. Go to <a>Manage Options - Licenses</a> to enter your license information.',
                              'licensing',
                              'WPBDM' ) );
        echo '<br /><br />';

        foreach ( $invalid_licenses as $l )
            echo '&#149; ' . $l['name'] . ' ' . $l['version'] . '<br />';

        echo '</p></div>';
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
}

/**
 * @since 3.4.2
 */
function wpbdp_licensing_register_module( $name, $file_, $version ) {
    global $wpbdp;
    return $wpbdp->licensing->register_module( $name, $file_, $version );
}
