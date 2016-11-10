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
        $payment = WPBDP_Payment::objects()->get( $_GET['payment-id'] );
        return compact( 'payment' );
    }

    function ajax_add_note() {
        $payment_id = absint( $_POST['payment_id'] );
        $payment = WPBDP_Payment::objects()->get( $payment_id );
        $text = trim( $_POST['note'] );

        $res = new WPBDP_Ajax_Response();

        if ( ! $payment || ! $text )
            $res->send_error();

        $note = $payment->add_note( $text, get_current_user_id() );
        if ( ! $note )
            $res->send_error();

        $res->add( 'note', $note );
        $res->add( 'html', wpbdp_render_page( WPBDP_PATH . 'admin/templates/payments-note.tpl.php', compact( 'note', 'payment_id' ) ) );
        $res->send();
    }

    function ajax_delete_note() {
        $payment_id = absint( $_GET['payment_id'] );
        $note_key = trim( $_GET['note'] );
        $payment = WPBDP_Payment::objects()->get( $payment_id );

        $res = new WPBDP_Ajax_Response();

        if ( ! $payment || ! isset( $payment->payment_notes[ $note_key ] ) )
            $res->send_error();

        $note = $payment->payment_notes[ $note_key ];
        $payment->delete_note( $note );

        $res->add( 'note', $note );
        $res->send();
    }

}

