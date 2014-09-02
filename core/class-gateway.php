<?php

abstract class WPBDP_Payment_Gateway {

    const INTEGRATION_BUTTON = 'button';
    const CAPABILITIES_RECURRING = 'recurring';

    public function __construct() {
    }

    public function get_id() {
        return '';
    }

    public function get_gateway_url( $args = array() ) {
        return add_query_arg( array_merge( array( 'wpbdpx' => 'payments',
                                                  'action' => 'postback',
                                                  'gid' => $this->get_id() ),
                                           $args ),
                              home_url('/') );
    }

    public function get_url( &$payment, $action = '' ) {
        // TODO: support pretty URLs
        return add_query_arg( array( 'wpbdpx' => 'payments',
                                     'action' => $action,
                                     'payment_id' => $payment->get_id() ),
                              home_url( '/' ) );
    }

    public function get_name() {
        $classname = get_class( $this );
        $classname = str_replace( 'WPBDP' , '', $classname );
        $classname = str_replace( '_' , ' ', $classname );
        $classname = trim( $classname );

        return $classname;
    }

    public function register_config( &$settings ) { }
    abstract public function validate_config();

    public function get_supported_currencies() {
        return array();
    }

    abstract public function get_integration_method();

    public function get_capabilities() {
        return array();
    }

    public function render_unsubscribe_integration( &$category, &$listing) {}

    public function setup_payment( &$payment ) {}

    public function process_generic( $action = '' ) {
        return;
    }

    abstract public function process( &$payment, $action );
    abstract public function render_integration( &$payment );


}
