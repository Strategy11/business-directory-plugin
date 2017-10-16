<?php

class WPBDP_FieldTypes_TextArea extends WPBDP_Form_Field_Type {

    private $tinymce_settings = array();
    private $quicktags_settings = array();

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

        if ( $this->should_show_wysiwyg_editor( $field ) ) {
            ob_start();
            add_filter( 'tiny_mce_before_init', array( $this, 'capture_tinymce_settings' ), 100, 2 );
            add_filter( 'quicktags_settings', array( $this, 'capture_quicktag_settings' ), 100, 2 );

            wp_editor( $value ? $value: '',
                       'wpbdp-field-' . $field->get_id(),
                       array( 'textarea_name' => 'listingfields[' . $field->get_id() . ']',
                              'drag_drop_upload' => false,
                              'media_buttons' => false,
                              'quicktags' => ( (bool) $field->data( 'wysiwyg_images' ) ) ? true : false  ) );

            remove_filter( 'tiny_mce_before_init', array( $this, 'capture_tinymce_settings' ), 100, 2 );
            remove_filter( 'quicktags_settings', array( $this, 'capture_quicktag_settings' ), 100, 2 );
            ob_end_clean();

            $html .= sprintf(
                '<textarea id="%s" class="wpbdp-editor-area" name="%s">%s</textarea>',
                'wpbdp-field-' . $field->get_id(),
                'listingfields[' . $field->get_id() . ']',
                $value ? esc_attr( $value ) : ''
            );

            $html .= sprintf(
                '<script type="text/javascript">
                    var WPBDPTinyMCESettings = WPBDPTinyMCESettings || {};

                    WPBDPTinyMCESettings[ \'%s\' ] = {
                        \'tinymce\': %s,
                        \'quicktags\': %s
                    };
                </script>',
                'wpbdp-field-' . $field->get_id(),
                $this->parse_tinymce_settings( $this->tinymce_settings ),
                $this->parse_tinymce_settings( $this->quicktags_settings )
            );
        } else {
            $html .= sprintf('<textarea id="%s" name="%s">%s</textarea>',
                             'wpbdp-field-' . $field->get_id(),
                             'listingfields[' . $field->get_id() . ']',
                             $value ? esc_attr( $value ) : '' );
        }

        return $html;
    }

    private function should_show_wysiwyg_editor( $field ) {
        if ( ! function_exists( 'wp_enqueue_editor' ) ) {
            return false;
        }

        return 'content' == $field->get_association() && $field->data( 'allow_html' ) && $field->data( 'wysiwyg_editor' );
    }

    public function capture_tinymce_settings( $settings, $editor_id ) {
        $this->tinymce_settings = $settings;
        return $settings;
    }

    public function capture_quicktag_settings( $settings, $editor_id ) {
        $this->quicktags_settings = $settings;
        return $settings;
    }

    /**
     * A copy of _WP_Editors::_parse_init().
     *
     * @since 5.0
     */
    private function parse_tinymce_settings( $init ) {
        $options = '';

        foreach ( $init as $key => $value ) {
            if ( is_bool( $value ) ) {
                $val = $value ? 'true' : 'false';
                $options .= $key . ':' . $val . ',';
                continue;
            } elseif ( ! empty( $value ) && is_string( $value ) && (
                ( '{' == $value{0} && '}' == $value{strlen( $value ) - 1} ) ||
                ( '[' == $value{0} && ']' == $value{strlen( $value ) - 1} ) ||
                preg_match( '/^\(?function ?\(/', $value ) ) ) {

                $options .= $key . ':' . $value . ',';
                continue;
            }
            $options .= $key . ':"' . $value . '",';
        }

        return '{' . trim( $options, ' ,' ) . '}';
    }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'content', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        $settings = array();

        $settings['allow_html'][] = _x( 'Allow HTML input for this field?', 'form-fields admin', 'WPBDM' );
        $settings['allow_html'][] = '<input type="checkbox" value="1" name="field[allow_html]" ' . ( $field && $field->data( 'allow_html' ) ? ' checked="checked"' : '' ) . ' />';

        $settings['allow_iframes'][] = _x( 'Allow IFRAME tags in content?', 'form-fields admin', 'WPBDM' );
        $settings['allow_iframes'][] =
            '<div class="iframe-confirm wpbdp-note warning">' .
            '<p>' . _x( 'Enabling iframe support in your listings can allow users to execute arbitrary scripts on a page if they want, which can possibly infect your site with malware. We do NOT recommend using this setting UNLESS you are posting the listings yourself and have sole control over the content. Are you sure you want to enable this?', 'admin form-fields', 'WPBDM' ) . '</p>' .
            '<a href="#" class="button no">' . _x( 'No', 'form-fields admin', 'WPBDM' ) . '</a> ' .
            '<a href="#" class="button button-primary yes">' . _x( 'Yes', 'form-fields admin', 'WPBDM' ) . '</a>' .
            '</div>' .
            '<input type="checkbox" value="1" name="field[allow_iframes]" ' . ( $field && $field->data( 'allow_iframes' ) ? ' checked="checked"' : '' ) . ' />';

        if ( ( $field && in_array( $field->get_association(), array( 'content', 'excerpt' ) ) ) || ( in_array( $association, array( 'content', 'excerpt' ) ) ) ) {
            $settings['allow_shortcodes'][] = _x( 'Allow WordPress shortcodes in this field?', 'form-fields admin', 'WPBDM' );
            $settings['allow_shortcodes'][] = '<input type="checkbox" value="1" name="field[allow_shortcodes]" ' . ( $field && $field->data( 'allow_shortcodes' ) ? ' checked="checked"' : '' ) . ' />';
        }

        if ( ( $field && $field->get_association() == 'content' ) || ( $association == 'content' ) ) {
            $settings['wysiwyg_editor'][] = _x( 'Display a WYSIWYG editor on the frontend?', 'form-fields admin', 'WPBDM' );
            $settings['wysiwyg_editor'][] = '<input type="checkbox" value="1" name="field[wysiwyg_editor]" ' . ( $field && $field->data( 'wysiwyg_editor' ) ? ' checked="checked"' : '' ) . ' />';

            $desc = _x( '<b>Warning:</b> Users can use this feature to get around your image limits in fee plans.', 'form-fields admin', 'WPBDM' );
            $settings['wysiwyg_images'][] = _x( 'Allow images in WYSIWYG editor?', 'form-fields admin', 'WPBDM' );
            $settings['wysiwyg_images'][] = '<input type="checkbox" value="1" name="field[wysiwyg_images]" ' . ( $field && $field->data( 'wysiwyg_images' ) ? ' checked="checked"' : '' ) . ' /> <span class="description">' . $desc . '</span>';

            $desc = _x( '<b>Advanced users only!</b> Unless you\'ve been told to change this, don\'t switch it unless you know what you\'re doing.', 'form-fields admin', 'WPBDM' );
            $settings['allow_filters'][] = _x( 'Apply "the_content" filter before displaying this field?', 'form-fields admin', 'WPBDM' );
            $settings['allow_filters'][] = '<input type="checkbox" value="1" name="field[allow_filters]" ' . ( $field && $field->data( 'allow_filters' ) ? ' checked="checked"' : '' ) . ' /> <span class="description">' . $desc . '</span>';
        }

        if ( ( $field && $field->get_association() == 'excerpt' ) || ( $association == 'excerpt' ) ) {
            $settings['auto_excerpt'][] = _x( 'Automatically generate excerpt from content field?', 'form-fields admin', 'WPBDM' );
            $settings['auto_excerpt'][] = '<input type="checkbox" value="1" name="field[auto_excerpt]" ' . ( $field && $field->data( 'auto_excerpt' ) ? ' checked="checked"' : '' ) . ' />';
        }

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        $field->set_data( 'allow_html', isset( $_POST['field']['allow_html'] ) ? (bool) intval( $_POST['field']['allow_html'] ) : false );
        $field->set_data( 'allow_iframes', isset( $_POST['field']['allow_iframes'] ) ? (bool) intval( $_POST['field']['allow_iframes'] ) : false );
        $field->set_data( 'allow_filters', isset( $_POST['field']['allow_filters'] ) ? (bool) intval( $_POST['field']['allow_filters'] ) : false );
        $field->set_data( 'allow_shortcodes', isset( $_POST['field']['allow_shortcodes'] ) ? (bool) intval( $_POST['field']['allow_shortcodes'] ) : false );
        $field->set_data( 'wysiwyg_editor', isset( $_POST['field']['wysiwyg_editor'] ) ? (bool) intval( $_POST['field']['wysiwyg_editor'] ) : false );
        $field->set_data( 'wysiwyg_images', isset( $_POST['field']['wysiwyg_images'] ) ? (bool) intval( $_POST['field']['wysiwyg_images'] ) : false );
        $field->set_data( 'auto_excerpt', isset( $_POST['field']['auto_excerpt'] ) ? (bool) intval( $_POST['field']['auto_excerpt'] ) : false );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( 'content' == $field->get_association() ) {
            if ( $field->data( 'allow_html' ) && $field->data( 'wysiwyg_editor' ) && ! $field->data( 'wysiwyg_images' ) ) {
                $tags = wp_kses_allowed_html( 'post' );

                if ( isset( $tags['img'] ) )
                    unset( $tags['img'] );

                if ( $field->data( 'allow_iframes' ) )
                    $tags['iframe'] = array( 'src' => true );

                $value = wp_kses( $value, $tags );
            }
        }

        return parent::store_field_value( $field, $post_id, $value );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        // Only return auto-generated excerpt if there's no value at all.
        if ( 'excerpt' == $field->get_association() && $field->data( 'auto_excerpt') && ! $value )
            $value = $this->get_excerpt_value_from_post( $post_id );

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );
        $allowed_tags = array();

        if ( $field->data( 'allow_html' ) ) {
            $allowed_tags = wp_kses_allowed_html( 'post' );

            if ( $field->data( 'allow_iframes' ) )
                $allowed_tags['iframe'] = array( 'src' => true );
        }

        $value = wp_kses( $value, $allowed_tags );

        if ( 'content' == $field->get_association() ) {
            if ( $field->data( 'allow_filters' ) ) {
                // Prevent Jetpack sharing from appearing twice. (#2039)
                $jetpack_hack = has_filter( 'the_content', 'sharing_display' );

                if ( $jetpack_hack )
                    remove_filter( 'the_content', 'sharing_display', 19 );

                $value = apply_filters( 'the_content', $value );

                if ( $jetpack_hack )
                    add_filter( 'the_content', 'sharing_display', 19 );
            } else {
                if ( $field->data( 'allow_shortcodes' ) ) {
                    global $post;
                    // Try to protect us from sortcodes messing things for us.
                    $current_post = $post;

                    $value = wpautop( $value );

                    if ( wpbdp_get_option( 'disable-cpt' ) )
                        $value = do_shortcode( shortcode_unautop( $value ) );

                    $post = $current_post;
                } else {
                    $value = wpautop( $value );
                }
            }
        } elseif ( 'excerpt' == $field->get_association() ) {
            if ( $field->data( 'auto_excerpt' ) ) {
                $value = $this->get_excerpt_value_from_post( $post_id );
            }

            if ( $field->data( 'allow_shortcodes' ) ) {
                global $post;
                // Try to protect us from sortcodes messing things for us.
                $current_post = $post;

                $value = wpautop( $value );
                $value = do_shortcode( shortcode_unautop( $value ) );

                $post = $current_post;
            } else {
                if ( $field->data( 'allow_html' ) ) {
                    $value = wpautop( $value );
                }
            }

            if ( ! $field->data( 'allow_html' ) ) {
                $value = nl2br( $value );
            }
        } else {
            if ( $field->data( 'allow_html' ) ) {
                $value = wpautop( $value );
            } else {
                $value = nl2br( $value );
            }
        }



        return $value;
    }

    private function get_excerpt_value_from_post( $post_id ) {
        global $post;

        $current_post = $post;
        $post = get_post( $post_id );
        $value = apply_filters( 'get_the_excerpt', '' );
        $post = $current_post;

        return $value;
    }

    public function get_field_csv_value( &$field, $post_id ) {
        $value = parent::get_field_csv_value( $field, $post_id );
        $value = str_replace( "\r\n", "\n", $value );
        $value = str_replace( "\n", "\\n", $value );

        return $value;
    }

}

