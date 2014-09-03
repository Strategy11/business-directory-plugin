<?php
require_once( WPBDP_PATH . 'core/class-gateway.php' );

if ( ! class_exists( 'JWT' ) )
    require_once( WPBDP_PATH . 'vendors/jwt/JWT.php' );


class WPBDP_Google_Wallet_Gateway extends WPBDP_Payment_Gateway {

    const LIVE_JS = 'https://wallet.google.com/inapp/lib/buy.js';
    const SANDBOX_JS = 'https://sandbox.google.com/checkout/inapp/lib/buy.js';

    public function get_id() {
        return 'googlewallet';
    }

    public function get_name() {
        return __( 'Google Wallet', 'google-wallet', 'WPBDM' );
    }

    public function get_integration_method() {
        return WPBDP_Payment_Gateway::INTEGRATION_BUTTON;
    }

    public function get_supported_currencies() {
        return array( 'USD', 'EUR', 'CAD', 'GBP', 'AUD', 'HKD', 'JPY', 'DKK', 'NOK', 'SEK' );
    }

    public function get_capabilities() {
        return array( 'recurring' );
    }

    /**
     * @since 3.4.2
     */
    public function setup_payment( &$payment ) {
        if ( ! $payment->has_item_type( 'recurring_fee' ) )
            return;

        $items = $payment->get_items();

        // XXX: Google Wallet is full of limitations:
        // - It doesn't handle subscription frequencies different than 30 days, so we must make those kind of fees
        //   non recurring.
        // - It doesn't notify of renewals so we must assume all recurring fees are of indefinite length until we
        //   receive a cancellation notification.
        foreach ( $items as &$item ) {
            if ( 'recurring_fee' != $item->item_type )
                continue;

            if ( $item->data['fee_days'] != 30 ) {
                $item->item_type = 'fee';
                continue;
            }

            $item->data['fee_days'] = 0;
        }

        $payment->update_items( $items );
    }

    public function register_config( &$settings ) {
        global $wpbdp;

        $desc  = '';

        if ( wpbdp_get_option( 'listing-renewal-auto' ) ) {
            $msg = _x( 'For recurring payments to work you need to <a>specify a postback URL</a> in your Google Wallet settings.', 'google-wallet', 'WPBDM' ) . '<br /> ' . 
                   _x( 'Please use %s as the postback URL.', 'google-wallet', 'WPBDM' );
            $url = '<b>' . $wpbdp->payments->gateways['googlewallet']->get_gateway_url() . '</b>';
            $desc .= str_replace( array( '<a>',
                                         '%s' ),
                                  array( '<a href="https://developers.google.com/wallet/digital/docs/postback" target="_blank">',
                                         $url ),
                                  $msg );
        }

        $s = $settings->add_section( 'payment',
                                     'googlewallet',
                                     $this->get_name(),
                                     $desc );
        $settings->add_setting( $s,
                                'googlewallet',
                                __( 'Activate Google Wallet?', 'google-wallet', 'WPBDM' ),
                                'boolean',
                                false );
        $settings->add_setting( $s,
                                'googlewallet-seller-id',
                                __( 'Seller Identifier', 'google-wallet', 'WPBDM' ) );
        $settings->register_dep( 'googlewallet-seller-id', 'requires-true', 'googlewallet' );

        $settings->add_setting( $s,
                                'googlewallet-seller-secret',
                                __( 'Seller Secret', 'google-wallet', 'WPBDM' ) );
        $settings->register_dep( 'googlewallet-seller-secret', 'requires-true', 'googlewallet' );
    }

    public function validate_config() {
        $seller_id = wpbdp_get_option( 'googlewallet-seller-id' );
        $seller_secret = wpbdp_get_option( 'googlewallet-seller-secret' );

        $errors = array();

        if ( ! $seller_id )
            $errors[] = _x( 'Seller ID is missing.', 'google-wallet', 'WPBDM' );

        if ( ! $seller_secret )
            $errors[] = _x( 'Seller Secret is missing.', 'google-wallet', 'WPBDM' );

        return $errors;
    }

    public function render_integration( &$payment ) {
        // See https://developers.google.com/commerce/wallet/digital/docs/jsreference#jwt.

        $payload = array();
        $payload['iss'] = wpbdp_get_option( 'googlewallet-seller-id' );
        $payload['aud'] = 'Google';
        $payload['exp'] = time() + 900; // Item expires in 15 mins.
        $payload['iat'] = time();

        if ( $payment->has_item_type( 'recurring_fee' ) ) {
            $regular_items = array();
            $recurring_item = null;

            foreach ( $payment->get_items() as $item ) {
                if ( $item->item_type == 'recurring_fee' ) {
                    $recurring_item = $item;
                    continue;
                }

                $regular_items[] = $item;
            }

            $payload['typ'] = 'google/payments/inapp/subscription/v1';
            $payload['request'] = array();
            $payload['request']['name'] = $regular_items ? _x( 'One time payment + recurring payment for renewal fees', 'google-wallet', 'WPBDM' ) : $recurring_item->description;
            $payload['request']['sellerData'] = 'payment_id=' . $payment->get_id() . '&listing_id=' . $payment->get_listing_id();
            $payload['request']['recurrence'] = array(
                    'price' => number_format( $recurring_item->amount, 2, '.', '' ),
                    'currencyCode' => $payment->get_currency_code(),
                    'frequency' => 'monthly'
            );

            if ( $regular_items ) {
                $payload['request']['initialPayment'] = array(
                    'price' => number_format( $regular_items[0]->amount, 2, '.', '' ),
                    'currencyCode' => $payment->get_currency_code(),
                    'paymentType' => 'free_trial'
                );
            }
        } else {
            $payload['typ'] = 'google/payments/inapp/item/v1';
            $payload['request'] = array(
                'name' => $payment->get_short_description(),
                'description' => $payment->get_description(),
                'price' => round( $payment->get_total(), 0 ),
                'currencyCode' => $payment->get_currency_code(),
                'sellerData' => 'payment_id=' . $payment->get_id() . '&listing_id=' . $payment->get_listing_id()
            );
        }

        $token = JWT::encode( $payload, wpbdp_get_option( 'googlewallet-seller-secret' ) );

        // HTML button.
        $html  = '';
        $html .= sprintf( '<script src="%s"></script>', wpbdp_get_option( 'payments-test-mode' ) ? self::SANDBOX_JS : self::LIVE_JS );

        $html .= sprintf( '<form action="%s" method="POST">', $this->get_url( $payment, 'process' ) );
        $html .= '<input type="hidden" name="success" value="1" />';
        $html .= '<input type="hidden" name="error" value="" />';
        $html .= sprintf( '<input type="hidden" name="jwt" value="%s" />', $token );
        $html .= '<input type="hidden" name="order_id" value="" />';
        $html .= sprintf( '<a href="#" id="googlewallet-buy">',
                          esc_attr( $token ),
                          esc_url( $this->get_url( $payment, 'process' ) )
                        );
        $html .= sprintf( '<img src="%s" />', WPBDP_URL . 'core/images/googlewallet.gif' );
        $html .= '</a>';
        $html .= '</form>';

        return $html;
    }

    /**
     * @since 3.4.2
     */
    public function process_generic( $action = '' ) {
        if ( 'postback' != $action )
            return;

        $jwt = JWT::decode( wpbdp_getv( $_REQUEST, 'jwt', '' ), wpbdp_get_option( 'googlewallet-seller-secret' ) );

        if ( ! is_object( $jwt ) || ! isset( $jwt->request) || ! isset( $jwt->request->sellerData ) || ! isset( $jwt->response ) )
            die();

        parse_str( $jwt->request->sellerData, $data );

        if ( ! isset( $data['payment_id'] ) )
            die();

        $payment_id = intval( $data['payment_id'] );
        $payment = WPBDP_Payment::get( $payment_id );

        if ( 'googlewallet' != $payment->get_gateway() )
            die();

        if ( 'SUBSCRIPTION_CANCELED' == $jwt->response->statusCode ) {
            $payment->cancel_recurring();
        }

        die();
    }

    public function process( &$payment, $action ) {
        if ( ! $payment->is_pending() )
            return;

        $a = '';

        if ( isset( $_REQUEST['success'] ) && 1 == $_REQUEST['success'] )
            $a = 'success';
        elseif ( isset( $_REQUEST['postback'] ) && 1 == $_REQUEST['postback'] )
            $a = 'postback';
        elseif ( isset( $_REQUEST['error'] ) && ! empty( $_REQUEST['error'] ) && ( ! isset( $_REQUEST['success'] ) || 0 == $_REQUEST['success'] ) )
            $a = 'error';

        switch ( $a ) {
            case 'success':
                $jwt = JWT::decode( wpbdp_getv( $_REQUEST, 'jwt', '' ), wpbdp_get_option( 'googlewallet-seller-secret' ) );

                if ( ! $this->validate_jwt( $jwt, $payment ) ) {
                    $payment->add_error( _x( 'Payment was rejected because internal data does not look like a valid Google Wallet transaction.', 'google-wallet', 'WPBDM' ) );
                    $payment->set_status( WPBDP_Payment::STATUS_REJECTED, WPBDP_Payment::HANDLER_GATEWAY );
                } else {
                    $payment->set_status( WPBDP_Payment::STATUS_COMPLETED, WPBDP_Payment::HANDLER_GATEWAY );
                }

                break;

            case 'error':
                $error = $_REQUEST['error'];

                switch ( $error ) {
                    case 'MERCHANT_ERROR':
                    case 'POSTBACK_ERROR':
                    case 'INTERNAL_SERVER_ERROR':
                        $payment->add_error( _x( 'Payment has been rejected because an internal error occurred.', 'google-wallet', 'WPBDM' ) );
                        $payment->set_status( WPBDP_Payment::STATUS_REJECTED, WPBDP_Payment::HANDLER_GATEWAY );

                        break;
                    case 'PURCHASE_CANCELED':
                    case 'PURCHASE_CANCELLED':
                        $payment->add_error( _x( "The transaction has been canceled at user's request.", 'google-wallet', 'WPBDM' ) );
                        $payment->set_status( WPBDP_Payment::STATUS_CANCELED, WPBDP_Payment::HANDLER_GATEWAY );

                        break;
                }

                break;

            case 'postback':
                // TODO: implement postback URL support.
                break;

            default:
                break;
        }

        $payment->save();

        wp_redirect( $payment->get_redirect_url() );
    }

    private function validate_jwt( &$jwt, &$payment ) {
        if ( !isset( $jwt->request ) || !isset( $jwt->response ) )
            return false;

        if ( !isset( $jwt->iss ) || $jwt->iss != 'Google' )
            return false;

        if ( !isset( $jwt->aud ) || $jwt->aud != wpbdp_get_option( 'googlewallet-seller-id' ) )
            return false;

        // Check seller data.
        $seller_data = isset( $jwt->request->sellerData ) ? $jwt->request->sellerData : null;
        if ( !$seller_data )
            return false;

        parse_str( $seller_data, $data );

        if ( $data['payment_id'] != $payment->get_id() || $data['listing_id'] != $payment->get_listing_id() )
            return false;

        // Check price.
        if ( round( $payment->get_total(), 0 ) != $jwt->request->price )
            return false;

        // Check order ID.
        if ( !isset( $jwt->response->orderId ) )
            return false;

        return true;
    }

    public static function register_gateway( &$payments ) {
        $payments->register_gateway( 'googlewallet', __CLASS__ );
    }

}

add_action( 'wpbdp_register_gateways', array( 'WPBDP_Google_Wallet_Gateway', 'register_gateway' ) );
