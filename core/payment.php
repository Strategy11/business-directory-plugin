<?php
require_once( WPBDP_PATH . 'core/class-payment.php' );

require_once( WPBDP_PATH . 'core/class-fees-api.php' );

/*
 * Fees/Payment API
 */

if ( ! class_exists( 'WPBDP_PaymentsAPI' ) ) {

class WPBDP_PaymentsAPI {

    public function __construct() {
        $this->gateways = array();

        // Listing abandonment.
        add_filter( 'WPBDP_Listing::get_payment_status', array( &$this, 'abandonment_status' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_views', array( &$this, 'abandonment_admin_views' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_filter', array( &$this, 'abandonment_admin_filter' ), 10, 2 );

        //add_action( 'WPBDP_Payment::status_change', array( &$this, 'payment_notification' ) );
//        add_action( 'WPBDP_Payment::before_save', array( &$this, 'gateway_payment_save' ) );
    }

    public function get_available_methods( $capabilities = array() ) {
        $ok_gateways = array();

        if ( ! wpbdp_get_option( 'payments-on' ) )
            return array();

        foreach ( $this->gateways as $gateway_id => &$gateway ) {
            if ( wpbdp_get_option( $gateway_id ) ) {
                if ( 0 === count( $gateway->validate_config() ) ) {
                    if ( $capabilities ) {
                        $has_caps = true;

                        foreach ( $capabilities as $cap ) {
                            if ( ! in_array( $cap, $gateway->get_capabilities(), true ) ) {
                                $has_caps = false;
                                break;
                            }
                        }

                        if ( $has_caps )
                            $ok_gateways[] = $gateway_id;
                    } else {
                        $ok_gateways[] = $gateway_id;
                    }
                }
            }
        }

        return $ok_gateways;
    }

    public function payments_possible() {
        return count( $this->get_available_methods() ) > 0;
    }

    public function get_registered_methods() {
        return $this->gateways;
    }

    /**
     * @since 3.5.3
     */
    public function is_available($gateway) {
        return in_array( $gateway, $this->get_available_methods(), true );
    }

    public function has_gateway($gateway) {
        return array_key_exists($gateway, $this->gateways);
    }

    public function check_capability( $cap ) {
        foreach ( $this->get_available_methods() as $gateway_id ) {
            if ( in_array( $cap, $this->gateways[ $gateway_id ]->get_capabilities(), true ) )
                return true;
        }

        return false;
    }

    public function render_payment_page($options_) {
        $options = array_merge(array(
            'title' => _x('Checkout', 'payments-api', 'WPBDM'),
            'item_text' => _x('Pay %1$s through %2$s', 'payments-api', 'WPBDM'),
            'return_link' => null
        ), $options_);

        $transaction = $this->get_transaction($options['transaction_id']);

        if ( $transaction->status == 'approved' || $transaction->amount == 0.0 ) {
            return wpbdp_render_msg( _x('Your transaction has been approved.', 'payments-api', 'WPBDM' ) );
        }

        return wpbdp_render('payment-page', array(
            'title' => $options['title'],
            'item_text' => $options['item_text'],
            'transaction' => $transaction,
            'payment_methods' => $this->get_available_methods(),
            'return_link' => $options['return_link']
            ));
    }

    public function get_transaction_from_uri_id() {
        if (!isset($_GET['tid']))
            return null;

        $uri_id_plain = explode('.', urldecode(base64_decode($_GET['tid'])));
        $transaction_id = $uri_id_plain[0];
        $transaction_date = $uri_id_plain[1];

        // check transaction date is valid
        if ($transaction = $this->get_transaction($transaction_id)) {
            if (strtotime($transaction->created_on) == $transaction_date)
                return $transaction;
        }

        return null;
    }

    /**
     * @deprecated since 3.4
     */
    public function get_processing_url($gateway, $transaction=null) {
        throw new Exception( sprintf( 'get_processing_url() is deprecated. Please upgrade your "%s" gateway.', $gateway ) );
    }

    public function in_test_mode() {
        return wpbdp_get_option('payments-test-mode');
    }

    public function get_transaction($transaction_id) {
        global $wpdb;

        if ($trans = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE id = %d", $transaction_id))) {
            if ($trans->payerinfo) {
                $trans->payerinfo = unserialize($trans->payerinfo);
            } else {
                $trans->payerinfo = array('name' => '',
                                          'email' => '');
            }

            if ($trans->extra_data) {
                $trans->extra_data = unserialize($trans->extra_data);
            } else {
                $trans->extra_data = array();
            }

            return $trans;
        }

        return null;
    }

    /**
     * @since 3.5.8
     */
    public function process_recurring_expiration( $payment_id = 0 ) {
        $payment = WPBDP_Payment::get( $payment_id );

        if ( ! $payment || ! $payment->is_completed() )
            return;

        $gateway = $payment->gateway;
        if ( ! $this->is_available( $gateway ) )
            return;

        $gateway = $this->gateways[ $gateway ];

        if ( ! $gateway->has_capability( 'handles-expiration' ) )
            return;

        $gateway->handle_expiration( $payment );
    }

    public function render_unsubscribe_integration( &$category, &$listing ) {
        global $wpdb;

        if ( ! $category || ! $listing )
            return;

        $payment = WPBDP_Payment::get( $category->payment_id );

        if ( ! $payment )
            return '';

        $gateway = $payment->gateway;

        if ( ! isset( $this->gateways[ $gateway ] ) )
            return '';

        return $this->gateways[ $gateway ]->render_unsubscribe_integration( $category, $listing );
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

    public function render_details( &$payment ) {
        $html  = '';
        $html .= '<div class="wpbdp-payment-details">';
        $html .= '<h4>' . _x( 'Payment Details', 'payments', 'WPBDM' ) . '</h4>';

        // TODO: better payment information.
        // if ( ! $payment->is_pending() ) {
        //     $html .= '<dl class="details">';
        //     $html .= '<dt>' . _x( 'Gateway', 'payments', 'WPBDM' ) . '</dt>';
        //     $html .= '<dd>' . $payment->gateway && isset( $this->gateways[ $payment->gateway ] ) ? $this->gateways[ $payment->gateway ]->get_name() : '–'  . '</dd>';
        //     $html .= '</dl>';
        // }

        $html .= $this->render_invoice( $payment );
        $html .= '</div>';

        return $html;
    }

    /**
     * Renders payment method selection for a given payment. Takes into account gateways supporting recurring items.
     * @param $payment WPBDP_Payment
     * @return string HTML output.
     * @since 3.4
     */
    public function render_payment_method_selection( &$payment ) {
        $payment_methods = $this->get_available_methods( $payment->has_item_type( 'recurring_plan' ) ? array( 'recurring' ) : array() );

        $html  = '';
        $html .= '<div class="wpbdp-payment-method-selection">';
        $html .= '<h4>' . _x( 'Payment Method', 'checkout', 'WPBDM' ) . '</h4>';

        $html .= '<select name="payment_method">';
        $html .= '<option value="none">-- Select a payment method --</option>';
        foreach ( $payment_methods as $method_id ) {
            $html .= '<option value="' . $method_id . '">' . $this->gateways[ $method_id ]->get_name() . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }

    // TODO: dodoc
    public function render_payment_method_integration( &$payment ) {
        $gateway_id = $payment->gateway;

        if ( ! isset( $this->gateways[ $gateway_id ] ) )
            throw new Exception('Unknown gateway for payment.'); // TODO: maybe allow re-selection of the gateway?

        $html  = '';
        $html .= sprintf( '<div class="wpbdp-checkout-gateway-integration %s">', $gateway_id );
        $html .= $this->gateways[ $gateway_id ]->render_integration( $payment );
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

        $last_pending = WPBDP_Payment::objects()->filter( array( 'listing_id' => $listing_id, 'status' => 'pending' ) )->order_by( '-created_on' )->get();

        if ( ! $last_pending || 'initial' != $last_pending['tag'] )
            return $status;

        $threshold = max( 1, absint( wpbdp_get_option( 'payment-abandonment-threshold' ) ) );
        $hours_elapsed = ( current_time( 'timestamp' ) - strtotime( $last_pending['created_on'] ) ) / ( 60 * 60 );

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
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments ps LEFT JOIN {$wpdb->posts} p ON p.ID = ps.listing_id WHERE ps.created_on > %s AND ps.created_on <= %s AND ps.status = %s AND ps.tag = %s AND p.post_status IN ({$post_statuses})",
            $within_abandonment,
            $within_pending,
            'pending',
            'initial'
        ) );
        $count_abandoned = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments ps LEFT JOIN {$wpdb->posts} p ON p.ID = ps.listing_id WHERE ps.created_on <= %s AND ps.status = %s AND ps.tag = %s AND p.post_status IN ({$post_statuses})",
            $within_abandonment,
            'pending',
            'initial'
        ) );

        $views['pending-abandonment'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                                 esc_url( add_query_arg( 'wpbdmfilter', 'pending-abandonment' ) ),
                                                 'pending-abandonment' == wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) ? 'current' : '',
                                                 _x( 'Pending Abandonment', 'admin', 'WPBDM' ),
                                                 number_format_i18n( $count_pending ) );
        $views['abandoned'] = sprintf( '<a href="%s" class="%s">%s</a> <span class="count">(%s)</span></a>',
                                        esc_url( add_query_arg( 'wpbdmfilter', 'abandoned' ) ),
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
        $pieces['where'] .= $wpdb->prepare( ' AND ps.tag = %s AND ps.status = %s ', 'initial', 'pending' );

        switch ( $filter ) {
            case 'abandoned':
                $pieces['where'] .= $wpdb->prepare( ' AND ps.created_on <= %s ', $within_abandonment );
                break;

            case 'pending-abandonment':
                $pieces['where'] .= $wpdb->prepare( ' AND ps.created_on > %s AND ps.created_on <= %s ', $within_abandonment, $within_pending );
                break;
        }

        return $pieces;
    }

    /**
     * @since 3.5.8
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
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE status = %s AND tag = %s AND processed_on IS NULL AND created_on < %s ORDER BY created_on",
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

}

}
