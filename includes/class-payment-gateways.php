<?php
/**
 * @since 5.0
 */
class WPBDP__Payment_Gateways {

    private $gateways = array();


    public function __construct() {
        add_action( 'wpbdp_modules_loaded', array( $this, 'load_gateways' ) );
        add_action( 'wpbdp_loaded', array( $this, '_execute_listener' ) );
        add_action( 'wpbdp_register_settings', array( $this, '_add_gateway_settings' ) );
        add_action( 'wpbdp_admin_notices', array( $this, '_admin_warnings' ) );
    }

    public function load_gateways() {
        $gateways = array();

        // Add Authorize.net by default.
        require_once( WPBDP_PATH . 'includes/gateways/class-gateway-authorize-net.php' );
        $gateways[] = new WPBDP__Gateway__Authorize_Net();

        // Allow modules to add gateways.
        $gateways = apply_filters( 'wpbdp_payment_gateways', $gateways );

        foreach ( $gateways as $gateway_ ) {
            $gateway = is_string( $gateway_ ) ? new $gateway_() : $gateway_;
            $this->gateways[ $gateway->get_id() ] = $gateway;
        }
    }

    public function _execute_listener() {
        $listener_id = ! empty( $_GET['wpbdp-listener'] ) ? $_GET['wpbdp-listener'] : '';

        if ( ! $listener_id )
            return;

        if ( ! $this->can_use( $listener_id ) )
            wp_die();

        $gateway = $this->get( $listener_id );
        $gateway->process_postback();
        exit;
    }

    public function get_available_gateways( $conditions = array() ) {
        $res = array();

        foreach ( $this->gateways as $gateway ) {
            if ( $gateway->is_enabled() ) {
                if ( $conditions ) {
                    if ( isset( $conditions['currency_code'] ) && ! $gateway->supports_currency( $conditions['currency_code'] ) )
                        continue;
                }

                $res[ $gateway->get_id() ] = $gateway;
            }
        }

        return $res;
    }

    public function can_use( $gateway_id ) {
        return isset( $this->gateways[ $gateway_id ] ) && $this->gateways[ $gateway_id ]->is_enabled();
    }

    public function get( $gateway_id ) {
        if ( isset( $this->gateways[ $gateway_id ] ) )
            return $this->gateways[ $gateway_id ];

        return false;
    }

    public function can_pay() {
        return count( $this->get_available_gateways() ) > 0;
    }

    public function _add_gateway_settings( $api ) {
        foreach ( $this->gateways as $gateway ) {
            wpbdp_register_settings_group( 'gateway_' . $gateway->get_id(), $gateway->get_title(), 'payment', array( 'desc' => $gateway->get_settings_text() ) );
            wpbdp_register_setting( array(
                'id' => $gateway->get_id(),
                'name' => sprintf( _x( 'Enable %s?', 'payment-gateways', 'WPBDM' ), $gateway->get_title() ),
                'type' => 'checkbox',
                'default' => false,
                'group'   => 'gateway_' . $gateway->get_id(),
                'requirements' => array( 'payments-on' )
            ) );
            foreach ( $gateway->get_settings() as $setting ) {
                $setting = array_merge( $setting, array( 'group' => 'gateway_' . $gateway->get_id() ) );
                $setting['id'] = $gateway->get_id() . '-' . $setting['id'];
                $setting['requirements'] = array( $gateway->get_id() );

                wpbdp_register_setting( $setting );
            }
        }
    }

    // TODO: Maybe integrate all of these warnings into just one message?
    public function _admin_warnings() {
        if ( empty( $_GET['page'] ) || 'wpbdp_settings' != $_GET['page'] )
            return;

        if ( ! wpbdp_get_option( 'payments-on' ) )
            return;

        $at_least_one_gateway = false;
        foreach ( $this->gateways as $gateway ) {
            if ( $gateway->is_enabled( true ) ) {
                $at_least_one_gateway = true;
            } elseif ( $gateway->is_enabled( false ) ) {
                $errors = rtrim( '&#149; ' . implode( ' &#149; ', $gateway->validate_settings() ), '.' );

                $msg  = _x( 'The <gateway> gateway is enabled but not properly configured. The gateway won\'t be available until the following problems are fixed: <problems>.', 'payment-gateways', 'WPBDM' );
                $msg .= '<br />';
                $msg .= _x( 'Please check the <link>payment settings</link>.', 'payment-gateways', 'WPBDM' );

                $msg = str_replace( '<gateway>', '<b>' . $gateway->get_title() .'</b>', $msg );
                $msg = str_replace( '<problems>', '<b>' . $errors . '</b>', $msg );
                $msg = str_replace( array( '<link>', '</link>' ), array( '<a href="' . admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) . '">', '</a>' ), $msg );

                wpbdp_admin_message( $msg, 'error' );
            }
        }

        if ( ! $at_least_one_gateway ) {
            $msg = _x( 'You have payments turned on but no gateway is active and properly configured. Go to <link>Manage Options - Payment</link> to change the payment settings. Until you change this, the directory will operate in <i>Free Mode</i>.', 'payment-gateways', 'WPBDM' );
            $msg = str_replace( array( '<link>', '</link>' ), array( '<a href="' . admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) . '">', '</a>' ), $msg );
            wpbdp_admin_message( $msg, 'error' );
        }
    }

}
