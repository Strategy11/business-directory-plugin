<?php
require_once( WPBDP_PATH . 'core/class-payment.php' );

/**
 * Payments admin page and AJAX actions.
 * @since 3.4
 */
class WPBDP_Admin_Payments {

    public function __construct() {
        add_action( 'wp_ajax_wpbdp-payment-details', array( &$this, 'ajax_payment_details' ) );
    }

    public function ajax_payment_details() {
        if ( ! current_user_can( 'administrator' ) )
            exit();

        global $wpbdp;

        $response = new WPBDP_AJAX_Response();

        $payment = WPBDP_Payment::get( intval( $_REQUEST['id'] ) );
        if ( ! $payment )
            $response->send_error();

        $response->add( 'html', wpbdp_render_page( WPBDP_PATH . 'admin/templates/payment-details.tpl.php',
                                                   array( 'payment' => $payment,
                                                          'invoice' => $wpbdp->payments->render_invoice( $payment ) ) ) );
        $response->send();
    }

}
