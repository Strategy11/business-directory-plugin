<?php

class WPBDP__WPBDPX_Payments_Compat {

    private $gateways;


    public function __construct() {
        $this->gateways = wpbdp()->payment_gateways;
    }

    public function dispatch() {
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';
        $payment = isset( $_GET['payment_id'] ) ? WPBDP_Payment::get( intval( $_GET['payment_id'] ) ) : null;
        $gid = isset( $_GET['gid'] ) ? trim( $_GET['gid'] ) : '';

        if ( ! in_array( $action, array( 'postback', 'process', 'notify', 'return', 'cancel', 'ins' ) ) || ( ! $payment && ! $gid ) )
            return;

        unset( $_GET['action'] );

        if ( $gid )
            unset( $_GET['gid'] );

        $gateway_id = $payment ? $payment->gateway : $gid;
        $gateway = $this->gateways->get( $gateway_id );

        if ( ! $gateway )
            return;

        switch ( $gateway ) {
        case '2checkout':
            $_POST['wpbdp_payment_id']    = $_REQUEST['merchant_order_id'];
            $_GET['wpbdp_payment_id']     = $_REQUEST['merchant_order_id'];
            $_REQUEST['wpbdp_payment_id'] = $_REQUEST['merchant_order_id'];
            break;
        case 'paypal':
            break;
        case 'stripe':
            break;
        }

        $gateway->process_postback();
        exit;
    }

}
    //     // if ( ! $payment )
    //     //     $this->gateways[ $gateway_id ]->process_generic( $action );
    //     // else
    //     //     $this->gateways[ $gateway_id ]->process( $payment, $action );
    // }
