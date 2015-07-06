<?php

abstract class WPBDP_Payment_Gateway {

    const INTEGRATION_BUTTON = 'button';
    const INTEGRATION_FORM = 'form';
    const CAPABILITIES_RECURRING = 'recurring';

    public function __construct() {
    }

    public function get_id() {
        return '';
    }

    public function get_gateway_url( $args = array() ) {
        return add_query_arg( array_merge( array( 'wpbdpx' => 'payments',
                                                  'action' => 'postback',
                                                  'gid' => $this->get_id() ),
                                           $args ),
                              home_url('/') );
    }

    public function get_url( &$payment, $action = '' ) {
        // TODO: support pretty URLs
        return add_query_arg( array( 'wpbdpx' => 'payments',
                                     'action' => $action,
                                     'payment_id' => $payment->get_id() ),
                              home_url( '/' ) );
    }

    public function get_name() {
        $classname = get_class( $this );
        $classname = str_replace( 'WPBDP' , '', $classname );
        $classname = str_replace( '_' , ' ', $classname );
        $classname = trim( $classname );

        return $classname;
    }

    public function register_config( &$settings ) { }
    abstract public function validate_config();

    public function get_supported_currencies() {
        return array();
    }

    abstract public function get_integration_method();

    public function get_capabilities() {
        return array();
    }

    /**
     * @since 3.5.8
     */
    public function has_capability( $cap ) {
        return in_array( $cap, $this->get_capabilities(), true );
    }

    public function render_unsubscribe_integration( &$category, &$listing) {}

    public function setup_payment( &$payment ) {}

    public function process_generic( $action = '' ) {
        return;
    }

    abstract public function process( &$payment, $action );
    abstract public function render_integration( &$payment );

    /**
     * @since 3.5.8
     */
    public function render_billing_information_form( &$payment, $args = array() ) {
        $defaults = array(
            'action' => $this->get_url( $payment, 'process' ),
            'posted' => $payment->get_data( 'billing-information' ),
            'errors' => $payment->get_data( 'validation-errors' ),
        );
        $args = wp_parse_args( $args, $defaults );
        $args['payment'] = $payment;

        // Clear errors.
        $payment->set_data( 'billing-information', false );
        $payment->set_data( 'validation-errors', false );
        $payment->save();

        return wpbdp_render( 'billing-information-form', $args );
    }

    /**
     * @since 3.5.8
     */
    public function sanitize_billing_information( $data ) {
        $fields = array(
            'first_name',
            'last_name',
            'cc_number',
            'cc_exp_month',
            'cc_exp_year',
            'cc_cvc',
            'address_country',
            'address_state',
            'address_city',
            'address_line1',
            'address_line2',
            'zipcode'
        );

        $sanitized_data = array();

        foreach ( $fields as $f )
            $sanitized_data[ $f ] = ! empty( $data[ $f ] ) ? trim( $data[ $f ] ) : '';

        if ( 2 == strlen( $sanitized_data['cc_exp_year'] ) )
            $sanitized_data['cc_exp_year'] = '20' . $sanitized_data['cc_exp_year'];

        return $sanitized_data;
    }

    /**
     * @since 3.5.8
     */
    public function validate_billing_information( &$payment ) {
        $errors = array();

        $data = $this->sanitize_billing_information( stripslashes_deep( $_POST ) );

        if ( ! $data['first_name'] )
            $errors[] = _x( 'First name is required.', 'billing info', 'WPBDM' );

        if ( ! $data['last_name'] )
            $errors[] = _x( 'Last name is required.', 'billing info', 'WPBDM' );

        if ( ! $data['cc_number'] )
            $errors[] = _x( 'Credit card number is required.', 'billing info', 'WPBDM' );

        if ( ! $data['cc_exp_month'] || ! $data['cc_exp_year'] )
            $errors[] = _x( 'Credit card expiration date is invalid.', 'billing info', 'WPBDM' );

        if ( ! $data['cc_cvc'] )
            $errors[] = _x( 'Credit card CVC number is required.', 'billing info', 'WPBDM' );

        if ( ! $data['address_country'] )
            $errors[] = _x( 'Country is required.', 'billing info', 'WPBDM' );

        if ( ! $data['address_line1'] && ! $data['address_line2'] )
            $errors[] = _x( 'Address is required.', 'billing info', 'WPBDM' );

        $payment->set_data( 'billing-information', $data );
        $payment->set_data( 'validation-errors', $errors );
        $payment->save();

        return empty( $errors );
    }

}
