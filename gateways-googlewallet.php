<?php
require_once( WPBDP_PATH . 'libs/jwt/JWT.php' );

class WPBDP_GoogleWalletGateway {

	public function __construct() {
		add_action( 'wpbdp_modules_init', array( &$this, 'init' ) );
	}

	public function init() {
        // Register settings.
        $settings = wpbdp_settings_api();
        $s = $settings->add_section( 'payment', 'googlewallet', __( 'Google Wallet', 'google-wallet', 'WPBDM' ) );
        $settings->add_setting( $s,
                                'googlewallet',
                                __( 'Activate Google Wallet?', 'google-wallet', 'WPBDM' ),
                                'boolean',
                                false
                              );
        $settings->add_setting( $s,
                                'googlewallet-seller-id',
                                __( 'Seller Identifier', 'google-wallet', 'WPBDM' ) );
        $settings->add_setting( $s,
                                'googlewallet-seller-secret',
                                __( 'Seller Secret', 'google-wallet', 'WPBDM' ) );        

        // Register gateway.
        $payments_api = wpbdp_payments_api();
        $payments_api->register_gateway( 'googlewallet', array(
        	'name' => _x( 'Google Wallet', 'googlewallet', 'WPBDM' ),
            'check_callback' => array( &$this, 'check_config' ),
        	'html_callback' => array( &$this, 'googlewallet_html' ),
        	'process_callback' => array( &$this, 'process_payment' )
        ) );
	}

    // TODO: The currently supported currencies are USD, EUR, CAD, GBP, AUD, HKD, JPY, DKK, NOK, SEK.
    public function check_config() {
        $seller_id = trim( wpbdp_get_option( 'googlewallet-seller-id' ) );
        $seller_secret = trim( wpbdp_get_option( 'googlewallet-seller-secret' ) );

        $errors = array();

        if ( !$seller_id )
            $errors[] = _x( 'Seller ID is missing.', 'google-wallet', 'WPBDM' );

        if ( !$seller_secret )
            $errors[] = _x( 'Seller Secret is missing.', 'google-wallet', 'WPBDM' );        

        return $errors;
    }

    public function googlewallet_html( $tid ) {
        $payments = wpbdp_payments_api();
        $transaction = $payments->get_transaction( $tid );
        $processing_url = $payments->get_processing_url( 'googlewallet', $transaction );

        if ( $transaction->payment_type == 'upgrade-to-sticky' ) {
            $item_name = _x( 'Listing Upgrade', 'google-wallet', 'WPBDM' );
            $item_description = sprintf( _x( 'Upgrade for listing "%s"', 'google-wallet', 'WPBDM' ), get_the_title( $transaction->listing_id ) );
        } else {
            $item_name = _x( 'Listing Submit', 'google-wallet', 'WPBDM' );
            $item_description = sprintf( _x( 'Payment for listing submit (listing "%s")', 'google-wallet', 'WPBDM' ), get_the_title( $transaction->listing_id ) );
        }

        // See https://developers.google.com/commerce/wallet/digital/docs/jsreference#jwt.
        $payload = array(
            'iss' => wpbdp_get_option( 'googlewallet-seller-id' ),
            'aud' => 'Google',
            'typ' => 'google/payments/inapp/item/v1',
            'exp' => time() + 900, /* item expires in 15 mins */
            'iat' => time(),
            'request' => array(
                'name' => $item_name,
                'description' => $item_description,
                'price' => round( $transaction->amount, 0 ),
                'currencyCode' => wpbdp_get_option( 'currency' ),
                'sellerData' => 'transaction_id=' . $tid . '&listing_id=' . $transaction->listing_id
            )
        );
        $token = JWT::encode( $payload, wpbdp_get_option( 'googlewallet-seller-secret' ) );

        // HTML button.
        $html  = '';

        if ( $payments->in_test_mode() ) {
            $html .= '<script src="https://sandbox.google.com/checkout/inapp/lib/buy.js"></script>';
        } else {
            $html .= '<script src="https://wallet.google.com/inapp/lib/buy.js"></script>';
        }
        
        $html .= sprintf( '<form action="%s" method="POST">', $processing_url );
        $html .= '<input type="hidden" name="success" value="1" />';
        $html .= '<input type="hidden" name="error" value="" />';
        $html .= sprintf( '<input type="hidden" name="jwt" value="%s" />', $token );
        $html .= '<input type="hidden" name="order_id" value="" />';
        $html .= sprintf( '<a href="#" id="googlewallet-buy">',
                          esc_attr( $token ),
                          esc_url( $processing_url )
                        );
        $html .= sprintf( '<img src="%s" />', WPBDP_URL . 'resources/images/googlewallet.gif' );
        $html .= '</a>';
        $html .= '</form>';

        return $html;
    }

    public function process_payment( $args, &$error_message ) {
        $payments = wpbdp_payments_api();
        $transaction = $payments->get_transaction_from_uri_id() or die();

        if ( $transaction->status == 'rejected' ) {
            $error_message = sprintf( _x( 'Your payment has been rejected by Google Wallet (Transaction ID: %s). Please contact the site admin if you have any questions.', 'google-wallet', 'WPBDM' ), $transaction->id );
            return $transaction->id;
        }

        $page = '';
        if ( isset( $_REQUEST['success'] ) && $_REQUEST['success'] == 1 )
            $page = 'success';
        elseif ( isset( $_REQUEST['postback'] ) && $_REQUEST['postback'] == 1 )
            $page = 'postback';
        elseif ( isset( $_REQUEST['error'] ) && !empty( $_REQUEST['error'] ) && ( !isset( $_REQUEST['success'] ) || $_REQUEST['success'] == 0 ) )
            $page = 'error';
        
        switch ( $page ) {
            case 'success':
                if ( $transaction->status == 'pending' ) {
                    // Try to approve transaction.
                    $transaction->gateway = 'googlewallet';
                    $transaction->processed_by = 'gateway';
                    $transaction->processed_on = current_time( 'mysql' );

                    $jwt = JWT::decode( wpbdp_getv( $args, 'jwt', '' ), wpbdp_get_option( 'googlewallet-seller-secret' ) );

                    if ( !$this->validate_jwt( $jwt, $transaction ) ) {
                        $transaction->status = 'rejected';
                        $error_message = sprintf( _x( 'Your payment has been rejected because the data received does not look like a valid Google Wallet transaction. Please contact the admin if you have any questions. (Transaction ID: %s)', 'google-wallet' ), $transaction->id );
                    } else {
                        $transaction->status = 'approved';
                    }
                }

                $payments->save_transaction( $transaction );
                return $transaction->id;

                break;

            case 'error':
                if ( $transaction->status == 'approved' )
                    die();

                $error = $args['error'];

                $transaction->gateway = 'googlewallet';
                $transaction->processed_by = 'gateway';
                $transaction->processed_on = current_time( 'mysql' );
                $transaction->status = 'rejected';
                $payments->save_transaction( $transaction );

                switch ( $error ) {
                    case 'MERCHANT_ERROR':
                    case 'POSTBACK_ERROR':
                    case 'INTERNAL_SERVER_ERROR':
                        $error_message = sprintf( _x( 'Your payment has been rejected because an internal error occurred. Please contact the site admin to solve this using Transaction ID: %s and Listing ID: %d.', 'google-wallet', 'WPBDM' ),
                                                      $transaction->id,
                                                      $transaction->listing_id );

                        break;

                    case 'PURCHASE_CANCELED':
                    case 'PURCHASE_CANCELLED':
                        $error_message = _x( 'The transaction has been canceled as you requested.', 'google-wallet', 'WPBDM' );
                        break;
                }

                return $transaction->id;
                break;

            case 'postback':
                // TODO: implement postback URL support.
                break;

            default:
                $error_message = _x( 'Invalid request.', 'google-wallet', 'WPBDM' );
                return $transaction->id;

                break;
        }

    }

    private function validate_jwt( &$jwt, &$transaction ) {
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

        if ( $data['transaction_id'] != $transaction->id || $data['listing_id'] != $transaction->listing_id )
            return false;

        // Check price.
        if ( round( $transaction->amount, 0 ) != $jwt->request->price )
            return false;

        // Check order ID.
        if ( !isset( $jwt->response->orderId ) )
            return false;

        return true;
    }

}

new WPBDP_GoogleWalletGateway();
