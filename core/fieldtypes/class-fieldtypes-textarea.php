<?php

class WPBDP_FieldTypes_TextArea extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Textarea', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textarea';
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        // render textareas as textfields when searching
        if ( $context == 'search' ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra, $field_settings );
        }

        $html  = '';

        if ( 'content' == $field->get_association() && $field->data( 'allow_html' ) && $field->data( 'wysiwyg_editor' ) ) {
            ob_start();
            wp_editor( $value ? $value: '',
                       'wpbdp-field-' . $field->get_id(),
                       array( 'textarea_name' => 'listingfields[' . $field->get_id() . ']',
                              'drag_drop_upload' => false,
                              'media_buttons' => false,
                              'quicktags' => false  ) );
            $html .= ob_get_contents();
            ob_end_clean();
        } else {
            $html .= sprintf('<textarea id="%s" name="%s" class="intextarea textarea %s">%s</textarea>',
                             'wpbdp-field-' . $field->get_id(),
                             'listingfields[' . $field->get_id() . ']',
                             $field->is_required() ? 'required' : '',
                             $value ? esc_attr( $value ) : '' );
        }

        return $html;
    }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'content', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        $settings = array();

        $settings['allow_html'][] = _x( 'Allow HTML input for this field?', 'form-fields admin', 'WPBDM' );
        $settings['allow_html'][] = '<input type="checkbox" value="1" name="field[allow_html]" ' . ( $field && $field->data( 'allow_html' ) ? ' checked="checked"' : '' ) . ' />';

        if ( ( $field && $field->get_association() == 'content' ) || ( $association == 'content' ) ) {
            $settings['allow_shortcodes'][] = _x( 'Allow WordPress shortcodes in this field?', 'form-fields admin', 'WPBDM' );
            $settings['allow_shortcodes'][] = '<input type="checkbox" value="1" name="field[allow_shortcodes]" ' . ( $field && $field->data( 'allow_shortcodes' ) ? ' checked="checked"' : '' ) . ' />';

            $settings['wysiwyg_editor'][] = _x( 'Display a WYSIWYG editor on the frontend?', 'form-fields admin', 'WPBDM' );
            $settings['wysiwyg_editor'][] = '<input type="checkbox" value="1" name="field[wysiwyg_editor]" ' . ( $field && $field->data( 'wysiwyg_editor' ) ? ' checked="checked"' : '' ) . ' />';

            //$desc  = _x( 'Useful for integrating with some plugins.', 'form-fields admin', 'WPBDM' ) . '<br />';
            $desc = _x( '<b>Advanced users only!</b> Unless you\'ve been told to change this, don\'t switch it unless you know what you\'re doing.', 'form-fields admin', 'WPBDM' );
            $settings['allow_filters'][] = _x( 'Apply "the_content" filter before displaying this field?', 'form-fields admin', 'WPBDM' );
            $settings['allow_filters'][] = '<input type="checkbox" value="1" name="field[allow_filters]" ' . ( $field && $field->data( 'allow_filters' ) ? ' checked="checked"' : '' ) . ' /> <span class="description">' . $desc . '</span>';
        }

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        $field->set_data( 'allow_html', isset( $_POST['field']['allow_html'] ) ? (bool) intval( $_POST['field']['allow_html'] ) : false );
        $field->set_data( 'allow_filters', isset( $_POST['field']['allow_filters'] ) ? (bool) intval( $_POST['field']['allow_filters'] ) : false );
        $field->set_data( 'allow_shortcodes', isset( $_POST['field']['allow_shortcodes'] ) ? (bool) intval( $_POST['field']['allow_shortcodes'] ) : false );
        $field->set_data( 'wysiwyg_editor', isset( $_POST['field']['wysiwyg_editor'] ) ? (bool) intval( $_POST['field']['wysiwyg_editor'] ) : false );
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( $field->data( 'allow_html' ) ) {
            $value = wp_kses_post( $value );
        } else {
            $value = wp_kses( $value, array() );
        }

        if ( 'content' == $field->get_association() ) {
            if ( $field->data( 'allow_filters' ) ) {
                $value = apply_filters( 'the_content', $value );
            } else {
                if ( ! $field->data( 'wysiwyg_editor' ) ) {
                    // Do not modify HTML output from WYSIWYG editor.
                    $value = nl2br( $value );
                }

                if ( $field->data( 'allow_shortcodes' ) ) {
                    global $post;
                    // Try to protect us from sortcodes messing things for us.
                    $current_post = $post;
                    $value = do_shortcode( nl2br( $value ) );
                    $post = $current_post;
                }
            }
        } else {
            $value = nl2br( $value );
        }

        return $value;
    }

}

