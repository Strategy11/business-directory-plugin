<?php
require_once( WPBDP_PATH . 'core/class-gateway.php' );

// TODO: add a warning about other currencies not being supporte and link to the relevant docs.
// TODO: add a warning about SSL not being on and being required for Authorize.net (unless in test mode).

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
        $settings->add_setting( $s,
                                'authorize-net-login-id',
                                __( 'Login ID', 'authorize-net', 'WPBDM' ) );
        $settings->add_setting( $s,
                                'authorize-net-transaction-key',
                                __( 'Transaction Key', 'authorize-net', 'WPBDM' ) );
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

    public function get_integration_method() {
        return WPBDP_Payment_Gateway::INTEGRATION_FORM;
    }

    public function render_integration( &$payment ) {
        $args = array();
        return $this->render_billing_information_form( $payment, $args );
    }

    public function process( &$payment, $action ) {
        if ( ! $this->validate_billing_information( $payment, $errors ) ) {
            wp_redirect( $payment->get_checkout_url() );
            die();
        }

        if ( 'pending' != $payment->get_status() )
            die();

        if ( ! class_exists( 'AuthorizeNetAIM' ) )
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );

        $data = $payment->get_data( 'billing-information' );

        $aim = new AuthorizeNetAIM( wpbdp_get_option( 'authorize-net-login-id' ),
                                    wpbdp_get_option( 'authorize-net-transaction-key' ) );

        if ( wpbdp_get_option( 'payments-test-mode' ) )
            $aim->setSandbox( true );

        // Order info.
        $aim->setFields( array(
            'amount' => $payment->get_total(),
            'description' => $payment->get_short_description(),
            'invoice_num' => $payment->get_id()
        ) );

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
            'country' => $data['address_country']
        ));
        // TODO: maybe add zip, phone, email and cust_id

        $aim->setCustomField( 'payment_id', $payment->get_id() );
        $aim->setCustomField( 'listing_id', $payment->get_listing_id() );

        $response = $aim->authorizeAndCapture();

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

        wp_redirect( $payment->get_redirect_url() ); die();
    }



}

