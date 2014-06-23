<?php
require_once( WPBDP_PATH . 'core/class-gateway.php' );

if ( ! class_exists( 'JWT' ) )
    require_once( WPBDP_PATH . 'vendors/jwt/JWT.php' );


class WPBDP_Google_Wallet_Gateway extends WPBDP_Payment_Gateway {

    const LIVE_JS = 'https://wallet.google.com/inapp/lib/buy.js';
    const SANDBOX_JS = 'https://sandbox.google.com/checkout/inapp/lib/buy.js';


    public function get_name() {
        return __( 'Google Wallet', 'google-wallet', 'WPBDM' );
    }

    public function get_integration_method() {
        return WPBDP_Payment_Gateway::INTEGRATION_BUTTON;
    }

    public function get_supported_currencies() {
        return array( 'USD', 'EUR', 'CAD', 'GBP', 'AUD', 'HKD', 'JPY', 'DKK', 'NOK', 'SEK' );
    }

    public function register_config( &$settings ) {
        $s = $settings->add_section( 'payment',
                                     'googlewallet',
                                     $this->get_name() );
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
        $payload['typ'] = 'google/payments/inapp/item/v1';
        $payload['exp'] = time() + 900; // Item expires in 15 mins.
        $payload['iat'] = time();
        $payload['request'] = array(
            'name' => $payment->get_short_description(),
            'description' => $payment->get_description(),
            'price' => round( $payment->get_total(), 0 ),
            'currencyCode' => $payment->get_currency_code(),
            'sellerData' => 'payment_id=' . $payment->get_id() . '&listing_id=' . $payment->get_listing_id()
        );

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
