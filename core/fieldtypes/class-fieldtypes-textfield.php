<?php
class WPBDP_FieldTypes_TextField extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Textfield', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textfield';
    }

    public function convert_input( &$field, $input ) {
        $input = strval( $input );

        if ( $field->get_association() == 'tags' ) {
            $input = str_replace( ';', ',', $input );
            return explode( ',', $input );
        }

        return $input;
    }

    public function get_field_value( &$field, $value ) {
        $value = parent::get_field_value( $field, $value );

        if ( $field->get_association() == 'tags' ) {
            $tags = implode( ',', $value );
            return $tags;
        }

        return $value;
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        if ( is_array( $value ) )
            $value = implode( ',', $value );

        $html = '';

        if ( $field->has_validator( 'date' ) )
            $html .= _x( 'Format 01/31/1969', 'form-fields api', 'WPBDM' );

        if ( isset( $field_settings['html_before'] ) )
            $html .= $field_settings['html_before'];

        $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" %s />',
                          'wpbdp-field-' . $field->get_id(),
                          'listingfields[' . $field->get_id() . ']',
                          $field->is_required() ? 'inselect required' : 'inselect',
                          esc_attr( $value ),
                          ( isset( $field_settings['placeholder'] ) ? sprintf( 'placeholder="%s"', esc_attr( $field_settings['placeholder'] ) ) : '' ) );

        return $html;
    }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'tags', 'meta' );
    }

}
