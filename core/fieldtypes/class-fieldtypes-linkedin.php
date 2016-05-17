<?php

class WPBDP_FieldTypes_LinkedIn extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Social Site (LinkedIn profile)', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'social-linkedin';
    }

    public function setup_field( &$field ) {
        $field->add_display_flag( 'social' );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        // LinkedIn fields are rendered as normal textfields
        global $wpbdp;

        $field_settings['placeholder'] = _x( 'You can add your Company ID or profile URL here.', 'form-fields api', 'WPBDM' );

        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra, $field_settings );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        static $js_loaded = false;

        $html  = '';
        if ( ! $value )
            return $html;

        if ( is_numeric( $value ) ) {
            if ( ! $js_loaded ) {
                $html .= '<script src="//platform.linkedin.com/in.js" type="text/javascript"></script>';
                $js_loaded = true;
            }
            $html .= '<script type="IN/FollowCompany" data-id="' . intval( $value ) . '" data-counter="none"></script>';
        } else {
            if ( function_exists( 'filter_var' ) ) {
                if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
                    if ( strstr( parse_url( $value, PHP_URL_HOST ), 'linkedin.com' ) ) {
                        $html .= sprintf( '<a target="_blank" href="%s" > <img src="%s" ></a>', esc_url( $value ), WPBDP_URL . 'core/images/linkedin.png' );
                    }
                }
            }
        }

        return $html;
    }

}

