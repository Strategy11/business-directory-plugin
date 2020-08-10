<?php
/**
 * @since 3.5.7
 */


require_once( WPBDP_PATH . 'vendors/anet_php_sdk/autoload.php' );

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;


class WPBDP__Gateway__Authorize_Net extends WPBDP__Payment_Gateway {

    private $API_Endpoint = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
    private $merchantAuthentication = null;

    public function __construct() {
        parent::__construct();

        // Silent Post / webhooks are not very reliable so we handle expiration a different way:
        // once the listing has actually expired, we verify the subscription status and act accordingly.
        add_action( 'wpbdp_listing_expired', array( $this, 'maybe_handle_expiration' ) );

        if ( ! $this->in_test_mode() ) {
            $this->API_Endpoint = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        }
    }

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
            array( 'id' => 'login-id', 'name' => _x( 'Login ID', 'authorize-net', 'WPBDM' ), 'type' => 'text' ),
            array( 'id' => 'transaction-key', 'name' => _x( 'Transaction Key', 'authorize-net', 'WPBDM' ), 'type' => 'text' )
        );
    }

    /**
     * @since 5.7.2
     */
    private function setup_merchant_authentication() {
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName( $this->get_option( 'login-id' ) );
        $merchantAuthentication->setTransactionKey( $this->get_option( 'transaction-key' ) );

        $this->merchantAuthentication = $merchantAuthentication;
    }

    /**
     * @since 5.5.11
     * @deprecated since 5.7.2
     */
    private function get_authnet( $class = NULL ) {
        if ( ! class_exists( 'AuthorizeNet' . $class ) ) {
            require_once( WPBDP_PATH . 'vendors/anet_php_sdk/AuthorizeNet.php' );
        }

        if ( ! $class ) {
            throw new AuthorizeNetException;
        }

        if ( 'ARB' == $class ) {
            return new AuthorizeNetARB( $this->get_option( 'login-id' ), $this->get_option( 'transaction-key' ) );
        }

        if ( 'AIM' == $class ) {
            return new AuthorizeNetAIM( $this->get_option( 'login-id' ), $this->get_option( 'transaction-key' ) );
        }
    }

    public function validate_settings() {
        $login_id = trim( $this->get_option( 'login-id' ) );
        $trans_key = trim( $this->get_option( 'transaction-key' ) );

        $errors = array();

        if ( ! $login_id ) {
            $errors[] = _x( 'Login ID is missing.', 'authorize-net', 'WPBDM' );
        }

        if ( ! $trans_key ) {
            $errors[] = _x( 'Transaction Key is missing.', 'authorize-net', 'WPBDM' );
        }

        if ( $errors ) {
            return $errors;
        }

        $url = $this->in_test_mode() ? 'https://apitest.authorize.net/xml/v1/request.api' : 'https://api.authorize.net/xml/v1/request.api';

        $request = wp_remote_post(
            $url,
            array(
                'timeout' => 10,
                'sslverify' => false,
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(
                    array(
                        'authenticateTestRequest' => array(
                            'merchantAuthentication' => array(
                                'name' => $login_id,
                                'transactionKey' => $trans_key
                            )
                        )
                    )
                )
            )
        );

        $response = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', wp_remote_retrieve_body( $request ) ) );

        if ( ! $response || ! isset( $response->messages ) || ! isset( $response->messages->resultCode ) ) {
            $errors[] = _x( 'Credentials validation failed: Could not contact AuthNet Endpoint', 'authorize-net', 'WPBDM' );
            return $errors;
        }

        if ( 'Ok' !== $response->messages->resultCode ) {
            $errors[] = sprintf(
                '%s: %s',
                _x( 'Credentials validation failed', 'authorize-net', 'WPBDM' ),
                $response->messages->message[0]->text
            );
        }

        return $errors;
    }

    public function process_payment( $payment ) {
        $this->setup_merchant_authentication();
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
            'orderInvoiceNumber' => $payment->id,
            'orderDescription' => $payment->summary
        );

        $args = array_merge( $args, $payment->get_payer_details() );
        $args = array_merge( $args, wp_array_slice_assoc( $_POST, array( 'card_number', 'exp_month', 'exp_year', 'cvc', 'card_name' ) ) );

        $refId = 'ref' . time();

        $args['card_object']        = $this->create_card( $args['card_number'], $args['exp_year'] . '-' . $args['exp_month'], $args['cvc'] );
        $args['payment_type']       = $this->create_payment_type( $args['card_object'] );
        $args['order']              = $this->create_order( $args );
        $args['billing_address']    = $this->create_billing_address( $args );
        $args['customer']           = $this->create_or_retrieve_customer( $args );
        $args['setting_payment_id'] = $this->create_payment_id_reference( $args );
        $args['setting_listing_id'] = $this->create_listing_id_reference( $args );

        $transaction_request_type = $this->create_transaction_request_type( $args );

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication( $this->merchantAuthentication );
        $request->setRefId($refId);
        $request->setTransactionRequest($transaction_request_type);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse( $this->API_Endpoint );

        if ($response != null) {
            // Check to see if the API request was successfully received and acted upon
            if ($response->getMessages()->getResultCode() == "Ok") {
                // Since the API request was successful, look for a transaction response
                // and parse it to display the results of authorizing the card
                $tresponse = $response->getTransactionResponse();

                $error = false;
            
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    switch( $tresponse->getResponseCode() ) {
                        case '1':
                            $payment->status = 'completed';
                            $payment->gateway_tx_id = $tresponse->getTransId();
                        break;
                        case '2':
                            $payment->status = 'declined';
                            $error = true;
                        break;
                        case '4':
                            $payment->status = 'on-hold';
                            $payment->gateway_tx_id = $tresponse->getTransId();
                            $payment->log(
                                sprintf(
                                    _x(
                                        'Payment is being held for review by the payment gateway. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                        '(' . $tresponse->getMessages()[0]->getCode() . ') ' . rtrim( $tresponse->getMessages()[0]->getDescription(), '.'
                                    )
                                )
                            );
                        break;
                        case '3':
                        default:
                            $payment->status = 'error';
                            $error = true;
                        break;
                    }

                    if ( $error ) {
                        $error_msg = sprintf(
                            _x( 'Payment is being held for review by the payment gateway. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                                '(' . $tresponse->getMessages()[0]->getCode() . ') ' . rtrim( $tresponse->getMessages()[0]->getDescription(), '.'
                            )
                        );
                        $payment->log( $error_msg );
                        
                        $payment->save();

                        return array( 'result' => 'failure', 'error' => $error_msg );
                    }

                    $payment->save();
        
                    return array( 'result' => 'success' );
                }
            }
             // Payment failed for other reasons.
            $error_msg = sprintf(
                _x( 'Payment was rejected. The following reason was given: "%s".', 'authorize-net', 'WPBDM' ),
                    '(' . $tresponse->getErrors()[0]->getErrorCode() . ') ' . rtrim( $response->getMessages()->getMessage()[0]->getText(), '.' )
                );

        } else {
            $error_msg = _x( "No response returned", 'authorize-net', 'WPBDM' );
        }

        $payment->status = 'failed';
        $payment->log( $error_msg );
        $payment->save();

        return array( 'result' => 'failure', 'error' => $error_msg );
    }

    private function process_payment_recurring( $payment ) {

        @date_default_timezone_set( 'America/Denver' );

        $total = $payment->amount;
        $recurring_item = $payment->find_item( 'recurring_plan' );

        $post = stripslashes_deep( $_POST );

        $subscription_args = array(
            'name' => $this->generate_subscription_name( $payment ),
            'intervalLength' => $recurring_item['fee_days'],
            'intervalUnit' => 'days',
            'totalOccurrences' => '9999',
            'startDate' => date( 'Y-m-d' ),
            'amount' => $recurring_item['amount'],
            'creditCardCardNumber' => $post['card_number'],
            'creditCardExpirationDate' => sprintf( '%02d', $post['exp_month'] ) . '-' . substr( $post['exp_year'], 2 ),
            'creditCardCardCode' => $post['cvc'],
            'first_name' => $post['payer_first_name'],
            'last_name' => $post['payer_last_name'],
            'address' => $post['payer_address'],
            'address_2' => $post['payer_address_2'],
            'city' => $post['payer_city'],
            'state' => $post['payer_state'],
            'country' => $post['payer_country'],
            'zip' => $post['payer_zip'],
            'customerEmail' => $post['payer_email'],
            'orderInvoiceNumber' => $payment->id,
            'orderDescription' => $payment->summary
        );

        if ( $recurring_item['amount'] != $total ) {
            $subscription_args = array_merge( $subscription_args, array(
                'trialAmount' => $total,
                'trialOccurrences' => 1
            ) );
        }

        // Set the transaction's refId
        $refId = 'ref' . time();

        // Subscription Type Info
        $subscription = new AnetAPI\ARBSubscriptionType();
        $subscription->setName( $subscription_args['name'] );

        $interval = new AnetAPI\PaymentScheduleType\IntervalAType();
        $interval->setLength( $subscription_args['intervalLength'] );
        $interval->setUnit( $subscription_args['intervalUnit'] );

        $paymentSchedule = new AnetAPI\PaymentScheduleType();
        $paymentSchedule->setInterval( $interval );
        $paymentSchedule->setStartDate( new DateTime( $subscription_args['startDate'] ) );
        $paymentSchedule->setTotalOccurrences( $subscription_args['totalOccurrences'] );

        if ( ! empty( $subscription_args['trialOccurrences'] ) ) {
            $paymentSchedule->setTrialOccurrences( $subscription_args['trialOccurrences'] );
        }

        $subscription->setPaymentSchedule( $paymentSchedule );
        $subscription->setAmount( $subscription_args['amount'] );
        if ( ! empty( $subscription_args['trialAmount'] ) ) {
            $subscription->setTrialAmount( $subscription_args['trialAmount'] );
        }
        
        $creditCard  = $this->create_card( $subscription_args['creditCardCardNumber'], $subscription_args['creditCardExpirationDate'], $subscription_args['creditCardCardCode'] );
        $paymentType = $this->create_payment_type( $creditCard );
        $subscription->setPayment( $paymentType );

        $order = $this->create_order( $subscription_args );
        $subscription->setOrder($order);
        
        $billTo = $this->create_billing_address( $subscription_args );
        $subscription->setBillTo($billTo);

        $request = new AnetAPI\ARBCreateSubscriptionRequest();
        $request->setmerchantAuthentication( $this->merchantAuthentication );
        $request->setRefId( $refId );
        $request->setSubscription( $subscription );
        $controller = new AnetController\ARBCreateSubscriptionController($request);

        $response = $controller->executeWithApiResponse( $this->API_Endpoint );

        if ( $response != null && 'Ok' === $response->getMessages()->getResultCode() ) {
            $subscription_id = $response->getSubscriptionId();

            // Payment is OK.
            $payment->status = 'completed';
            $payment->save();

            // Register subscription.
            $subscription = $payment->get_listing()->get_subscription();
            $subscription->set_subscription_id( $subscription_id );
            $subscription->record_payment( $payment );

            return array( 'result' => 'success' );
        }

        $errorMessages = $response->getMessages()->getMessage();
        $error_msg = sprintf( _x( 'Payment failed. Reason: %s', 'authorize-net', 'WPBDM' ), $errorMessages[0]->getText() );
        $payment->log( $error_msg );

        return array( 'result' => 'failure', 'error' => $error_msg );
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

    /**
     * @deprecated since 5.7.2
     */
    private function aim_request( $args = array() ) {
        $aim = $this->get_authnet( 'AIM' );
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
            'email' => ! empty( $args['email'] ) ? $args['email'] : '',
            'first_name' => ! empty( $args['first_name'] ) ? $args['first_name'] : '',
            'last_name' => ! empty( $args['last_name'] ) ? $args['last_name'] : '',
            'address' => ! empty( $args['address'] ) ? $args['address'] : '',
            'city' => ! empty( $args['city'] ) ? $args['city'] : '',
            'state' => ! empty( $args['state'] ) ? $args['state'] : '',
            'country' => ! empty( $args['country'] ) ? $args['country'] : '',
            'zip' => ! empty( $args['zip'] ) ? $args['zip'] : ''
        ) );

        $aim->setCustomField( 'payment_id', $args['payment_id'] );
        $aim->setCustomField( 'payment_key', $args['payment_key'] );
        $aim->setCustomField( 'listing_id', $args['listing_id'] );

        $response = $aim->authorizeAndCapture();

        return $response;
    }

    public function maybe_handle_expiration( $listing ) {
        if ( ! $listing || ! $listing->has_subscription() )
            return;

        $subscription = $listing->get_subscription();
        $payment = $subscription->get_parent_payment();

        if ( ! $payment || 'authorize-net' != $payment->gateway )
            return;

        $susc_id = $subscription->get_subscription_id();
        if ( ! $susc_id ) {
            return;
        }

        $status = $this->get_subscription_status( $susc_id );

        if ( 'active' == $status ) {
            $subscription->record_payment( array( 'amount' => $payment->amount ) );
            $subscription->renew();
        } else {
            $subscription->cancel();
        }
    }

    /**
     * @deprecated since 5.7.2
     */
    private function setup_webhooks() {
        if ( $this->in_test_mode() ) {
            $authorize_net_api = 'https://apitest.authorize.net';
        } else {
            $authorize_net_api = 'https://api.authorize.net';
        }

        $auth_header = 'Basic ' . base64_encode( $this->get_option( 'login-id' ) . ':' . $this->get_option( 'transaction-key' ) );

        $listener_url = $this->get_listener_url();
        $webhook_id = get_option( 'wpbdp-authorize-webhook-id', '' );

        // Test the webhook.
        // if ( $webhook_id ) {
        //     $response = wp_remote_get(
        //         $authorize_net_api . '/rest/v1/webhooks/' . $webhook_id,
        //         array(
        //             'timeout' => 10,
        //             'sslverify' => false,
        //             'headers' => array(
        //                 'Authorization' => $auth_header,
        //                 'Content-Type' => 'application/json'
        //             )
        //         )
        //     );
        //
        //     wpbdp_debug_e( 'test', $response );
        // }

        // Create a webhook.
        if ( ! $webhook_id ) {
            $request = wp_remote_post(
                $authorize_net_api . '/rest/v1/webhooks',
                array(
                    'timeout' => 10,
                    'sslverify' => false,
                    'headers' => array(
                        'Authorization' => $auth_header,
                        'Content-Type' => 'application/json'
                    ),
                    'body' => json_encode(
                        array(
                            'url' => $listener_url,
                            'eventTypes' => array(
                                'net.authorize.customer.subscription.created',
                                'net.authorize.customer.subscription.terminated',
                                'net.authorize.customer.subscription.cancelled',
                                'net.authorize.payment.authcapture.created'
                            )
                        )
                    )
                )
            );
            $response = json_decode( wp_remote_retrieve_body( $request ) );
            update_option( 'wpbdp-authorize-webhook-id', $response->webhookId );
        }
    }

    /**
     * @since 5.5.11
     */
    public function cancel_subscription( $listing, $subscription ) {
        $susc_id = $subscription->get_subscription_id();
        if ( ! $susc_id ) {
            return;
        }

        if ( ! $this->merchantAuthentication ) {
            $this->setup_merchant_authentication();
        }

        $status = $this->get_subscription_status( $susc_id );

        if ( $status && ! in_array( $status, array( 'canceled', 'terminated' ) ) ) {
            $refId = 'ref' . time();

            $request = new AnetAPI\ARBCancelSubscriptionRequest();
            $request->setMerchantAuthentication($this->merchantAuthentication);
            $request->setRefId($refId);
            $request->setSubscriptionId($subscriptionId);
        
            $controller = new AnetController\ARBCancelSubscriptionController($request);
        
            $response = $controller->executeWithApiResponse( $this->API_Endpoint );

            if ( ! ( $response != null && $response->getMessages()->getResultCode() == "Ok" ) ) {
                $msg = __( 'An error occurred while trying to cancel your subscription. Please try again later or contact the site administrator.', 'WPBDM' );

                if ( current_user_can( 'administrator' ) ) {
                    $msg = sprintf(
                        __( 'An error occurred while trying to cancel Authorize.net subscription with ID %s. You can try again later or cancel subscription from gateway dashboard.', 'WPBDM' ),
                        $susc_id
                    );
                }

                throw new Exception( $msg );   
            }
        }

        $subscription->cancel();
    }

    /**
     * @since 5.7.2
     */
    private function create_card( $card_number, $expiration_date, $card_code ) {
        // Create the payment data for a credit card
        $creditCard = new AnetAPI\CreditCardType();
        $creditCard->setCardNumber( $card_number );
        $creditCard->setExpirationDate( $expiration_date );
        $creditCard->setCardCode($card_code );

        return $creditCard;
    }

    /**
     * @since 5.7.2
     */
    private function create_payment_type( $creditCard ) {
        // Add the payment data to a paymentType object
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setCreditCard( $creditCard );

        return $paymentOne;
    }

    /**
     * @since 5.7.2
     */
    private function create_order( $args ) {
        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber( $args['orderInvoiceNumber'] );
        $order->setDescription( $args['orderDescription'] );

        return $order;
    }

    /**
     * @since 5.7.2
     */
    private function create_billing_address( $args ) {
        // Set the customer's Bill To address
        $customerAddress = new AnetAPI\CustomerAddressType();
        $customerAddress->setFirstName( $args['first_name'] );
        $customerAddress->setLastName( $args['last_name'] );
        $customerAddress->setAddress( trim( sprintf( '%s, %s', $args['address'], $args['address_2'] ) ) );
        $customerAddress->setCity( $args['city'] );
        $customerAddress->setState( $args['state'] );
        $customerAddress->setZip( $args['zip'] );
        $customerAddress->setCountry( $args['country'] );

        return $customerAddress;
    }

    /**
     * @since 5.7.2
     */
    private function create_or_retrieve_customer( $args ) {
        $customer_profile_id = get_post_meta( $args['listing_id'], '_authnet_customer_id', true );

        if ( ! $customer_profile_id  ) {
            $request = new AnetAPI\GetCustomerProfileRequest();
            $request->setMerchantAuthentication( $this->merchantAuthentication );
            $request->setEmail( $args['email'] );
            $controller = new AnetController\GetCustomerProfileController($request);
            $response = $controller->executeWithApiResponse( $this->API_Endpoint );

            if ( ($response != null ) && ( $response->getMessages()->getResultCode() == "Ok" ) )
            {
                $profileSelected = $response->getProfile();
                $customer_profile_id = $profileSelected->getCustomerProfileId();
            }

            if ( ! $customer_profile_id ) {
                // Create a new Customer.

                $refId = 'ref' . time();

                // Create a new CustomerPaymentProfile object
                $paymentProfile = new AnetAPI\CustomerPaymentProfileType();
                $paymentProfile->setCustomerType('individual');
                $paymentProfile->setBillTo($args['billing_address']);
                $paymentProfile->setPayment($args['payment_type']);
                $paymentProfiles[] = $paymentProfile;

                // Create a new CustomerProfileType and add the payment profile object
                $customerProfile = new AnetAPI\CustomerProfileType();
                $customerProfile->setDescription( sprintf( '%s %s', _x( 'Customer', 'authorize-net', 'WPBDM' ), get_option('blogname') ) );
                $customerProfile->setMerchantCustomerId( "M_" . time() );
                $customerProfile->setEmail( $args['email'] );
                $customerProfile->setpaymentProfiles( $paymentProfiles );

                // Assemble the complete transaction request
                $request = new AnetAPI\CreateCustomerProfileRequest();
                $request->setMerchantAuthentication($this->merchantAuthentication);
                $request->setRefId($refId);
                $request->setProfile($customerProfile);

                $controller = new AnetController\CreateCustomerProfileController($request);
                $response = $controller->executeWithApiResponse( $this->API_Endpoint );

                if ( ( $response != null ) && ( $response->getMessages()->getResultCode() == "Ok" ) ) {
                    $customer_profile_id = $response->getCustomerProfileId();
                }
            }
        }

        if ( $customer_profile_id ) {
            update_post_meta( $args['listing_id'], '_authnet_customer_id', $customer_profile_id );

            $customerData = new AnetAPI\CustomerDataType();
            $customerData->setType( 'individual' );
            $customerData->setId( $customer_profile_id );
            $customerData->setEmail( $args['email'] );

            return $customerData;
        }

        return null;
    }

    /**
     * @since 5.7.2
     */
    private function create_payment_id_reference( $args ) {
        // Add values for transaction settings
        $paymentIdSetting = new AnetAPI\UserFieldType();
        $paymentIdSetting->setName( 'wpbdp_payment_id' );
        $paymentIdSetting->setValue( $args['payment_id'] );

        return $paymentIdSetting;
    }

    /**
     * @since 5.7.2
     */
    private function create_listing_id_reference( $args ) {
        // Add values for transaction settings
        $listingIdSetting = new AnetAPI\UserFieldType();
        $listingIdSetting->setName( 'wpbdp_listing_id' );
        $listingIdSetting->setValue( $args['listing_id'] );

        return $listingIdSetting;
    }

    /**
     * @since 5.7.2
     */
    private function create_transaction_request_type( $args ) {
        $duplicateWindowSetting = new AnetAPI\SettingType();
        $duplicateWindowSetting->setSettingName("duplicateWindow");
        $duplicateWindowSetting->setSettingValue("600");

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount( $args['amount'] );
        $transactionRequestType->setOrder( $args['order'] );
        $transactionRequestType->setPayment($args['payment_type']);
        $transactionRequestType->setBillTo( $args['billing_address'] );
        $transactionRequestType->setCustomer( $args['customer'] );
        $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
        $transactionRequestType->addToUserFields( $args['setting_payment_id'] );
        $transactionRequestType->addToUserFields( $args['setting_listing_id'] );

        return $transactionRequestType;
    }

    /**
     * @since 5.7.2
     */
    private function get_subscription_status( $subscription_id ) {
        if ( ! $this->merchantAuthentication ) {
            $this->setup_merchant_authentication();
        }

        $status = null;

        $refId = 'ref' . time();

        $request = new AnetAPI\ARBGetSubscriptionStatusRequest();
        $request->setMerchantAuthentication( $this->merchantAuthentication );
        $request->setRefId( $refId );
        $request->setSubscriptionId( $subscription_id );
    
        $controller = new AnetController\ARBGetSubscriptionStatusController($request);
    
        $response = $controller->executeWithApiResponse( $this->API_Endpoint );
        
        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok"))
        {
            $status = $response->getStatus();
        }

        return $status;
    }
}
