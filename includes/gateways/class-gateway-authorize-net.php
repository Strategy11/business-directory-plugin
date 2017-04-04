<?php
/**
 * @since 3.5.7
 */
class WPBDP__Gateway__Authorize_Net extends WPBDP__Payment_Gateway {

    public function get_id() {
        return 'authorize-net';
    }

    public function get_title() {
        return _x( 'Authorize.net', 'authorize-net', 'WPBDM' );
    }

    public function get_integration_method() {
        return 'direct';
    }

    public function supports( $feature ) {
        return in_array( $feature, array( 'recurring' ) );
    }

    public function supports_currency( $currency ) {
        // Hope for the best (:
        return true;
    }

    public function get_settings() {
        return array(
            array( 'login-id', __( 'Login ID', 'authorize-net', 'WPBDM' ), 'text' ),
            array( 'transaction-key', __( 'Transaction Key', 'authorize-net', 'WPBDM' ), 'text' )
        );
    }

    public function validate_settings() {
        $login_id = trim( $this->get_option( 'login-id' ) );
        $trans_key = trim( $this->get_option( 'transaction-key' ) );

        $errors = array();

        if ( ! $login_id )
            $errors[] = _x( 'Login ID is missing.', 'authorize-net', 'WPBDM' );

        if ( ! $trans_key )
            $errors[] = _x( 'Transaction Key is missing.', 'authorize-net', 'WPBDM' );

        return $errors;
    }

    public function process_payment( $payment ) {
        // This is a recurring payment.
        if ( $payment->has_item_type( 'recurring_plan' ) ) {
            return $this->process_payment_recurring( $payment );
        }

        // This is a regular payment.
        $args = array(
            'payment_id' => $payment->id,
            'payment_key' => $payment->payment_key,
            'listing_id' => $payment->listing_id,
            'amount' => $payment->amount,
            'description' => $payment->summary
        );
        $args = array_merge( $args, $payment->get_payer_details() );
        $args = array_merge( $args, wp_array_slice_assoc( $_POST, array( 'card_number', 'exp_month', 'exp_year', 'cvc', 'card_name' ) ) );

        $response = $this->aim_request( $args );

        if ( $response->approved || $response->held ) {
            $payment->status = $response->approved ? 'completed' : 'on-hold';
            $payment->gateway_tx_id = $response->transaction_id;

            if ( $response->held ) {
                $error_msg = sprintf( _x( 'Payment is being held for review by the payment gateway. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                          '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) );
                $payment->log( $error_msg );
            }

            return array( 'result' => 'success' );
        } elseif ( $response->error ) {
            $error_msg = sprintf( _x( 'The payment gateway didn\'t accept the credit card or billing information. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                         '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) );
            $payment->log( $error_msg );

            return array( 'result' => 'failure', 'error' => $error_msg );
        }

        // Payment failed for other reasons.
        $error_msg = sprintf( _x( 'Payment was rejected. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                  '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) );
        $payment->status = 'failed';
        $payment->log( $error_msg );
        return array( 'result' => 'failure', 'error' => $error_msg );
    }

    private function process_payment_recurring( $payment ) {
        @date_default_timezone_set( 'America/Denver' );

        $total = $payment->amount;
        $recurring_item = $payment->find_item( 'recurring_plan' );

        $subscription_args = array(
            'name' => $this->generate_subscription_name( $payment ),
            'intervalLength' => $recurring_item['fee_days'],
            'intervalUnit' => 'days',
            'totalOccurrences' => '9999',
            'startDate' => date( 'Y-m-d' ),
            'amount' => $recurring_item['amount'],
            'creditCardCardNumber' => $_POST['card_number'],
            'creditCardExpirationDate' => sprintf( '%02d', $_POST['exp_month'] ) . '-' . substr( $_POST['exp_year'], 2 ),
            'creditCardCardCode' => $_POST['cvc'],
            'billToFirstName' => $_POST['payer_first_name'],
            'billToLastName' => $_POST['payer_last_name'],
            'billToAddress' => $_POST['payer_address'],
            'billToCity' => $_POST['payer_city'],
            'billToState' => $_POST['payer_state'],
            'billToCountry' => $_POST['payer_country'],
            'billToZip' => $_POST['payer_zip'],
            'customerEmail' => $_POST['payer_email'],
            'orderInvoiceNumber' => $payment->id,
            'orderDescription' => $payment->summary
        );

        if ( $recurring_item['amount'] != $total ) {
            $subscription_args = array_merge( $subscription_args, array(
                'trialAmount' => $total,
                'trialOccurrences' => 1
            ) );
        }

        if ( ! class_exists( 'AuthorizeNetARB' ) )
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );            

        $arb = new AuthorizeNetARB( $this->get_option( 'login-id' ), $this->get_option( 'transaction-key' ) );
        $arb->setSandbox( $this->in_test_mode() );

        $subscription = new AuthorizeNet_Subscription();
        foreach ( $subscription_args as $arg_name => $arg_val ) {
            $subscription->{$arg_name} = $arg_val;
        }

        $response = $arb->createSubscription( $subscription );

        if ( ! $response->isOk() ) {
            $error_msg = sprintf( _x( 'Payment failed. Reason: %s', 'authorize-net', 'WPBDM' ), $response->getMessageText() );
            $payment->log( $error_msg );

            return array( 'result' => 'failure', 'error' => $error_msg );
        }

        $subscription_id = $response->getSubscriptionId();
        $subscription_data = array(
            'payment_id' => $payment->id,
            'customerProfileId'        => ! empty( $response->xml->profile->customerProfileId ) ? (string) $response->xml->profile->customerProfileId : '',
            'customerPaymentProfileId' => ! empty( $response->xml->profile->customerPaymentProfileId ) ? (string) $response->xml->profile->customerPaymentProfileId : ''
        );

        // Payment is OK. Update status and store subscription info.
        $payment->status = 'completed';
        $payment->data['subscription_id'] = $subscription_id;
        $payment->data['subscription_data'] = $subscription_data;

        // // Update listing too.
        // $listing = wpbdp_get_listing( $payment->listing_id );
        // $listing->set_subscription_data( $subscription_id, $subscription_data );       
        // TODO: maybe we should allow gateways to update this instead of doing it automatically with ListingsAPI? ->save() should be
        // called by the gateway on the payment when changing status and THEN register subscription info.

        return array( 'result' => 'success' );
    }

    private function generate_subscription_name( $payment ) {
        $listing = wpbdp_get_listing( $payment->listing_id );
        $recurring_item = $payment->find_item( 'recurring_plan' );

        $name  = '';
        $name .= $listing->get_title() ? $listing->get_title() : sprintf( _x( 'Listing #%d', 'authorize-net', 'WPBDM' ), $listing->get_id() );
        $name .= ' - ';
        $name .= $recurring_item['description'];

        return substr( $name, 0, 50 );
    }

    private function aim_request( $args = array() ) {
        if ( ! class_exists( 'AuthorizeNetAIM' ) )
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );

        $aim = new AuthorizeNetAIM( $this->get_option( 'login-id' ), $this->get_option( 'transaction-key' ) );
        $aim->setSandbox( $this->in_test_mode() );

        // Basic order info.
        $aim->setFields( array(
            'amount' => $args['amount'],
            'description' => $args['description'],
            'invoice_num' => $args['payment_id']
        ) );

        // Card info.
        $aim->setFields( array(
            'card_num' => $args['card_number'],
            'exp_date' => sprintf( '%02d', $args['exp_month'] ) . substr( $args['exp_year'], 2 ),
            'card_code' => $args['cvc']
        ) );

        // Billing info.
        $aim->setFields( array(
            'email' => ! empty( $args['payer_email'] ) ? $args['payer_email'] : '',
            'first_name' => ! empty( $args['payer_first_name'] ) ? $args['payer_first_name'] : '',
            'last_name' => ! empty( $args['payer_last_name'] ) ? $args['payer_last_name'] : '',
            'address' => ! empty( $args['payer_address'] ) ? $args['payer_address'] : '',
            'city' => ! empty( $args['payer_city'] ) ? $args['payer_city'] : '',
            'state' => ! empty( $args['payer_state'] ) ? $args['payer_state'] : '',
            'country' => ! empty( $args['payer_country'] ) ? $args['payer_country'] : '',
            'zip' => ! empty( $args['payer_zip'] ) ? $args['payer_zip'] : ''
        ) );

        $aim->setCustomField( 'payment_id', $args['payment_id'] );
        $aim->setCustomField( 'payment_key', $args['payment_key'] );
        $aim->setCustomField( 'listing_id', $args['listing_id'] );

        $response = $aim->authorizeAndCapture();

        return $response;
    }

}
