<?php

class WPBDP_FieldTypes_Image extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x( 'Image (file upload)', 'form-fields api', 'WPBDM' ) );
    }

    public function get_id() {
        return 'image';
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function setup_field( &$field ) {
        $field->remove_display_flag( 'search' ); // image fields are not searchable
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        if ( $context == 'search' )
            return '';

        $html = '';
        $html .= sprintf( '<input type="hidden" name="listingfields[%d]" value="%s" />',
                          $field->get_id(),
                          $value
                        );

        $html .= '<div class="preview">';
        if ($value)
            $html .= wp_get_attachment_image( $value, 'wpbdp-thumb', false );

        $html .= sprintf( '<a href="http://google.com" class="delete" onclick="return WPBDP.fileUpload.deleteUpload(%d);" style="%s">%s</a>',
                          $field->get_id(),
                          !$value ? 'display: none;' : '',
                          _x( 'Remove', 'form-fields-api', 'WPBDM' )
                        );

        $html .= '</div>';

        $ajax_url = add_query_arg( array( 'action' => 'wpbdp-file-field-upload',
                                                      'field_id' => $field->get_id(),
                                                      'element' => 'listingfields[' . $field->get_id() . ']' ),
                                   admin_url( 'admin-ajax.php' ) );

        $html .= '<div class="wpbdp-upload-widget">';
        $html .= sprintf( '<iframe class="wpbdp-upload-iframe" name="upload-iframe-%d" id="wpbdp-upload-iframe-%d" src="%s" scrolling="no" seamless="seamless" border="0" frameborder="0"></iframe>',
                          $field->get_id(),
                          $field->get_id(),
                          $ajax_url
                        );
        $html .= '</div>';

        return $html;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $img_id = $field->value( $post_id );

        if ( ! $img_id )
            return '';

        _wpbdp_resize_image_if_needed( $img_id );
        $img = wp_get_attachment_image_src( $img_id, 'large' );

        $html  = '';
        $html .= '<br />';
        $html .= '<a href="' . esc_url( $img[0] ) . '" target="_blank" ' . ( wpbdp_get_option( 'use-thickbox' ) ? 'class="thickbox" data-lightbox="wpbdpgal" rel="wpbdpgal"' : '' )  . '>';
        $html .= wp_get_attachment_image( $img_id, 'wpbdp-thumb', false );
        $html .= '</a>';

        return $html;
    }

}

