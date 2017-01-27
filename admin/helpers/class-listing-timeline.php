<?php
/**
 * @since next-release
 */

class WPBDP__Listing_Timeline {

    private $listing = null;


    public function __construct( $listing_id ) {
        $this->listing = wpbdp_get_listing( $listing_id );
    }

    public function render() {
        $items = wpbdp_get_logs( array( 'object_type' => 'listing', 'object_id' => $this->listing->get_id(), 'order' => 'DESC' ) );
        $timeline = array();

        foreach ( $items as $item ) {
            $obj = clone $item;
            $obj->html = '';
            $obj->timestamp = strtotime( $obj->created_at );
            $obj->extra = '';
            $obj->actions = array();

            $callback = 'process_' . str_replace( '.', '_', $obj->log_type );
            if ( method_exists( $this, $callback ) )
                $obj = call_user_func( array( $this, $callback ), $obj );

            if ( ! $obj->html )
                $obj->html = $obj->message ? $obj->message : $obj->log_type;

            $timeline[] = $obj;
        }

        return wpbdp_render_page( WPBDP_PATH . 'admin/templates/metaboxes-listing-timeline.tpl.php', array( 'timeline' => $timeline ) );
    }

    private function process_listing_created( $item ) {
        $item->html = _x( 'Listing created', 'listing timeline', 'WPBDM' );
        return $item;
    }

    private function process_listing_expired( $item ) {
        $item->html = _x( 'Listing expired', 'listing timeline', 'WPBDM' );
        return $item;
    }

    private function process_listing_renewal( $item ) {
        $item->html = _x( 'Listing renewed', 'listing timeline', 'WPBDM' );
        return $item;
    }

    private function process_listing_payment( $item ) {
        $payment = WPBDP_Payment::objects()->get( $item->rel_object_id );

        // switch ( $payment->payment_type ) {
        // case 'initial':
        //     $item->html .= 'Initial Payment';
        //     break;
        // default:
        //     $item->html .= 'Payment #' . $payment->id;
        //     break;
        // }

        $title = $payment->summary;

        if ( 'initial' == $payment->payment_type ) {
            if ( $payment->has_flag( 'admin-no-charge' ) )
                $title = 'Paid as admin';
            else
                $title = 'Initial Payment';
        }

        $item->html  = '';
        $item->html .= '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id ) ) . '">';
        $item->html .= $title;
        $item->html .= '</a>';

        if ( 'completed' != $payment->status )
            $item->html .= '<span class="payment-status tag ' . $payment->status . '">' . $payment->status . '</span>';

        $item->extra .= '<span class="payment-id">Payment #' . $payment->id . '</span>';
        $item->extra .= '<span class="payment-amount">Amount: ' . wpbdp_currency_format( $payment->amount, 'force_numeric=1' ) . '</span>';

        $item->actions = array(
            'details' => '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id ) ) . '">Go to payment</a>'
        );

        return $item;
    }

}
