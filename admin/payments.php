<?php
require_once( WPBDP_PATH . 'core/class-payment.php' );

/**
 * @since next-release
 */
class WPBDP__Admin__Payments extends WPBDP__Admin__Controller {

    function index() {
        $_SERVER['REQUEST_URI'] = remove_query_arg( 'listing' );

        require_once( WPBDP_PATH . 'admin/helpers/class-payments-table.php' );

        $table = new WPBDP__Admin__Payments_Table();
        $table->prepare_items();

        if ( ! empty( $_GET['listing'] ) ) {
            $listing = WPBDP_Listing::get( $_GET['listing'] );

            if ( $listing )
                wpbdp_admin_message(
                    str_replace( '<a>',
                                 '<a href="' . remove_query_arg( 'listing' ) . '">',
                                 sprintf( _x( 'You\'re seeing payments related to listing: "%s" (ID #%d). <a>Click here</a> to see all payments.', 'payments admin', 'WPBDM' ),
                                          esc_html( $listing->get_title() ),
                                          $listing->get_id() ) )
                    );
        }

        return compact( 'table' );
    }

    function details() {
        $payment = WPBDP_Payment::get( $_GET['payment-id'] ) or die();
        return compact( 'payment' );
    }

}

