<?php

/**
 * @since 3.6.5
 */
class WPBDP_FieldTypes_Date extends WPBDP_FieldTypes_TextField {

    public function get_name() {
        return _x( 'Date Field', 'form-fields api', 'WPBDM' );
    }

    public function get_id() {
        return 'date';
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function render_field_settings(  &$field = null, $association = null) {
        if ( 'meta' != $association )
            return '';

        $now = current_time( 'timestamp' );
        $current_format = $this->date_format( $field );

        $select = '';
        foreach ( $this->get_formats() as $format => $data ) {
            $select .= sprintf( '<label><input type="radio" name="field[x_date_format]" value="%s" %s />%s</label><br />',
                                $format,
                                checked ( $format, $current_format, false ),
                                sprintf( __( '%s (ex. %s)', 'form-fields api', 'WPBDM' ), strtoupper( $format ), date( $data['date_format'], $now ) ) );
        }

        $settings = array(
            'date_format' => array( _x( 'Date Format', 'form-fields api', 'WPBDM' ),
                                    $select )
        );

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        if ( ! array_key_exists( 'x_date_format', $_POST['field'] ) )
            return;

        $date_format = $_POST['field']['x_date_format'];
        $field->set_data( 'date_format', $date_format );
    }

    public function setup_field( &$field ) {
        $field->add_validator( 'date_' );
    }

    public function setup_validation( $field, $validator, $value ) {
        if ( 'date_' != $validator )
            return;

        $args = array();
        $args['format'] = 'yyyymmdd';
        $args['messages'] = array( 'incorrect_format' => sprintf( _x( '%s must be in the format %s.', 'date field', 'WPBDM' ),
                                                                  esc_attr( $field->get_label() ),
                                                                  $this->date_format( $field ) ),
                                   'invalid' => sprintf( _x( '%s must be a valid date.', 'date field', 'WPBDM' ),
                                                         esc_attr( $field->get_label() ) ) );
        return $args;
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        static $enqueued = false;

        if ( ! $enqueued ) {
            wp_enqueue_style( 'wpbdp-jquery-ui-css',
                              'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.21/themes/smoothness/jquery-ui.css' );
            wp_enqueue_script( 'jquery-ui-datepicker', false, false, false, true );
            $enqueued = true;
        }

        $format = $this->date_format( $field, true );

        $html = '';
        $html .= sprintf( '<input type="text" name="%s" class="intextbox %s %s" value="%s" data-date-format="%s" />',
                          'listingfields[' . $field->get_id() . ']',
                          'wpbdp-field-' . $field->get_id(),
                          $field->is_required() ? 'inselect required' : 'inselect',
                          $value ? date( $format['date_format'], strtotime( $value ) ) : '',
                          $format['datepicker_format'] );

        wp_enqueue_script( 'jquery-ui-datepicker', false, false, false, true );

        return $html;
    }

    public function convert_input( &$field, $input ) {
        return $this->date_to_storage_format( $field, $input );
    }

    public function date_to_storage_format( &$field, $value ) {
        $value = preg_replace('/[^0-9]/','', $value); // Normalize value.
        $format = str_replace( array( '/', '.', '-' ), '', $this->date_format( $field ) );

        if ( ! $value || strlen( $format ) != strlen( $value ) )
            return null;

        $d = 0; $m = 0; $y = 0;

        switch ( $format ) {
            case 'ddmmyy':
                $d = substr( $value, 0, 2 );
                $m = substr( $value, 2, 2 );
                $y = substr( $value, 4, 2 );
                break;
            case 'ddmmyyyy':
                $d = substr( $value, 0, 2 );
                $m = substr( $value, 2, 2 );
                $y = substr( $value, 4, 4 );
                break;
            case 'mmddyy':
                $m = substr( $value, 0, 2 );
                $d = substr( $value, 2, 2 );
                $y = substr( $value, 4, 2 );
                break;
            case 'mmddyyyy':
                $m = substr( $value, 0, 2 );
                $d = substr( $value, 2, 2 );
                $y = substr( $value, 4, 4 );
                break;
            default:
                break;
        }

        if ( strlen( $y ) < 4 ) {
            $y_ = intval( $y );

            if ( $y_ < 0 )
                $y = '19' . $y;
            else
                $y = '20' . $y;
        }

        $value = sprintf( "%'.04d%'.02d%'.02d", $y, $m, $d );
        return $value;
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( 'meta' != $field->get_association() )
            return false;

//        $val = $this->date_to_storage_format( $field, $value );
        return parent::store_field_value( $field, $post_id, $value );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        if ( empty( $value ) )
            return '';

        return $value;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( empty( $value ) )
            return '';

        $format = $this->date_format( $field, true );
        $y = substr( $value, 0, 4 );
        $m = substr( $value, 4, 2 );
        $d = substr( $value, 6, 2 );

        return date( $format['date_format'], strtotime( $y . '-' . $m . '-' . $d ) );
    }

    public function get_field_html_value( &$field, $post_id ) {
        return $this->get_field_plain_value( $field, $post_id );
    }

    private function get_formats() {
        $formats = array();

        $formats['dd/mm/yy'] = array( 'date_format' => 'd/m/y', 'datepicker_format' => 'dd/mm/y' );
        $formats['dd.mm.yy'] = array( 'date_format' => 'd.m.y', 'datepicker_format' => 'dd.mm.y' );

        $formats['dd/mm/yyyy'] = array( 'date_format' => 'd/m/Y', 'datepicker_format' => 'dd/mm/yy' );
        $formats['dd.mm.yyyy'] = array( 'date_format' => 'd.m.Y', 'datepicker_format' => 'dd.mm.yy' );

        $formats['mm/dd/yy'] = array( 'date_format' => 'm/d/y', 'datepicker_format' => 'mm/dd/y' );
        $formats['mm/dd/yyyy'] = array( 'date_format' => 'm/d/Y', 'datepicker_format' => 'mm/dd/yy' );

        return $formats;
    }

    private function date_format( &$field, $full_info = false ) {
        if ( $full_info ) {
            $formats = $this->get_formats();
            $format = $this->date_format( $field, false );

            return $formats[ $format ];
        }

        if ( ! $field || ! $field->data( 'date_format' ) || ! array_key_exists( $field->data( 'date_format' ), $this->get_formats() ) )
            return 'dd/mm/yyyy';

        return $field->data( 'date_format' );
    }

}
