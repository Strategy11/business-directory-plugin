<?php
/**
 * Fees/Payment API
 *
 * @package BDP/Includes/Views/Checkout
 */

// phpcs:disable

require_once( WPBDP_PATH . 'includes/class-payment.php' );
require_once( WPBDP_INC . 'class-payment-gateway.php' );
require_once( WPBDP_PATH . 'includes/class-fees-api.php' );


if ( ! class_exists( 'WPBDP_PaymentsAPI' ) ) {
    /**
     * Class WPBDP_PaymentsAPI
     *
     * @SuppressWarnings(PHPMD)
     */
class WPBDP_PaymentsAPI {

    public function __construct() {
        // Listing abandonment.
        add_filter( 'WPBDP_Listing::get_payment_status', array( &$this, 'abandonment_status' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_views', array( &$this, 'abandonment_admin_views' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_filter', array( &$this, 'abandonment_admin_filter' ), 10, 2 );

        add_action( 'wpbdp_checkout_form_top', array( $this, '_return_fee_list_button' ), -2, 1 );
        add_action( 'wpbdp_checkout_before_action', array( $this, 'maybe_fee_select_redirect' ) );
    }

    public function cancel_subscription( $listing, $subscription ) {
        $payment = $subscription->get_parent_payment();

        if ( ! $payment ) {
            $message = __( "We couldn't find a payment associated with the given subscription.", 'WPBDM' );
            throw new Exception( $message );
        }

        $gateway = $GLOBALS['wpbdp']->payment_gateways->get( $payment->gateway );

        if ( ! $gateway ) {
            $message = __( 'The payment gateway "<payment-gateway>" is not available.', 'WPBDM' );
            $message = str_replace( '<payment-gateway>', $gateway, $message );
            throw new Exception( $message );
        }

        $gateway->cancel_subscription( $listing, $subscription );
    }

    /**
     * @since 5.0
     *
     * @SuppressWarnings(PHPMD)
     */
    public function render_receipt( $payment ) {
        $current_user = wp_get_current_user();
        ob_start();
        do_action( 'wpbdp_before_render_receipt', $payment );
?>

<div class="wpbdp-payment-receipt">

    <div class="wpbdp-payment-receipt-header">
        <h4><?php printf( _x( 'Payment #%s', 'payments', 'WPBDM' ), $payment->id ); ?></h4>
        <span class="wpbdp-payment-receipt-date"><?php echo date( 'Y-m-d H:i', strtotime( $payment->created_at ) ); ?></span>

        <span class="wpbdp-tag wpbdp-payment-status wpbdp-payment-status-<?php echo $payment->status; ?>"><?php echo WPBDP_Payment::get_status_label( $payment->status ); ?></span>
    </div>
    <div class="wpbdp-payment-receipt-details">
        <dl>
            <?php if ( $payment->gateway ): ?>
            <dt><?php _ex( 'Gateway:', 'payments', 'WPBDM' ); ?></dt>
            <dd><?php echo $payment->gateway; ?></dd>
            <dt><?php _ex( 'Gateway Transaction ID:', 'payments', 'WPBDM' ); ?></dt>
            <dd><?php echo $payment->gateway_tx_id ? $payment->gateway_tx_id : '—'; ?></dd>
            <?php endif; ?>
            <dt><?php _ex( 'Bill To:', 'payments', 'WPBDM' ); ?></dt>
            <dd>
                <?php
                $bill_to  = '';

                $bill_to .= ( $payment->payer_first_name || $payment->payer_last_name ) ? $payment->payer_first_name . ' ' . $payment->payer_last_name : $current_user->display_name;
                $bill_to .= $payment->payer_data ? '<br />' . implode( '<br />', $payment->get_payer_address() ) : '';
                $bill_to .= '<br />';
                $bill_to .= $payment->payer_email ? $payment->payer_email : sprintf( '<%s>', $current_user->user_email );
                echo $bill_to;
                ?>
            </dd>
        </dl>
    </div>

    <?php echo $this->render_invoice( $payment ); ?>

    <input type="button" class="wpbdp-payment-receipt-print" value="<?php _ex( 'Print Receipt', 'checkout', 'WPBDM' ); ?>" />
</div>

<?php
        do_action( 'wpbdp_after_render_receipt', $payment );
        return ob_get_clean();
    }

    /**
     * Renders an invoice table for a given payment.
     * @param $payment WPBDP_Payment
     * @return string HTML output.
     * @since 3.4
     */
    public function render_invoice( &$payment ) {
        $html  = '';
        $html .= '<div class="wpbdp-checkout-invoice">';
        $html .= wpbdp_render( 'payment/payment_items', array( 'payment' => $payment ), false );
        $html .= '</div>';

        return $html;
    }

    /**
     * @since 3.5.8
     */
    public function abandonment_status( $status, $listing_id ) {
        // For now, we only consider abandonment if it involves listings with pending INITIAL payments.
        if ( 'pending' != $status || ! $listing_id || ! wpbdp_get_option( 'payment-abandonment' ) )
            return $status;

        $last_pending = WPBDP_Payment::objects()->filter( array( 'listing_id' => $listing_id, 'status' => 'pending' ) )->order_by( '-created_at' )->get();

        if ( ! $last_pending || 'initial' != $last_pending->payment_type ) {
            return $status;
        }

        $threshold = max( 1, absint( wpbdp_get_option( 'payment-abandonment-threshold' ) ) );
        $hours_elapsed = ( current_time( 'timestamp' ) - strtotime( $last_pending['created_at'] ) ) / ( 60 * 60 );

        if ( $hours_elapsed <= 0 )
            return $status;

        if ( $hours_elapsed >= ( 2 * $threshold ) ) {
            return 'payment-abandoned';
        } elseif ( $hours_elapsed >= $threshold ) {
            return 'pending-abandonment';
        }

        return $status;
    }

    /**
     * @since 3.5.8
     */
    public function abandonment_admin_views( $views, $post_statuses ) {
        global $wpdb;

        if ( ! wpbdp_get_option( 'payment-abandonment' ) )
            return $views;

        $threshold = max( 1, absint( wpbdp_get_option( 'payment-abandonment-threshold' ) ) );
        $now = current_time( 'timestamp' );

        $within_pending = wpbdp_format_time( strtotime( sprintf( '-%d hours', $threshold ), $now ), 'mysql' );
        $within_abandonment = wpbdp_format_time( strtotime( sprintf( '-%d hours', $threshold * 2 ), $now ), 'mysql' );

        $count_pending = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments ps LEFT JOIN {$wpdb->posts} p ON p.ID = ps.listing_id WHERE ps.created_at > %s AND ps.created_at <= %s AND ps.status = %s AND ps.payment_type = %s AND p.post_status IN ({$post_statuses})",
            $within_abandonment,
            $within_pending,
            'pending',
            'initial'
        ) );
        $count_abandoned = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments ps LEFT JOIN {$wpdb->posts} p ON p.ID = ps.listing_id WHERE ps.created_at <= %s AND ps.status = %s AND ps.payment_type = %s AND p.post_status IN ({$post_statuses})",
            $within_abandonment,
            'pending',
            'initial'
        ) );

        $views['pending-abandonment'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                                 esc_url( add_query_arg( 'wpbdmfilter', 'pending-abandonment', remove_query_arg( 'listing_status' ) ) ),
                                                 'pending-abandonment' == wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) ? 'current' : '',
                                                 _x( 'Pending Abandonment', 'admin', 'WPBDM' ),
                                                 number_format_i18n( $count_pending ) );
        $views['abandoned'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                        esc_url( add_query_arg( 'wpbdmfilter', 'abandoned', remove_query_arg( 'listing_status' ) ) ),
                                        'abandoned' == wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) ? 'current' : '',
                                        _x( 'Abandoned', 'admin', 'WPBDM' ),
                                        number_format_i18n( $count_abandoned ) );

        return $views;
    }

    /**
     * @since 3.5.8
     */
    public function abandonment_admin_filter( $pieces, $filter = '' ) {
        if ( ! wpbdp_get_option( 'payment-abandonment' ) ||
             ! in_array( $filter, array( 'abandoned', 'pending-abandonment' ), true ) )
            return $pieces;

        global $wpdb;

        // TODO: move this code elsewhere since it is used in several places.
        $threshold = max( 1, absint( wpbdp_get_option( 'payment-abandonment-threshold' ) ) );
        $now = current_time( 'timestamp' );

        $within_pending = wpbdp_format_time( strtotime( sprintf( '-%d hours', $threshold ), $now ), 'mysql' );
        $within_abandonment = wpbdp_format_time( strtotime( sprintf( '-%d hours', $threshold * 2 ), $now ), 'mysql' );

        $pieces['join'] .= " LEFT JOIN {$wpdb->prefix}wpbdp_payments ps ON {$wpdb->posts}.ID = ps.listing_id";
        $pieces['where'] .= $wpdb->prepare( ' AND ps.payment_type = %s AND ps.status = %s ', 'initial', 'pending' );

        switch ( $filter ) {
            case 'abandoned':
                $pieces['where'] .= $wpdb->prepare( ' AND ps.created_at <= %s ', $within_abandonment );
                break;

            case 'pending-abandonment':
                $pieces['where'] .= $wpdb->prepare( ' AND ps.created_at > %s AND ps.created_at <= %s ', $within_abandonment, $within_pending );
                break;
        }

        return $pieces;
    }

    /**
     * @since 3.5.8
     *
     * @SuppressWarnings(PHPMD)
     */
    public function notify_abandoned_payments() {
        global $wpdb;

        $threshold = max( 1, absint( wpbdp_get_option( 'payment-abandonment-threshold' ) ) );
        $time_for_pending = wpbdp_format_time( strtotime( "-{$threshold} hours", current_time( 'timestamp' ) ), 'mysql' );
        $notified = get_option( 'wpbdp-payment-abandonment-notified', array() );

        if ( ! is_array( $notified ) )
               $notified = array();

        // For now, we only notify listings with pending INITIAL payments.
        $to_notify = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s AND tag = %s AND created_at < %s ORDER BY created_at",
                            'pending',
                            'initial',
                            $time_for_pending )
        );

        foreach ( $to_notify as &$data ) {
            if ( in_array( $data->id, $notified ) )
                continue;

            $payment = WPBDP_Payment::get( $data->id );

            // Send e-mail.
            $replacements = array(
                'listing' => get_the_title( $payment->get_listing_id() ),
                'link' => sprintf( '<a href="%1$s">%1$s</a>', esc_url( $payment->get_checkout_url() ) )
            );

            $email = wpbdp_email_from_template( 'email-templates-payment-abandoned', $replacements );
            $email->to[] = wpbusdirman_get_the_business_email( $payment->get_listing_id() );
            $email->template = 'businessdirectory-email';
            $email->send();

            $notified[] = $data->id;
        }

        update_option( 'wpbdp-payment-abandonment-notified', $notified );
    }



    function _return_fee_list_button( $payment ){
        if ( 'renewal' !== $payment->payment_type ) {
            return;
        }

        echo '<input type="submit" name="return-to-fee-select" value="' . _x( 'Return to fee selection', 'templates', 'wpbdp-claim-listings' ) . '" style="margin-bottom:  1.5em;" />';
    }

    function maybe_fee_select_redirect( $checkout ) {
        if ( 'renewal' !== $checkout->payment->payment_type ) {
            return;
        }

        if ( empty( $_POST['return-to-fee-select'] ) ) {
            return;
        }


        $url = esc_url_raw(
            add_query_arg(
                array(
                    'return-to-fee-select' => 1,
                ),
                wpbdp_url( 'renew_listing', $checkout->payment->listing_id )
            )
        );

        wp_redirect( $url );
    }

}

}
