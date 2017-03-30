<?php
class WPBDP__Views__Checkout extends WPBDP__View {

    private $payment_id = 0;
    private $payment = null;
    private $gateway = null;

    private $errors = array();


    public function __construct( $payment = null ) {
        if ( $payment && is_object( $payment ) ) {
            $this->payment_id = $payment->id;
        } elseif ( is_numeric( $payment ) ) {
            $this->payment_id = absint( $payment );
        }
    }

    public function dispatch() {
        if ( ! wpbdp()->payment_gateways->can_pay() )
            wp_die( _x( 'Can not process a payment at this time. Please try again later.', 'checkout', 'WPBDM' ) );

        $this->fetch_payment();
        $this->validate_nonce();
        $this->set_current_gateway();

        $action = ! empty( $_POST['action'] ) ? $_POST['action'] : '';

        if ( has_action( 'wpbdp_checkout_before_action' ) ) {
            // Lightweight object used to pass checkout state to modules.
            // Eventually, we might want to pass $this directly with a better get/set interface.
            $checkout = new StdClass;
            $checkout->payment = $this->payment;
            $checkout->gateway = $this->gateway;
            $checkout->errors = array();

            do_action( 'wpbdp_checkout_before_action', $checkout );

            $this->errors = array_merge( $this->errors, $checkout->errors );
        }

        if ( ! $this->errors ) {
            if ( 'do_checkout' == $action ) {
                $this->do_checkout();

                // Let's see if the checkout process changed the payment status to something we can no longer handle.
                $this->fetch_payment();
            }
        }

        return $this->checkout_form();
    }

    private function fetch_payment() {
        if ( ! $this->payment_id && ! empty( $_REQUEST['payment'] ) ) {
            $this->payment = WPBDP_Payment::objects()->get( array( 'payment_key' => $_REQUEST['payment'] ) );
            $this->payment_id = $this->payment->id;
        } elseif ( $this->payment_id ) {
            $this->payment = WPBDP_Payment::objects()->get( $this->payment_id );
        }

        if ( ! $this->payment ) {
            wp_die( 'Invalid Payment ID/key' );
        }

        switch ( $this->payment->status ) {
        case 'completed':
            wp_die( 'Order completed!' );
            break;
        case 'on-hold':
            wp_die('Order is on hold!');
            break;
        case 'failed':
            wp_die('Order failed!');
            break;
        case 'refunded':
            wp_die('Order refunded');
            break;
        case 'canceled':
            wp_die('Order canceled by user');
            break;
        case 'pending':
            if ( $this->payment->gateway )
                wp_die('Maybe awaiting confirmation from gateway?');
        default:
            break;
        }
    }

    private function validate_nonce() {
        if ( ! $_POST )
            return;

        $nonce = ! empty( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';

        if ( ! wp_verify_nonce( $nonce, 'wpbdp-checkout-' . $this->payment_id ) )
            wp_die( _x( 'Invalid nonce received.', 'checkout', 'WPBDM' ) );
    }

    private function set_current_gateway() {
        $chosen_gateway = '';

        if ( ! empty( $_REQUEST['gateway'] ) ) {
            $chosen_gateway = $_REQUEST['gateway'];
        } elseif ( $this->payment->gateway ) {
            $chosen_gateway = $this->payment->gateway;
        } else {
            $gateway_ids = array_keys( wpbdp()->payment_gateways->get_available_gateways( array( 'currency_code' => $this->payment->currency_code ) ) );
            $chosen_gateway = array_shift( $gateway_ids );
        }

        if ( ! wpbdp()->payment_gateways->can_use( $chosen_gateway ) ) {
            wp_die( _x( 'Invalid gateway selected.', 'checkout', 'WPBDM' ) );
        }

        $this->gateway = wpbdp()->payment_gateways->get( $chosen_gateway );
        if ( ! $this->gateway->supports_currency( $this->payment->currency_code ) )
            wp_die( _x( 'Selected gateway does not support payment\'s currency.', 'checkout', 'WPBDM' ) );
    }

    private function checkout_form() {
        if ( ! empty( $_POST ) ) {
            $_POST = stripslashes_deep( $_POST );
        }

        // if ( ! empty( $this->payment->data['checkout_errors'] ) ) {
        //     $vars['errors'] = $this->payment->data['checkout_errors'];
        // } else {
        //     $vars['errors'] = array();
        // }
        //
        // // Clear errors.
        // $this->payment->data['checkout_errors'] = array();
        // $this->payment->save(); $this->payment->refresh();

        $invoice = wpbdp()->payments->render_invoice( $this->payment );
        $checkout_form = $this->gateway->render_form( $this->payment, $this->errors );

        $vars['_bar'] = false;
        $vars['errors'] = $this->errors;
        $vars['invoice'] = $invoice;
        $vars['chosen_gateway'] = $this->gateway;
        $vars['checkout_form'] = $checkout_form;
        $vars['payment'] = $this->payment;

        return $this->_render_page( 'checkout', $vars );
    }

    private function do_checkout() {
        if ( ! $this->gateway )
            wp_die();

        // Allows short-circuiting of validation.
        $validation_errors = $this->gateway->validate_form( $this->payment );
        $validation_errors = apply_filters( 'wpbdp_checkout_validation_errors', $validation_errors, $this->payment );
        if ( $validation_errors ) {
            $this->errors = $validation_errors;
            return;
        }

        // Save customer data.
        $this->gateway->save_billing_data( $this->payment );
        $this->payment->refresh();

        $res = (array) $this->gateway->process_payment( $this->payment );

        if ( 'success' == $res['result'] ) {
            $this->payment->gateway = $this->gateway->get_id();
            $this->payment->save();

            if ( isset( $res['redirect'] ) )
                return $this->_redirect( $res['redirect'] );

            return $this->_redirect( $this->payment->checkout_url );
        }

        if ( 'pending' != $this->payment->status )
            $payment->gateway = $this->gateway->get_id();

        // Update payment with changes from the gateway.
        $this->payment->save();

        // If payment failed, let's see if the payment can be continued (maybe data was entered wrong) or we definitely
        // got a rejected transaction.
        if ( ! empty( $res['error'] ) ) {
            $this->errors = array_merge( $this->errors, array( $res['error'] ) );
        } else {
            $this->errors[] = _x( 'Unknown gateway error.', 'checkout', 'WPBDM' );
        }

        // Forget about the card (just in case).
        unset( $_POST['card_number'] );
        unset( $_POST['cvc'] );
        unset( $_POST['card_name'] );
    }
}
