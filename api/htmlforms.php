<?php
/*
 * Validation.
 */

function WPBDP_ValidationError( $msg, $stop_validation=false ) {
	if ( $stop_validation )
		return new WP_Error( 'wpbdp-validation-error-stop', $msg );

	return new WP_Error( 'wpbdp-validation-error', $msg );
}

class WPBDP_FieldValidation {

	private static $instance = null;

	public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

	public function validate_value( $value, $validator, $args=array() ) {
		$dummyfield = new StdClass();
		$dummyfield->label = 'Unlabeled Field';
		$dummyfield->data = $value; 
		
		$res = self::validate_field( $dummyfield, $validator, $args );

		if ( is_wp_error( $res ) )
			return false;

		return true;
	}

	public function validate_field( $field, $validator, $args=array() ) {
		return call_user_func( array( $this, $validator ) , $field, $args );
	}

	/* Required validator */
	private function required( $field, $args=array() ) {
		$args = wp_parse_args( $args, array( 'allow_whitespace' => false ) );

		if ( !$field->data || ( is_string( $field->data ) && !$args['allow_whitespace'] && !trim( $field->data ) ) )
			return WPBDP_ValidationError( sprintf( _x( '%s is required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
	}

	/* URL Validator */
	private function url( $field, $args=array() ) {
		if ( !preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $field->data ) )
			return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid URL format required. Include http://', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) )  );
	}

    /* EmailValidator */
    private function email( $field, $args=array() ) {
    	if ( !wpbusdirman_isValidEmailAddress( $field->data ) )
			return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid Email format required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
    }

	/* IntegerNumberValidator */
	private function integer_number( $field, $args=array() ) {
		if ( !ctype_digit( $field->data ) )
			return WPBDP_ValidationError( sprintf( _x( '%s must be a number. Decimal values are not allowed.', 'form-fields-api validation', 'WPBDM' ), esc_att ( $field->label) ) );
	}

    /* DecimalNumberValidator */
    private function decimal_number( $field, $args=array() ) {
    	if ( !is_numeric( $field->data ) )
        	return WPBDP_ValidationError( sprintf( _x( '%s must be a number.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label) ) );
    }

    /* DateValidator */
    private function date_( $field, $args=array() ) {
    	$args = wp_parse_args( $args, array( 'format' => 'm/d/Y' ) );

    	$value = $field->data;

    	// TODO: validate with format
    	list( $m, $d, $y ) = explode( '/', $value );

    	if ( !is_numeric( $m ) || !is_numeric( $d ) || !is_numeric( $y ) || !checkdate( $m, $d, $y ) )
    		return WPBDP_ValidationError( sprintf( _x( '%s must be in the format MM/DD/YYYY.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
    }

    private function any_of( $field, $args=array() ) {
    	$args = wp_parse_args( $args, array( 'values' => array(), 'formatter' => create_function( '$x', 'return join(",", $x);' ) ) );
    	extract( $args, EXTR_SKIP );

    	if ( is_string( $values ) )
    		$values = explode( ',', $values );

    	if ( !in_array( $field->data, $values ) )
    		return WPBDP_ValidationError( sprintf( _x( '%s is invalid. Value most be one of %s.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ), call_user_func( $formatter, $values ) ) );    	
    }

}
