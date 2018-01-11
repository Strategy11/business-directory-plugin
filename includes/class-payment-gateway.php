<?php

abstract class WPBDP__Payment_Gateway {

    public function __construct() {
    }

    public abstract function get_id();
    public abstract function get_title();

    public function get_logo() {
        return $this->get_title();
    }

    public function enqueue_scripts() {
    }

    public function is_enabled( $no_errors = true ) {
        $setting_on = wpbdp_get_option( $this->get_id() );
        if ( ! $no_errors )
            return $setting_on;

        return $setting_on && ! $this->validate_settings();
    }

    public function in_test_mode() {
        return wpbdp_get_option( 'payments-test-mode' );
    }

    public function get_option( $key ) {
        return wpbdp_get_option( $this->get_id() . '-' . $key );
    }

    public abstract function get_integration_method();

    public function supports( $feature ) {
        return false;
    }

    public function supports_currency( $currency ) {
        return false;
    }

    public function get_settings() {
        return array();
    }

    public function get_settings_text() {
        return '';
    }

    public function validate_settings() {
        return array();
    }

    public function process_payment( $payment ) {
        return false;
    }

    public function process_postback() {
    }

    public function refund( $payment, $data = array() ) {
    }

    public function render_form( $payment, $errors = array() ) {
        $vars = array();

        $vars['data'] = $_POST;

        foreach ( $payment->get_payer_details() as $k => $v ) {
            if ( empty( $_POST[ 'payer_' . $k ] ) && ! empty( $v ) ) {
                $vars['data'][ 'payer_' . $k ] = $v;
            }
        }

        $vars['gateway'] = $this;
        $vars['errors'] = $errors;
        $vars['payment'] = $payment;

        if ( 'form' == $this->get_integration_method() ) {
            $vars['show_cc_section'] = false;
            $vars['show_details_section'] = false;
        }

        return wpbdp_x_render( 'checkout-billing-form', $vars );
    }

    public function validate_form( $payment ) {
        $errors = array();

        $required = array( 'payer_email', 'payer_first_name' );
        if ( 'form' != $this->get_integration_method() ) {
            $required = array_merge( $required, array( 'card_number', 'cvc', 'card_name', 'exp_month', 'exp_year', 'payer_address', 'payer_city', 'payer_zip', 'payer_country' ) );
        }

        foreach ( $required as $req_field ) {
            $field_value = isset( $_POST[ $req_field ] ) ? $_POST[ $req_field ] : '';

            if ( ! $field_value ) {
                $errors[ $req_field ] = _x( 'This field is required (' . $req_field . ').', 'payment-gateway', 'WPBDM' );
            }
        }

        return $errors;
    }

    public function save_billing_data( $payment ) {
        $form = $_POST;

        foreach ( array( 'payer_email', 'payer_first_name', 'payer_last_name' ) as $k ) {
            if ( ! empty( $form[ $k ] ) )
                $payment->{$k} = $form[ $k ];
        }

        if ( ! empty( $form['payer_address'] ) )
            $payment->payer_data['address'] = $form['payer_address'];

        if ( ! empty( $form['payer_address_2'] ) )
            $payment->payer_data['address_2'] = $form['payer_address_2'];

        if ( ! empty( $form['payer_city'] ) )
            $payment->payer_data['city'] = $form['payer_city'];

        if ( ! empty( $form['payer_state'] ) )
            $payment->payer_data['state'] = $form['payer_state'];

        if ( ! empty( $form['payer_zip'] ) )
            $payment->payer_data['zip'] = $form['payer_zip'];

        if ( ! empty( $form['payer_country'] ) )
            $payment->payer_data['country'] = $form['payer_country'];

        $payment->save();
    }

    public function get_listener_url() {
        return add_query_arg( 'wpbdp-listener', $this->get_id(), home_url( 'index.php' ) );
    }

    public function cancel_subscription( $listing, $subscription ) {
        $message = __( "There was an unexpected error trying to cancel your subscription. Please contact the website's administrator mentioning this problem. The administrator should be able to cancel your subscription contacting the payment processor directly.", 'WPBDM' );
        throw new Exception( $message );
    }
}
