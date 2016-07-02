<?php
class WPBDP__Views__Checkout extends WPBDP_NView {

    private $api = null;
    private $payment = null;
    private $errors = array();

    public function __construct( $payment = null ) {
        $this->api = wpbdp_payments_api();
        $this->payment = $payment;
    }

    public function dispatch() {
        if ( ! $this->payment ) {
            $q = isset( $_REQUEST['payment'] ) ? $_REQUEST['payment'] : null;

            if ( $q ) {
                $q = urldecode( base64_decode( $q ) );
                parse_str( $q, $payment_data );

                if ( isset( $payment_data['payment_id'] ) && isset( $payment_data['verify'] ) ) { // TODO: check 'verify'.
                    $this->payment = WPBDP_Payment::get( $payment_data['payment_id'] );
                }
            }
        }

        if ( ! $this->payment )
            return wpbdp_render_msg( _x( 'Invalid payment id.', 'payments', 'WPBDM' ), 'error' );

        $step = 'gateway_selection';

        if ( $this->payment->is_rejected() || $this->payment->is_canceled() )
            $step = 'rejected';
        elseif ( ! $this->payment->is_pending() ) {
            $step = 'done';
        } else {
            if ( $this->payment->get_data( 'returned' ) )
                $step = 'pending_verification';
            elseif ( $this->payment->get_gateway() )
                $step = 'checkout';
        }

        return call_user_func( array( &$this, $step ) );
    }

    private function gateway_selection() {
        global $wpbdp;

        // Auto-select gateway if there is only one available.
        $gateways = $wpbdp->payments->get_available_methods();
        $skip_gateway_selection = false;

        if ( 1 == count( $gateways ) )
            $skip_gateway_selection = true;

        $skip_gateway_selection = apply_filters( 'wpbdp_checkout_skip_gateway_selection', $skip_gateway_selection );
        if ( $skip_gateway_selection ) {
            $this->payment->set_payment_method( array_pop( $gateways ) );
            $this->payment->save();
            return $this->checkout();
        }

        $html  = '';
        do_action_ref_array( 'wpbdp_checkout_page_process', array( &$this->payment ) );

        // Check if the payment changed in case we need to update something.
        if ( 0.0 == $this->payment->get_total() )
            return $this->dispatch();

        if ( isset( $_POST['payment_method'] ) ) {
            $payment_method = trim( $_POST['payment_method'] );

            if ( ! $payment_method || 'none' == $payment_method ) {
//                $html .= wpbdp_render_msg( _x( 'Please select a valid payment method.', 'checkout', 'WPBDM' ), 'error' );
            } else {
                $this->payment->set_payment_method( $payment_method );
                $this->payment->save();
                return $this->checkout();
            }
        }

        $html .= '<form action="' . esc_url( $this->payment->get_checkout_url() ) . '" method="POST">';
        $html .= $wpbdp->payments->render_invoice( $this->payment );
        $html .= wpbdp_capture_action_array( 'wpbdp_checkout_page_before_method_selection', array( &$this->payment ) );
        $html .= $wpbdp->payments->render_payment_method_selection( $this->payment );
        $html .= '<input type="submit" value="' . _x( 'Continue', 'checkout', 'WPBDM' ) . '" />';
        $html .= '</form>';

        return $html;
    }

    private function checkout() {
        if ( ! is_ssl() && wpbdp_get_option( 'payments-use-https' ) ) {
            return wpbdp_render_msg(
                    str_replace( '<a>',
                                 '<a href="' . esc_url( $this->payment->get_checkout_url() ) . '">',
                                 _x( 'Payments are not allowed on the non-secure version of this site. Please <a>continue to the secure server to proceed with your payment</a>.', 'checkout', 'WPBDM' ) ),
                    'error'
            );
        }

        $html  = '';
        $html .= $this->api->render_standard_checkout_page( $this->payment, array( 'retry_rejected' => true ) );

        return $html;
    }

    private function pending_verification() {
        $message = wpbdp_get_option( 'payment-message' );

        if ( ! $message )
            $message .= wpbdp_render_msg( _x( 'Your payment is being verified. This usually takes a few minutes but can take up to 24 hours.', 'checkout', 'WPBDM' ) );

        $html  = '';
        $html .= $message;
        $html .= $this->api->render_details( $this->payment );
        $html .= '<p>';
        $html .= sprintf( '<a href="%s">%s</a>',
                          wpbdp_get_page_link( 'main' ),
                          _x( '← Return to Directory.', 'checkout', 'WPBDM' ) );
        $html .= '</p>';

        return $html;
    }

    private function done() {
        $listing = WPBDP_Listing::get( $this->payment->get_listing_id() );

        $html  = '';
        $html .= wpbdp_render_msg( _x( 'Your payment was received sucessfully.', 'checkout', 'WPBDM' ) );
        $html .= $this->api->render_details( $this->payment );

        $html .= '<p>';
        if ( $listing->is_published() )
            $html .= sprintf( '<a href="%s">%s</a>',
                              $listing->get_permalink(),
                              _x( '← Return to your listing.', 'checkout', 'WPBDM' ) );
        else
            $html .= sprintf( '<a href="%s">%s</a>',
                              wpbdp_get_page_link( 'main' ),
                              _x( '← Return to Directory.', 'checkout', 'WPBDM' ) );
        $html .= '</p>';

        return $html;
    }

    private function rejected() {
        $html  = '';
        $html .= wpbdp_render_msg( implode( '<br />', $this->payment->get_data('errors') ), 'error' );
        $html .= $this->api->render_details( $this->payment );

        return $html;
    }

}
