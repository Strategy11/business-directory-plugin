<?php
/**
 * @package WPBDP\FieldTypes\Textfield
 */

// phpcs:disable
/**
 * @SuppressWarnings(PHPMD)
 */
class WPBDP_FieldTypes_TextField extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x( 'Textfield', 'form-fields api', 'WPBDM' ) );
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

        return sanitize_text_field( $input );
    }

    public function get_field_value( &$field, $value ) {
        $value = parent::get_field_value( $field, $value );

        if ( $field->get_association() == 'tags' ) {
            $tags = implode( ',', $value );
            return $tags;
        }

        return $value;
    }

    public function render_field_inner( &$field, $value, $context, &$extra = null, $field_settings = array() ) {
        if ( is_array( $value ) ) {
            $value = implode( ',', $value );
        }

        $html = '';

        if ( $field->has_validator( 'date' ) ) {
            $html .= _x( 'Format 01/31/1969', 'form-fields api', 'WPBDM' );
        }

        if ( isset( $field_settings['html_before'] ) ) {
            $html .= $field_settings['html_before'];
        }

        $html .= sprintf(
            '<input type="text" id="%s" name="%s" value="%s" %s />',
            'wpbdp-field-' . $field->get_id(),
            'listingfields[' . $field->get_id() . ']',
            esc_attr( $value ),
            ( isset( $field_settings['placeholder'] ) ? sprintf( 'placeholder="%s"', esc_attr( $field_settings['placeholder'] ) ) : '' )
        );

        return $html;
    }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'tags', 'meta' );
    }

    public function process_field_settings( &$field ) {
        $field->set_data( 'word_count', ( in_array( 'word_number', $field->get_validators() ) && isset( $_POST['field']['word_count'] ) ) ? intval( $_POST['field']['word_count'] ) : 0 );
    }

    /**
     * @since 5.5.1
     */
    public function get_field_csv_value( &$field, $post_id ) {
        return sanitize_text_field( $this->get_field_plain_value( $field, $post_id ) );
    }

}
