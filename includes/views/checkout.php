<?php
/**
 * Checkout view
 *
 * @package BDP/Includes/Views/Checkout
 */

// phpcs:disable

/**
 * Class WPBDP__Views__Checkout
 *
 * @SuppressWarnings(PHPMD)
 */
class WPBDP__Views__Checkout extends WPBDP__View {

    private $payment_id = 0;
    private $payment    = null;
    private $gateway    = null;

    private $errors = array();


    public function __construct( $payment = null ) {
        if ( $payment && is_object( $payment ) ) {
            $this->payment_id = $payment->id;
        } elseif ( is_numeric( $payment ) ) {
            $this->payment_id = absint( $payment );
        }
    }

    public function enqueue_resources() {
        foreach ( wpbdp()->payment_gateways->get_available_gateways() as $gateway ) {
            $gateway->enqueue_scripts();
        }

        wp_enqueue_script( 'wpbdp-checkout' );
    }

    public function ajax_load_gateway() {
        $this->pre_dispatch();

        if ( $this->can_checkout() ) {
            echo $this->checkout_form();
        }
        exit;
    }

    public function dispatch() {
        $this->pre_dispatch();

        if ( $this->is_successful_initial_payment() ) {
            $args = array(
                'listing_id' => $this->payment->listing_id,
            );

            $this->_redirect( add_query_arg( $args, wpbdp_url( 'submit_listing' ) ) );
        }

        if ( ! $this->can_checkout() ) {
            return $this->thank_you_message();
        }

        $action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

        if ( has_action( 'wpbdp_checkout_before_action' ) ) {
            // Lightweight object used to pass checkout state to modules.
            // Eventually, we might want to pass $this directly with a better get/set interface.
            $checkout          = new StdClass();
            $checkout->payment = $this->payment;
            $checkout->gateway = $this->gateway;
            $checkout->errors  = array();

            do_action( 'wpbdp_checkout_before_action', $checkout );

            $this->errors = array_merge( $this->errors, $checkout->errors );
        }

        if ( ! $this->errors ) {
            if ( 'do_checkout' == $action ) {
                $this->do_checkout();

                // Let's see if the checkout process changed the payment status to something we can no longer handle.
                $this->fetch_payment();

                if ( ! $this->can_checkout() ) {
                    return $this->_redirect( $this->payment->checkout_url );
                }
            } elseif ( 'return' == $action ) {
                return $this->handle_return_request();
            }
        }

        if ( ! empty( $_POST ) ) {
            $_POST = stripslashes_deep( $_POST );
        }

        $vars['_bar']                 = false;
        $vars['errors']               = $this->errors;
        $vars['invoice']              = wpbdp()->payments->render_invoice( $this->payment );
        $vars['chosen_gateway']       = $this->gateway;
        $vars['checkout_form_top']    = wpbdp_capture_action( 'wpbdp_checkout_form_top', $this->payment );
        $vars['checkout_form']        = $this->checkout_form();
        $vars['checkout_form_bottom'] = wpbdp_capture_action( 'wpbdp_checkout_form_bottom', $this->payment );
        $vars['payment']              = $this->payment;
        $vars['nonce']                = wp_create_nonce( 'wpbdp-checkout-' . $this->payment->id );

        return $this->_render_page( 'checkout', $vars );
    }

    private function is_successful_initial_payment() {
        if ( 'initial' !== $this->payment->payment_type ) {
            return false;
        }

        if ( 'completed' !== $this->payment->status ) {
            return false;
        }

        return $this->payment->gateway ? true : false;
    }

    private function can_checkout() {
        return ( 'pending' == $this->payment->status && ! $this->payment->gateway );
    }

    private function pre_dispatch() {
        $this->fetch_payment();

        if ( ! wpbdp()->payment_gateways->can_pay() && 0 < $this->payment->amount ) {
            wp_die( _x( 'Can not process a payment at this time. Please try again later.', 'checkout', 'WPBDM' ) );
        }

        // We don't set gateway and validate nonce for non-pending payments or pending with already a gateway set.
        if ( ! $this->can_checkout() ) {
            return;
        }

        $this->validate_nonce();
        $this->set_current_gateway();
    }

    private function fetch_payment() {
        if ( ! $this->payment_id && ! empty( $_REQUEST['payment'] ) ) {
            $this->payment = WPBDP_Payment::objects()->get( array( 'payment_key' => $_REQUEST['payment'] ) );
        } elseif ( $this->payment_id ) {
            $this->payment = WPBDP_Payment::objects()->get( $this->payment_id );
        }

        if ( ! $this->payment ) {
            wp_die( 'Invalid Payment ID/key' );
        }

        $this->payment_id = $this->payment->id;
    }

    private function validate_nonce() {
        if ( ! $_POST ) {
            return;
        }

        // Return URL for PayPal and other gateways include the nonce in the query
        // string while form submissions include it as a POST parameter. We use
        // $_REQUEST to handle both cases.
        $nonce = ! empty( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

        if ( ! wp_verify_nonce( $nonce, 'wpbdp-checkout-' . $this->payment_id ) ) {
            wp_die( _x( 'Invalid nonce received.', 'checkout', 'WPBDM' ) );
        }
    }

    private function set_current_gateway() {
        $chosen_gateway = '';

        if ( ! empty( $_REQUEST['gateway'] ) ) {
            $chosen_gateway = $_REQUEST['gateway'];
        } elseif ( $this->payment->gateway ) {
            $chosen_gateway = $this->payment->gateway;
        } else {
            $gateway_ids    = array_keys( wpbdp()->payment_gateways->get_available_gateways( array( 'currency_code' => $this->payment->currency_code ) ) );
            $chosen_gateway = array_shift( $gateway_ids );
        }

        if ( ! wpbdp()->payment_gateways->can_use( $chosen_gateway ) ) {
            wp_die( _x( 'Invalid gateway selected.', 'checkout', 'WPBDM' ) );
        }

        $this->gateway = wpbdp()->payment_gateways->get( $chosen_gateway );
        if ( ! $this->gateway->supports_currency( $this->payment->currency_code ) ) {
            wp_die( _x( 'Selected gateway does not support payment\'s currency.', 'checkout', 'WPBDM' ) );
        }
    }

    private function checkout_form() {
        $checkout_form = '';
        // $checkout_form .= wpbdp_capture_action( 'wpbdp_checkout_form_top', $this->payment );
        $checkout_form .= $this->gateway->render_form( $this->payment, $this->errors );
        // $checkout_form .= wpbdp_capture_action( 'wpbdp_checkout_form_bottom', $this->payment );
        $checkout_form .= '<div class="wpbdp-checkout-submit"><input type="submit" value="' . _x( 'Pay Now', 'checkout', 'WPBDM' ) . '" /></div>';

        return $checkout_form;
    }

    private function do_checkout() {
        if ( ! $this->gateway ) {
            wp_die();
        }

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

        if ( 'success' == $res['result'] && ! empty( $res['redirect'] ) ) {
            return $this->_redirect( $res['redirect'] );
        }

        if ( 'success' == $res['result'] ) {
            $this->payment->gateway = $this->gateway->get_id();
            $this->payment->save();

            return $this->_redirect( $this->payment->checkout_url );
        }

        if ( 'pending' != $this->payment->status ) {
            $this->payment->gateway = $this->gateway->get_id();
        }

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

    private function thank_you_message() {
        $vars = array(
            'payment' => $this->payment,
            'status'  => $this->payment->status,
            '_bar'    => false,
        );
        return $this->_render_page( 'checkout-confirmation', $vars );
    }

    private function handle_return_request() {
        if ( ! $this->gateway ) {
            wp_die( __( 'There was an error trying to process your request. No gateway is selected.', 'checkout', 'WPBDM' ) );
        }

        $this->payment->gateway = $this->gateway->get_id();
        $this->payment->save();

        return $this->_redirect( $this->payment->get_checkout_url() );
    }
}
