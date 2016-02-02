<?php
require_once( WPBDP_PATH . 'core/gateways-authorize-net.php' );
require_once( WPBDP_PATH . 'core/class-payment.php' );

require_once( WPBDP_PATH . 'core/class-fees-api.php' );

/*
 * Fees/Payment API
 */

if ( ! class_exists( 'WPBDP_PaymentsAPI' ) ) {

class WPBDP_PaymentsAPI {

    public function __construct() {
        $this->gateways = array();
        $this->register_gateway( 'authorize-net', 'WPBDP_Authorize_Net_Gateway' );

        do_action_ref_array( 'wpbdp_register_gateways', array( &$this ) );
        add_action( 'wpbdp_register_settings', array( &$this, 'register_gateway_settings' ) );
        add_action( 'WPBDP_Payment::set_payment_method', array( &$this, 'gateway_payment_setup' ), 10, 2 );

        // Listing abandonment.
        add_filter( 'WPBDP_Listing::get_payment_status', array( &$this, 'abandonment_status' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_views', array( &$this, 'abandonment_admin_views' ), 10, 2 );
        add_filter( 'wpbdp_admin_directory_filter', array( &$this, 'abandonment_admin_filter' ), 10, 2 );

        //add_action( 'WPBDP_Payment::status_change', array( &$this, 'payment_notification' ) );
//        add_action( 'WPBDP_Payment::before_save', array( &$this, 'gateway_payment_save' ) );
    }

    public function register_gateway($id, $classorinstance ) {
        if ( isset( $this->gateways[ $id ] ) )
            return false;

        if ( ! is_string( $classorinstance ) && ! is_object( $classorinstance ) )
            return false;

        if ( is_string( $classorinstance ) && ! class_exists( $classorinstance ) )
            return false;

        $this->gateways[ $id ] = is_object( $classorinstance ) ? $classorinstance : new $classorinstance;
        return true;
    }

    public function register_gateway_settings( &$settings ) {
        foreach ( $this->gateways as &$gateway )
            $gateway->register_config( $settings );
    }

    public function get_available_methods( $capabilities = array() ) {
        $ok_gateways = array();

        if ( ! wpbdp_get_option( 'payments-on' ) )
            return array();

        foreach ( $this->gateways as $gateway_id => &$gateway ) {
            if ( wpbdp_get_option( $gateway_id ) || 'dummy' == $gateway_id ) {
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

    public function check_config() {
        global $wpdb;

        if ( wpbdp_get_option( 'featured-on' ) && ! wpbdp_get_option( 'payments-on' ) )
            return array(
                sprintf( _x( 'You are offering featured listings but have payments turned off. Go to <a href="%s">Manage Options - Payment</a> to change the payment settings. Until you change this, the <i>Upgrade to Featured</i> option will be disabled.', 'payments-api', 'WPBDM' ), admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=payment' ) )
            );

        if ( ! wpbdp_get_option( 'payments-on' ) )
            return array();

        // Check every registered & enabled gateway to see if it is properly configured.
        $errors = array();
        $gateway_ok = false;

        foreach ( $this->gateways as $gateway_id => &$gateway ) {
            if ( ! wpbdp_get_option( $gateway_id ) )
                continue;

            $gateway_errors = $gateway->validate_config();

            if ( $gateway_errors ) {
                $gateway_messages = rtrim('&#149; ' . implode(' &#149; ', $gateway_errors), '.');
                $errors[] = sprintf(_x('The <b>%s</b> gateway is active but not properly configured. The gateway won\'t be available until the following problems are fixed: <b>%s</b>. <br/> Check the <a href="%s">payment settings</a>.', 'payments-api', 'WPBDM'),
                                        $gateway->get_name(),
                                        $gateway_messages,
                                        admin_url('admin.php?page=wpbdp_admin_settings&groupid=payment') );
            } else {
                $gateway_ok = true;
            }
        }

        if ( ! $gateway_ok ) {
            $errors[] = sprintf(_x('You have payments turned on but no gateway is active and properly configured. Go to <a href="%s">Manage Options - Payment</a> to change the payment settings. Until you change this, the directory will operate in <i>Free Mode</i>.', 'admin', 'WPBDM'),
                                admin_url('admin.php?page=wpbdp_admin_settings&groupid=payment'));
        } else {
            if ( count( $this->get_available_methods() ) >= 2 && $this->is_available( 'payfast' ) ) {
                $errors[] = __( 'BD detected PayFast and another gateway were enabled. This setup is not recommended due to PayFast supporting only ZAR and the other gateways not supporting this currency.', 'admin', 'WPBDM' );
            }

            if ( wpbdp_get_option( 'listing-renewal-auto' ) && ! $this->check_capability( 'recurring' ) ) {
                $errors[] = __( 'You have recurring renewal of listing fees enabled but the payment gateways installed don\'t support recurring payments. Until a gateway that supports recurring payments (such as PayPal) is enabled automatic renewals will be disabled.', 'WPBDM' );
            }

            if ( 0 == absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_fees WHERE tag != %s AND enabled = %d", 'free', 1 ) ) ) ) {
                $errors[] = str_replace( array( '<a href="fees">',
                                                '<a href="settings">' ),
                                         array( '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_fees' ) . '">',
                                                '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=payment' ) . '">' ),
                                         __( 'You have payments enabled but there are no fees available. Users won\'t be able to post listings. Please <a href="fees">create some fees</a> or <a href="settings">configure the Directory</a> to operate in "Free Mode".',
                                             'WPBDM' ) );
            }
        }

        return $errors;
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

    public function get_transactions($listing_id) {
        global $wpdb;

        $transactions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $listing_id));

        foreach ($transactions as &$trans) {
            $trans->payerinfo = unserialize($trans->payerinfo);
            $trans->extra_data = unserialize($trans->extra_data);

            if (!$trans->payerinfo)
                $trans->payerinfo = array('name' => '', 'email' => '');
        }

        return $transactions;
    }

    public function get_last_transaction($listing_id) {
        global $wpdb;

        $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d ORDER BY id DESC LIMIT 1", $listing_id));

        if ($transaction) {
            $transaction->payerinfo = unserialize($transaction->payerinfo);
            $transaction->extra_data = unserialize($transaction->extra_data);            

            if (!$transaction->payerinfo)
                $transaction->payerinfo = array('name' => '', 'email' => '');            

            return $transaction;
        }

        return null;
    }

    /**
     * Resolves ?wpbdpx=payments requests.
     * @since 3.3
     */
    public function process_request() {
        $action = isset( $_GET['action'] ) ? trim( $_GET['action'] ) : '';
        $payment = isset( $_GET['payment_id'] ) ? WPBDP_Payment::get( intval( $_GET['payment_id'] ) ) : null;
        $gid = isset( $_GET['gid'] ) ? trim( $_GET['gid'] ) : '';

        if ( ! in_array( $action, array( 'postback', 'process', 'notify', 'return', 'cancel' ) ) || ( ! $payment && ! $gid ) )
            return;

        unset( $_GET['action'] );

        if ( $payment )
            unset( $_GET['payment_id'] );

        if ( $gid )
            unset( $_GET['gid'] );

        $gateway_id = $payment ? $payment->get_gateway() : $gid;

        if ( ! $gateway_id || ! isset( $this->gateways[ $gateway_id ] )  )
            return;

        if ( ! $payment )
            $this->gateways[ $gateway_id ]->process_generic( $action );
        else
            $this->gateways[ $gateway_id ]->process( $payment, $action );
    }

    /**
     * @since 3.5.8
     */
    public function process_recurring_expiration( $payment_id = 0 ) {
        $payment = WPBDP_Payment::get( $payment_id );

        if ( ! $payment || ! $payment->is_completed() )
            return;

        $gateway = $payment->get_gateway();
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

        $gateway = $payment->get_gateway();

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
        //     $html .= '<dd>' . $payment->get_gateway() && isset( $this->gateways[ $payment->get_gateway() ] ) ? $this->gateways[ $payment->get_gateway() ]->get_name() : '–'  . '</dd>';
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
        $payment_methods = $this->get_available_methods( $payment->has_item_type( 'recurring_fee' ) ? array( 'recurring' ) : array() );

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
        $gateway_id = $payment->get_gateway();

        if ( ! isset( $this->gateways[ $gateway_id ] ) )
            throw new Exception('Unknown gateway for payment.'); // TODO: maybe allow re-selection of the gateway?

        $html  = '';
        $html .= sprintf( '<div class="wpbdp-checkout-gateway-integration %s">', $gateway_id );
        $html .= $this->gateways[ $gateway_id ]->render_integration( $payment );
        $html .= '</div>';

        return $html;
    }

    // TODO: dodoc
    public function render_standard_checkout_page( &$payment, $opts = array() ) {
        if ( $payment->is_completed() )
            return;

        $opts = wp_parse_args( $opts,
                               array( 'return_link' => '<a href="' . wpbdp_get_page_link( 'main' ) . '">' . _x( 'Return to Directory.', 'payment', 'WPBDM' ) . '</a>' )
                             );

        $html  = '';
        $html .= '<div class="wpbdp-checkout">';

        if ( $payment->is_pending() && $payment->has_been_processed() ) {
            $html .= '<p>' . _x( 'Your payment is being processed by the payment gateway. Please reload this page in a moment to see if the status has changed or contact the site administrator.', 'payments', 'WPBDM' ) . '</p>';
        } elseif ( $payment->is_rejected() ) {
            if ( $opts['retry_rejected'] ) {
                $html .= '<p>' . _x( 'The payment has been rejected by the payment gateway. Please contact the site administrator if you think there is an error or click "Change Payment Method" to select another payment method and try again.', 'payments', 'WPBDM' ) . '</p>';
                $html .= '<p><a href="' . esc_url( add_query_arg( 'change_payment_method', 1 ) )  . '">' . _x( 'Change Payment Method', 'payments', 'WPBDM' ) . '</a></p>';
            } else {
                $html .= '<p>' . _x( 'The payment has been rejected by the payment gateway. Please contact the site administrator if you think there is an error.', 'payments', 'WPBDM' ) . '</p>';
            }
        } elseif ( $payment->is_canceled() ) {
            $html .= '<p>' . _x( 'The payment has been canceled at your request.', 'payments', 'WPBDM' ) . '</p>';
        } elseif ( $payment->is_pending() && $payment->get_gateway() ) {
            $html .= $this->render_invoice( $payment );
            $html .= $this->render_payment_method_integration( $payment );
        }

        if ( ! $opts['retry_rejected'] && $opts['return_link'] )
            $html .= '<p>' . $opts['return_link'] . '</p>';

        $html .= '</div>';

        return $html;
    }

    /**
     * @since 3.4.2
     */
    public function gateway_payment_setup( &$payment, $method_id = '' ) {
        if ( ! $method_id || ! isset( $this->gateways[ $method_id ] ) )
            return;

        $gateway = $this->gateways[ $method_id ];
        $gateway->setup_payment( $payment );
    }

//    public function payment_notification( &$payment ) {
//        if ( ! in_array( 'payment-status-change', wpbdp_get_option( 'user-notifications' ), true ) )
//            return;
//
//        if ( 0.0 == $payment->get_total() )
//            return;
//
//
//
//        wpbdp_debug_e( $payment );
//    }


    /**
     * @since 3.5.8
     */
    public function abandonment_status( $status, $listing_id ) {
        // For now, we only consider abandonment if it involves listings with pending INITIAL payments.
        if ( 'pending' != $status || ! $listing_id || ! wpbdp_get_option( 'payment-abandonment' ) )
            return $status;

        $last_pending = WPBDP_Payment::find( array( 'listing_id' => $listing_id, 'status' => 'pending', '_single' => true, '_order' => '-created_on' ), true );

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
