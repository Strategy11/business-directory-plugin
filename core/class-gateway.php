<?php

abstract class WPBDP_Payment_Gateway {

    public function __construct() {
    }

    public function get_url( &$payment, $action = '' ) {
        // TODO: support pretty URLs

        if ( $action == 'notify' )
            return 'http://bdtest.wpengine.com/wp-content/plugins/business-directory-plugin/ipn.php';
        
        return add_query_arg( array( 'wpbdpx' => 'payments_process',
                                     'action' => $action,
                                     'payment_id' => $payment->get_id() ),
                              home_url( 'index.php' ) );
    }

    abstract public function process( &$payment, $action );
    abstract public function render_integration( &$payment );


}