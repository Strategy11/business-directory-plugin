<?php
/**
 * Image Field-type
 *
 * @package BDP/Form Fields/Image Field-type
 * @SuppressWarnings(PHPMD)
 */

// phpcs:disable
class WPBDP_FieldTypes_Image extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x( 'Image (file upload)', 'form-fields api', 'WPBDM' ) );

        // TODO(fes-revamp): maybe this should go somewhere else?
        add_action( 'wp_ajax_wpbdp-file-field-upload', array( $this, '_ajax_file_field_upload' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-file-field-upload', array( $this, '_ajax_file_field_upload' ) );
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

    public function setup_validation( $field, $validator, $value ) {
        if ( 'caption_' != $validator ) {
            return;
        }

        $args                     = array();
        $args['caption_required'] = $field->data( 'caption_required' );
        $args['messages']         = array(
            'caption_required' => sprintf(
                _x( 'Caption for %s is required.', 'date field', 'WPBDM' ),
                esc_attr( $field->get_label() )
            ),
        );
        return $args;
    }

    public function render_field_settings( &$field = null, $association = null ) {
        if ( $association != 'meta' ) {
            return '';
        }

        $settings = array();

        $settings['display-caption'][] = _x( 'Display caption?', 'form-fields admin', 'WPBDM' );
        $settings['display-caption'][] = '<input type="checkbox" value="1" name="field[x_display_caption]" ' . ( $field && $field->data( 'display_caption' ) ? ' checked="checked"' : '' ) . ' />';

        $settings['caption-required'][] = _x( 'Field Caption required?', 'form-fields admin', 'WPBDM' );
        $settings['caption-required'][] = '<input type="checkbox" value="1" name="field[x_caption_required]" ' . ( $field && $field->data( 'caption_required' ) ? ' checked="checked"' : '' ) . ' />';

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        if ( array_key_exists( 'x_display_caption', $_POST['field'] ) ) {
            $display_caption = (bool) intval( $_POST['field']['x_display_caption'] );
            $field->set_data( 'display_caption', $display_caption );
        }

        if ( array_key_exists( 'x_caption_required', $_POST['field'] ) ) {
            $caption_required = (bool) intval( $_POST['field']['x_caption_required'] );
            $field->set_data( 'caption_required', $caption_required );
            $field->add_validator( 'caption_' );
        }
    }

    public function render_field_inner( &$field, $value, $context, &$extra = null, $field_settings = array() ) {
        if ( $context == 'search' ) {
            return '';
        }

        $value = is_array( $value ) ? $value : array( $value );

        $html  = '';
        $html .= sprintf(
            '<input type="hidden" name="listingfields[%d][0]" value="%s" />',
            $field->get_id(),
            $value[0]
        );

        $html .= '<div class="preview"' . ( empty( $value[0] ) ? ' style="display: none;"' : '' ) . '>';
        if ( ! empty( $value[0] ) ) {
            $html .= wp_get_attachment_image( $value[0], 'wpbdp-thumb', false );
        }

        $html .= sprintf(
            '<a href="http://google.com" class="delete" onclick="return WPBDP.fileUpload.deleteUpload(%d, \'%s\');">%s</a>',
            $field->get_id(),
            'listingfields[' . $field->get_id() . '][0]',
            _x( 'Remove', 'form-fields-api', 'WPBDM' )
        );

        $html .= sprintf(
            '<label for="wpbdp-field-%1$d-caption" style="display:none;">Image Caption:</label><input id="wpbdp-field-%1$d-caption" type="text" name="listingfields[%1$d][1]" value="%2$s" placeholder="Image caption or description">',
            $field->get_id(),
            ! empty( $value[1] ) ? $value[1] : ''
        );

        $html .= '</div>';

        // We use $listing_id to prevent CSFR. Related to #2848.
        $listing_id = 0;
        if ( 'submit' == $context ) {
            $listing_id = $extra->get_id();
        } elseif ( is_admin() ) {
            global $post;
            if ( ! empty( $post ) && WPBDP_POST_TYPE == $post->post_type ) {
                $listing_id = $post->ID;
            }
        }

        if ( ! $listing_id ) {
            return wpbdp_render_msg( _x( 'Field unavailable at the moment.', 'form fields', 'WPBDM' ), 'error' );
        }

        $nonce    = wp_create_nonce( 'wpbdp-file-field-upload-' . $field->get_id() . '-listing_id-' . $listing_id );
        $ajax_url = add_query_arg(
            array(
                'action'     => 'wpbdp-file-field-upload',
                'field_id'   => $field->get_id(),
                'element'    => 'listingfields[' . $field->get_id() . '][0]',
                'nonce'      => $nonce,
                'listing_id' => $listing_id,
            ),
            admin_url( 'admin-ajax.php' )
        );

        $html .= '<div class="wpbdp-upload-widget">';
        $html .= sprintf(
            '<iframe class="wpbdp-upload-iframe" name="upload-iframe-%d" id="wpbdp-upload-iframe-%d" src="%s" scrolling="no" seamless="seamless" border="0" frameborder="0"></iframe>',
            $field->get_id(),
            $field->get_id(),
            $ajax_url
        );
        $html .= '</div>';

        return $html;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $field_value = $field->value( $post_id );

        $img_id  = $field_value;
        $caption = '';

        if ( is_array( $field_value ) ) {
            $img_id   = $field_value[0];
            $caption .= $field_value[1];
        }

        if ( ! $img_id ) {
            return '';
        }

        $thumbnail_width = absint( wpbdp_get_option( 'thumbnail-width' ) );

        _wpbdp_resize_image_if_needed( $img_id );
        $img = wp_get_attachment_image_src( $img_id, 'large' );

        $html  = '';
        $html .= '<br />';
        $html .= '<div class="listing-image" style="width: ' . $thumbnail_width . 'px;">';
        $html .= '<a href="' . esc_url( $img[0] ) . '" target="_blank" rel="noopener" ' . ( wpbdp_get_option( 'use-thickbox' ) ? 'class="thickbox" data-lightbox="wpbdpgal" rel="wpbdpgal"' : '' ) . '>';
        $html .= wp_get_attachment_image( $img_id, 'wpbdp-thumb', false, array( 'alt' => $caption ? $caption : $field->get_label() ) );
        $html .= '</a>';
        $html .= $field->data( 'display_caption' ) ? '<br />' . $caption : '';
        $html .= '</div>';

        return $html;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );
        return is_array( $value ) ? $value[0] : $value;
    }

    public function convert_csv_input( &$field, $input = '', $import_settings = array() ) {
        $input = str_replace( array( '"', '\'' ), '', $input );
        $input = str_replace( ';', ',', $input ); // Support ; as a separator here.
        $parts = explode( ',', $input );

        if ( 1 == count( $parts ) ) {
            return array( $parts[0], '' );
        }

        return $parts;
    }

    public function get_field_csv_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( is_array( $value ) && count( $value ) > 1 ) {
            return sprintf( '%s,%s', $value[0], $value[1] );
        }

        return is_array( $value ) ? $value[0] : '';
    }

    public function _ajax_file_field_upload() {
        $field_id   = ! empty( $_REQUEST['field_id'] ) ? absint( $_REQUEST['field_id'] ) : 0;
        $nonce      = ! empty( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '';
        $listing_id = ! empty( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;

        if ( ! $field_id || ! $nonce || ! $listing_id ) {
            die;
        }

        $element = ! empty( $_REQUEST['element'] ) ? $_REQUEST['element'] : 'listingfields[' . $field_id . '][0]';

        if ( ! wp_verify_nonce( $nonce, 'wpbdp-file-field-upload-' . $field_id . '-' . 'listing_id-' . $listing_id ) ) {
            die;
        }

        $field = wpbdp_get_form_field( $field_id );
        if ( ! $field || ! in_array(  $field->get_field_type_id(), array( 'image', 'social-network') ) ) {
            die;
        }

        echo '<form action="" method="POST" enctype="multipart/form-data">';
        echo '<input type="file" name="file" class="file-upload" onchange="return window.parent.WPBDP.fileUpload.handleUpload(this);"/>';
        echo '</form>';

        if ( isset( $_FILES['file'] ) && $_FILES['file']['error'] == 0 ) {
            // TODO: we support only images for now but we could use this for anything later
            if ( $media_id = wpbdp_media_upload(
                $_FILES['file'],
                true,
                true,
                array(
					'image'      => true,
					'min-size'   => intval( wpbdp_get_option( 'image-min-filesize' ) ) * 1024,
					'max-size'   => intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024,
					'min-width'  => wpbdp_get_option( 'image-min-width' ),
					'min-height' => wpbdp_get_option( 'image-min-height' ),
                ),
                $errors
            ) ) {
				echo '<div class="preview" style="display: none;">';
				echo wp_get_attachment_image( $media_id, 'thumb', false );
				echo '</div>';

				echo '<script type="text/javascript">';
				echo sprintf( 'window.parent.WPBDP.fileUpload.finishUpload(%d, %d, "%s");', $field_id, $media_id, $element );
				echo '</script>';
            } else {
                print $errors;
            }
        }

        echo sprintf( '<script type="text/javascript">window.parent.WPBDP.fileUpload.resizeIFrame(%d);</script>', $field_id );

        exit;
    }

    public function is_empty_value( $value ) {
        return empty( $value[0] );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( ! is_array( $value ) && empty( $value ) ) {
            $value = null;
        }

        if ( is_array( $value ) && empty( $value[0] ) ){
            $value = null;
        }

        if ( is_array( $value ) && ! empty( $value[1] ) ) {
            $img               = get_post( $value[0] );
            $img->post_excerpt = $value[1];
            wp_update_post( $img );
        }

        parent::store_field_value( $field, $post_id, $value );
    }

    public function convert_input( &$field, $input ) {
        if ( $input === null ) {
            return array( '', '' );
        }

        $image   = trim( is_array( $input ) ? $input[0] : $input );
        $caption = trim( is_array( $input ) ? $input[1] : '' );

        return array( $image, $caption );
    }
}

