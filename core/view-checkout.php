<?php
require_once( WPBDP_PATH . 'core/class-view.php' );

class WPBDP_Checkout_Page extends WPBDP_View {

    private $api = null;
    private $payment = null;
    private $errors = array();

    public function __construct( &$payment = null ) {
        $this->api = wpbdp_payments_api();
        $this->payment = $payment;
    }

    public function get_page_name() {
        return 'checkout';
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

        if ( ! $this->payment->is_pending() ) {
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
        $html  = '';

        global $wpbdp;

        if ( isset( $_POST['payment_method'] ) ) {
            $payment_method = trim( $_POST['payment_method'] );

            if ( ! $payment_method ) {
                $html .= wpbdp_render_msg( _x( 'Please select a valid payment method.', 'checkout', 'WPBDM' ), 'error' );
            } else {
                $this->payment->set_payment_method( $payment_method );
                $this->payment->save();
                return $this->checkout();
            }

        }

        $html .= '<form action="' . esc_url( $this->payment->get_checkout_url() ) . '" method="POST">';
        $html .= $wpbdp->payments->render_invoice( $this->payment );
        $html .= $wpbdp->payments->render_payment_method_selection( $this->payment );
        $html .= '<input type="submit" value="Continue" />';
        $html .= '</form>';

        return $html;
    }

    private function checkout() {
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

}
