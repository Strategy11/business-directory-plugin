<?php
require_once( WPBDP_PATH . 'core/class-gateway.php' );

// TODO: add a warning about other currencies not being supporte and link to the relevant docs.
// TODO: add a warning about SSL not being on and being required for Authorize.net (unless in test mode).
// TODO: add a warning if CURL and the other reqs for Authorize are not present.

/**
 * @since 3.5.7
 */
class WPBDP_Authorize_Net_Gateway extends WPBDP_Payment_Gateway {

    public function register_config( &$settings ) {
        $s = $settings->add_section( 'payment',
                                     'authorize-net',
                                     $this->get_name() );
        $settings->add_setting( $s,
                                'authorize-net',
                                __( 'Activate Authorize.net?', 'authorize-net', 'WPBDM' ),
                                'boolean',
                                false );
        $settings->register_dep( 'authorize-net', 'requires-true', 'payments-on' );
        $settings->add_setting( $s,
                                'authorize-net-login-id',
                                __( 'Login ID', 'authorize-net', 'WPBDM' ) );
        $settings->register_dep( 'authorize-net-login-id', 'requires-true', 'authorize-net' );
        $settings->add_setting( $s,
                                'authorize-net-transaction-key',
                                __( 'Transaction Key', 'authorize-net', 'WPBDM' ) );
        $settings->register_dep( 'authorize-net-transaction-key', 'requires-true', 'authorize-net' );
    }

    public function validate_config() {
        $login_id = trim( wpbdp_get_option( 'authorize-net-login-id' ) );
        $trans_key = trim( wpbdp_get_option( 'authorize-net-transaction-key' ) );

        $errors = array();

        if ( ! $login_id )
            $errors[] = _x( 'Login ID is missing.', 'authorize-net', 'WPBDM' );

        if ( ! $trans_key )
            $errors[] = _x( 'Transaction Key is missing.', 'authorize-net', 'WPBDM' );

        return $errors;
    }

    public function get_capabilities() {
        return array( 'recurring', 'handles-expiration' );
    }

    public function get_integration_method() {
        return WPBDP_Payment_Gateway::INTEGRATION_FORM;
    }

    public function render_integration( &$payment ) {
        $args = array();
        return $this->render_billing_information_form( $payment, $args );
    }

    public function process( &$payment, $action ) {
        if ( ! $this->validate_billing_information( $payment ) ) {
            wp_redirect( esc_url_raw( $payment->get_checkout_url() ) );
            die();
        }

        if ( 'pending' != $payment->get_status() )
            die();

        $payment->clear_errors();

        if ( ! class_exists( 'AuthorizeNetAIM' ) )
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );

        if ( $payment->has_item_type( 'recurring_fee' ) ) {
            // TODO: round fees not within 7-365 days (or make non-recurring).
            return $this->process_recurring( $payment );
        }

        $data = $payment->get_data( 'billing-information' );

        $response = $this->doAIM( $payment->get_total(),
                                  $payment->get_short_description(),
                                  $payment->get_id(),
                                  $payment->get_listing_id(),
                                  $data );

        if ( $response->approved ) {
            $payment->set_status( WPBDP_Payment::STATUS_COMPLETED, WPBDP_Payment::HANDLER_GATEWAY );
        } elseif ( $response->error ) {
            $payment->set_data( 'validation-errors', array(
                sprintf( _x( 'The payment gateway didn\'t accept your credit card or billing information. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                         '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) ) )
            );
        } elseif ( $response->held ) {
            $payment->add_error( sprintf( _x( 'Your payment is being held for review by the payment gateway. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                          '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) ) );
        } else {
            $payment->add_error( sprintf( _x( 'Payment was rejected. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                          '(' . $response->response_reason_code . ') ' . rtrim( $response->response_reason_text, '.' ) ) );
            $payment->set_status( WPBDP_Payment::STATUS_REJECTED, WPBDP_Payment::HANDLER_GATEWAY );
        }

        $payment->save();

        wp_redirect( esc_url_raw( $payment->get_redirect_url() ) ); die();
    }

    private function process_recurring( &$payment ) {
        $data = $payment->get_data( 'billing-information' );

        $summary = $payment->summarize();

        if ( $summary['balance'] > 0.0 ) {
            $res1 = $this->doAIM( $summary['balance'],
                                  _x( 'Setup fee' ,'authorize-net', 'WPBDM' ),
                                  $payment->get_id(),
                                  $payment->get_listing_id(),
                                  $data );

            if ( ! $res1->approved ) {
                $payment->add_error( sprintf( _x( 'Payment was rejected. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                          '(' . $res1->response_reason_code . ') ' . rtrim( $res1->response_reason_text, '.' ) ) );
                $payment->set_status( WPBDP_Payment::STATUS_REJECTED, WPBDP_Payment::HANDLER_GATEWAY );
                $payment->save();

                wp_redirect( esc_url_raw( $payment->get_redirect_url() ) );
                die();
            }
        }

        $arb = new AuthorizeNetARB( wpbdp_get_option( 'authorize-net-login-id' ),
                                    wpbdp_get_option( 'authorize-net-transaction-key' ) );

        if ( wpbdp_get_option( 'payments-test-mode' ) )
            $arb->setSandbox( true );
        else
            $arb->setSandbox( false );


        $s = new AuthorizeNet_Subscription();
        $s->intervalLength = $summary['recurring_days'];
        $s->intervalUnit = 'days';
        $s->totalOccurrences = '9999';
        $s->startDate = date( 'Y-m-d',
                              strtotime( '+1 day', current_time( 'timestamp' ) ) );
        $s->amount = $summary['recurring_amount'];

        if ( $summary['trial'] ) {
            $s->trialOccurrences = '1';
            $s->trialAmount = $summary['trial_amount'];
        }

        $s->creditCardCardNumber = $data['cc_number'];
        $s->creditCardExpirationDate = $data['cc_exp_year'] . '-' . $data['cc_exp_month'];
        $s->creditCardCardCode = $data['cc_cvc'];

        $s->billToFirstName = $data['first_name'];
        $s->billToLastName = $data['last_name'];
        $s->billToAddress = $data['address_line1'];
        $s->billToCity = $data['address_city'];
        $s->billToState = $data['address_state'];
        $s->billToCountry = $data['address_country'];
        $s->billToZip = $data['zipcode'];

        $s->orderInvoiceNumber = $payment->get_id();
        $s->orderDescription = $payment->get_short_description();
        // TODO: maybe add zip, phone, email, cust_id.

        $response = $arb->createSubscription( $s );

        if ( ! $response->isOk() ) {
            $payment->add_error( _x( 'Could not process payment.', 'authorize-net', 'WPBDM' ) );
            $payment->set_status( WPBDP_Payment::STATUS_REJECTED, WPBDP_Payment::HANDLER_GATEWAY );
        } else {
            $subscription_id = $response->getSubscriptionId();

            $payment->set_data( 'recurring_id', $subscription_id );
            $payment->set_status( WPBDP_Payment::STATUS_COMPLETED, WPBDP_Payment::HANDLER_GATEWAY );
        }

        $payment->save();
        wp_redirect( esc_url_raw( $payment->get_redirect_url() ) );
    }

    public function handle_expiration( $payment ) {
        if ( ! class_exists( 'AuthorizeNetAIM' ) )
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );

        $recurring = $payment->get_recurring_item();
        $listing = WPBDP_Listing::get( $payment->get_listing_id() );

        if ( ! $listing || ! $recurring )
            return;

        $recurring_id = $payment->get_data( 'recurring_id' );

        $arb = new AuthorizeNetARB( wpbdp_get_option( 'authorize-net-login-id' ),
                                    wpbdp_get_option( 'authorize-net-transaction-key' ) );

        if ( wpbdp_get_option( 'payments-test-mode' ) )
            $arb->setSandbox( true );
        else
            $arb->setSandbox( false );

        $response = $arb->getSubscriptionStatus( $recurring_id );
        $status = $response->isOk() ? $response->getSubscriptionStatus() : '';

        if ( 'active' == $status ) {
            // If subscription is active, renew automatically for another period.
            $term_payment = $payment->generate_recurring_payment();
            $term_payment->set_status( WPBDP_Payment::STATUS_COMPLETED, WPBDP_Payment::HANDLER_GATEWAY );
            $term_payment->save();
        } else {
            // If subscription is not active, make item non recurring so it expires normally next time.
            $recurring_item = $payment->get_recurring_item();
            $listing->make_category_non_recurring( $recurring_item->rel_id_1 );
        }
    }

    private function doAIM( $amount, $desc, $invoice, $listing_id, $data ) {
        $aim = new AuthorizeNetAIM( wpbdp_get_option( 'authorize-net-login-id' ),
                                    wpbdp_get_option( 'authorize-net-transaction-key' ) );

        if ( wpbdp_get_option( 'payments-test-mode' ) )
            $aim->setSandbox( true );
        else
            $aim->setSandbox( false );

        // Order info.
        $aim->setFields( array( 'amount' => $amount,
                                'description' => $desc,
                                'invoice_num' => $invoice ) );

        // Card info.
        $aim->setFields(array(
            'card_num' => $data['cc_number'],
            'exp_date' => $data['cc_exp_month'] . substr( $data['cc_exp_year'], 0, 2 ),
            'card_code' => $data['cc_cvc']
        ));

        // Billing addres info.
        $aim->setFields(array(
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address' => $data['address_line1'],
            'city' => $data['address_city'],
            'state' => $data['address_state'],
            'country' => $data['address_country'],
            'zip' => $data['zipcode']
        ));
        // TODO: maybe add zip, phone, email and cust_id

        $aim->setCustomField( 'payment_id', $invoice );
        $aim->setCustomField( 'listing_id', $listing_id );

        $response = $aim->authorizeAndCapture();

        return $response;
    }

}

