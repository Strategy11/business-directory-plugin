<?php
class WPBDP_FieldTypes_URL extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x( 'URL Field', 'form-fields api', 'WPBDM' ) );
    }

    public function get_id() {
        return 'url';
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        if ( $association != 'meta' )
            return '';

        $settings = array();

        $settings['new-window'][] = _x( 'Open link in a new window?', 'form-fields admin', 'WPBDM' );
        $settings['new-window'][] = '<input type="checkbox" value="1" name="field[x_open_in_new_window]" ' . ( $field && $field->data( 'open_in_new_window' ) ? ' checked="checked"' : '' ) . ' />';

        $settings['nofollow'][] = _x( 'Use rel="nofollow" when displaying the link?', 'form-fields admin', 'WPBDM' );
        $settings['nofollow'][] = '<input type="checkbox" value="1" name="field[x_use_nofollow]" ' . ( $field && $field->data( 'use_nofollow' ) ? ' checked="checked"' : '' ) . ' />';

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        if ( array_key_exists( 'x_open_in_new_window', $_POST['field'] ) ) {
            $open_in_new_window = (bool) intval( $_POST['field']['x_open_in_new_window'] );
            $field->set_data( 'open_in_new_window', $open_in_new_window );
        }

        if ( array_key_exists( 'x_use_nofollow',  $_POST['field'] ) ) {
            $use_nofollow = (bool) intval( $_POST['field']['x_use_nofollow'] );
            $field->set_data( 'use_nofollow', $use_nofollow );
        }
    }

    public function setup_field( &$field ) {
        $field->add_validator( 'url' );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        if ( $value === null )
            return array( '', '' );

        if ( !is_array( $value ) )
            return array( $value, $value );

        if ( !isset( $value[1] ) || empty( $value[1] ) )
            $value[1] = $value[0];

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( empty( $value ) || empty( $value[0] ) )
            return '';


        return sprintf( '<a href="%s" rel="%s" target="%s" title="%s">%s</a>',
                        esc_url( $value[0] ),
                        $field->data( 'use_nofollow' ) == true ? 'nofollow': '',
                        $field->data( 'open_in_new_window' ) == true ? '_blank' : '_self',
                        esc_attr( $value[1] ),
                        esc_attr( $value[1] ) );
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );
        return $value[0];
    }

    public function convert_csv_input( &$field, $input = '', $import_settings = array() ) {
        $input = str_replace( array( '"', '\'' ), '', $input );
        $input = str_replace( ';', ',', $input ); // Support ; as a separator here.
        $parts = explode( ',', $input );

        if ( 1 == count( $parts ) )
            return array( $parts[0], $parts[0] );

        return array( $parts[0], $parts[1] );
    }

    public function get_field_csv_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( is_array( $value ) && count( $value ) > 1 ) {
            return sprintf( '%s,%s', $value[0], $value[1] );
        }

        return is_array( $value ) ? $value[0] : '';
    }

    public function convert_input( &$field, $input ) {
        if ( $input === null )
            return array( '', '' );

        $url = trim( is_array( $input ) ? $input[0] : $input );
        $text = trim( is_array( $input ) ? $input[1] : $url );

        if ( $url && ! parse_url( $url, PHP_URL_SCHEME ) )
            $url = 'http://' . $url;

        return array( $url, $text );
    }

    public function is_empty_value( $value ) {
        return empty( $value[0] );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( !is_array( $value ) || $value[0] == '' )
            $value = null;

        parent::store_field_value( $field, $post_id, $value );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        if ( $context == 'search' ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value[0], $context, $extra, $field_settings );
        }

        $html  = '';
        $html .= sprintf( '<span class="sublabel">%s</span>', _x( 'URL:', 'form-fields api', 'WPBDM' ) );
        $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" />',
                          'wpbdp-field-' . $field->get_id(),
                          'listingfields[' . $field->get_id() . '][0]',
                          $field->is_required() ? 'inselect required' : 'inselect',
                          esc_attr( $value[0] ) );

        $html .= sprintf( '<span class="sublabel">%s</span>', _x( 'Link Text (optional):', 'form-fields api', 'WPBDM' ) );
        $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox" value="%s" placeholder="" />',
                          'wpbdp-field-' . $field->get_id() . '-title',
                          'listingfields[' . $field->get_id() . '][1]',
                          esc_attr( $value[1] ) );

        return $html;
    }

}
