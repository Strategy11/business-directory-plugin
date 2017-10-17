<?php
require_once( WPBDP_PATH . 'includes/class-payment.php' );

/**
 * @since 5.0
 */
class WPBDP__Admin__Payments extends WPBDP__Admin__Controller {

    function _enqueue_scripts() {
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wpbdp_enqueue_jquery_ui_style();
        parent::_enqueue_scripts();
    }

    function index() {
        $_SERVER['REQUEST_URI'] = remove_query_arg( 'listing' );

        if ( ! empty( $_GET['message'] ) && 'payment_delete' == $_GET['message'] )
            wpbdp_admin_message( _x( 'Payment deleted.', 'payments admin', 'WPBDM' ) );


        require_once( WPBDP_INC . 'admin/helpers/class-payments-table.php' );

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
        if ( ! empty( $_GET['message'] ) && 1 == $_GET['message'] )
            wpbdp_admin_message( _x( 'Payment details updated.', 'payments admin', 'WPBDM' ) );

        $payment = WPBDP_Payment::objects()->get( $_GET['payment-id'] );
        return compact( 'payment' );
    }

    function payment_update() {
        $data = $_POST['payment'];
        $payment = WPBDP_Payment::objects()->get( $data['id'] );
        $payment->update( $data );
        $payment->save();

        wp_redirect( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id . '&message=1' ) );
        exit;
    }

    function payment_delete() {
        $payment = WPBDP_Payment::objects()->get( (int) $_REQUEST['payment-id'] );
        $payment->delete();

        wp_redirect( admin_url( 'admin.php?page=wpbdp_admin_payments&message=payment_delete' ) );
        exit;
    }

    function ajax_add_note() {
        $payment_id = absint( $_POST['payment_id'] );
        $payment = WPBDP_Payment::objects()->get( $payment_id );
        $text = trim( $_POST['note'] );

        $res = new WPBDP_Ajax_Response();

        if ( ! $payment || ! $text )
            $res->send_error();

        $note = wpbdp_insert_log( array( 'log_type' => 'payment.note', 'message' => $text, 'actor' => 'user:' . get_current_user_id(), 'object_id' => $payment_id ) );
        if ( ! $note )
            $res->send_error();

        $res->add( 'note', $note );
        $res->add( 'html', wpbdp_render_page( WPBDP_PATH . 'templates/admin/payments-note.tpl.php', compact( 'note', 'payment_id' ) ) );
        $res->send();
    }

    function ajax_delete_note() {
        $payment_id = absint( $_GET['payment_id'] );
        $note_key = trim( $_GET['note'] );

        $res = new WPBDP_Ajax_Response();

        $note = wpbdp_get_log( $note_key );
        if ( 'payment.note' != $note->log_type || $payment_id != $note->object_id )
            $res->send_error();

        wpbdp_delete_log( $note_key );

        $res->add( 'note', $note );
        $res->send();
    }

}

