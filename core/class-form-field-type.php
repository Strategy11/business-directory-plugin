<?php
class WPBDP_Form_Field_Type {

    private $name = null;

    public function __construct( $name = '' ) {
        if ( ! empty( $name ) )
            $this->name = $name;
    }

    public function get_id() {
        $id = strtolower( get_class( $this ) );
        $id = str_replace( 'wpbdp_fieldtypes_', '', $id );
        $id = str_replace( 'wpbdp_', '', $id );

        return $id;
    }

    public function get_name() {
        if ( empty( $this->name ) ) {
            $name = get_class( $this );
            $name = str_replace( 'WPBDP_FieldTypes_', '', $name );
            $name = str_replace( 'WPBDP_', '', $name );
            $name = str_replace( '_', ' ', $name );
            $name = trim( $name );

            $this->name = $name;
        }

        return $this->name;
    }

    /**
     * Called after a field of this type is constructed.
     * @param object $field
     */
    public function setup_field( &$field ) {
        return;
    }

    /**
     * Called before field validation takes place.
     * @since 3.6.5
     */
    public function setup_validation( $field, $validator, $value ) {
        return array();
    }

    /**
     * TODO: dodoc.
     * @since 3.4
     */
    public function get_behavior_flags( &$field ) {
        return array();
    }

    public function get_field_value( &$field, $post_id ) {
        $post = get_post( $post_id );

        if ( !$post )
            return null;

        switch ( $field->get_association() ) {
            case 'title':
                $value = $post->post_title;
                break;
            case 'excerpt':
                $value = $post->post_excerpt;
                break;
            case 'content':
                $value = $post->post_content;
                break;
            case 'category':
                $value = wp_get_object_terms( $post_id, WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
                break;
            case 'tags':
                $value = wp_get_object_terms( $post_id, WPBDP_TAGS_TAX, array( 'fields' => 'names' ) );
                break;
            case 'meta':
                $value = get_post_meta( $post_id, '_wpbdp[fields][' . $field->get_id() . ']', true );
                break;
            default:
                $value = null;
                break;
        }

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $post = get_post( $post_id );

        switch ( $field->get_association() ) {
            case 'title':
                $value = sprintf( '<a href="%s">%s</a>', get_permalink( $post_id ), get_the_title( $post_id ) );
                break;
            case 'excerpt':
                $value = apply_filters( 'get_the_excerpt', wpautop( $post->post_excerpt, true ) );
                break;
            case 'content':
                $value = apply_filters( 'the_content', $post->post_content );
                break;
            case 'category':
                $value = get_the_term_list( $post_id, WPBDP_CATEGORY_TAX, '', ', ', '' );
                break;
            case 'tags':
                $value = get_the_term_list( $post_id, WPBDP_TAGS_TAX, '', ', ', '' );
                break;
            case 'meta':
            default:
                $value = $field->value( $post_id );
                break;
        }

        return $value;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        return $this->get_field_value( $field, $post_id );
    }

    /**
     * @since 3.4.1
     */
    public function get_field_csv_value( &$field, $post_id ) {
        return $this->get_field_plain_value( $field, $post_id );
    }

    public function is_empty_value( $value ) {
        return empty( $value );
    }

    public function convert_input( &$field, $input ) {
        return $input;
    }

    /**
     * @since 3.4.1
     */
    public function convert_csv_input( &$field, $input = '', $import_settings = array() ) {
        return $this->convert_input( $field, $input );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        switch ( $field->get_association() ) {
            case 'title':
                wp_update_post( array( 'ID' => $post_id, 'post_title' => trim( strip_tags( $value ) ) ) );
                break;
            case 'excerpt':
                wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => $value ) );
                break;
            case 'content':
                wp_update_post( array( 'ID' => $post_id, 'post_content' => $value ) );
                break;
            case 'category':
                wp_set_post_terms( $post_id, $value, WPBDP_CATEGORY_TAX, false );
                break;
            case 'tags':
                wp_set_post_terms( $post_id, $value, WPBDP_TAGS_TAX, false );
                break;
            case 'meta':
            default:
                update_post_meta( $post_id, '_wpbdp[fields][' . $field->get_id() . ']', $value );
                break;
        }
    }

    // this function should not try to hide values depending on field, context or value itself.
    public function display_field( &$field, $post_id, $display_context ) {
        return self::standard_display_wrapper( $field, $field->html_value( $post_id ) );
    }

    public function render_field_inner( &$field, $value, $render_context, &$extra=null, $field_settings = array() ) {
        return '';
    }

    public function render_field( &$field, $value, $render_context, &$extra=null, $field_settings = array() ) {
        $html = '';

        switch ( $render_context ) {
            case 'search':
                $html .= sprintf( '<div class="wpbdp-search-filter %s %s" %s>',
                                  $field->get_field_type()->get_id(),
                                  implode(' ', $field->get_css_classes( $render_context ) ),
                                  $this->html_attributes( $field->html_attributes ) );
                $html .= sprintf( '<div class="wpbdp-search-field-label"><label>%s</label></div>', esc_html( apply_filters( 'wpbdp_render_field_label', $field->get_label(), $field ) ) );
                $html .= '<div class="field inner">';

                $field_inner = $this->render_field_inner( $field, $value, $render_context, $extra, $field_settings );
                $field_inner = apply_filters_ref_array( 'wpbdp_render_field_inner', array( $field_inner, &$field, $value, $render_context, &$extra ) );

                $html .= $field_inner;
                $html .= '</div>';
                $html .= '</div>';

                break;

            case 'submit':
            case 'edit':
            default:
                $html_attributes = $this->html_attributes( apply_filters_ref_array( 'wpbdp_render_field_html_attributes', array( $field->html_attributes, &$field, $value, $render_context, &$extra ) ) );

                $html .= sprintf( '<div class="%s" %s>',
                                  implode( ' ', $field->get_css_classes( $render_context ) ),
                                  $html_attributes );
                $html .= '<div class="wpbdp-form-field-label">';
                $html .= sprintf( '<label for="%s">%s</label>', 'wpbdp-field-' . $field->get_id(), apply_filters( 'wpbdp_render_field_label', $field->get_label(), $field ) );

                if ( $field->get_description() )
                    $html .= sprintf( '<span class="field-description">(%s)</span>', apply_filters( 'wpbdp_render_field_description', $field->get_description(), $field ) );

                $html .= '</div>';
                $html .= '<div class="wpbdp-form-field-html wpbdp-form-field-inner">';

                $field_inner = $this->render_field_inner( $field, $value, $render_context, $extra, $field_settings );
                $field_inner = apply_filters_ref_array( 'wpbdp_render_field_inner', array( $field_inner, &$field, $value, $render_context, &$extra ) );                

                $html .= $field_inner;
                $html .= '</div>';
                $html .= '</div>';

                break;
        }

        return $html;
    }

    /**
     * Called after a field of this type is deleted.
     * @param object $field the deleted WPBDP_FormField object.
     */
    public function cleanup( &$field ) {
        if ( $field->get_association() == 'meta' ) {
            global $wpdb;
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[fields][' . $field->get_id() . ']' ) );
        }


    }

    /**
     * Returns an array of valid associations for this field type.
     * @return array
     */
    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'content', 'category', 'tags', 'meta' );
    }

    /**
     * Renders the field-specific settings area for fields of this type.
     * It is recommended to use `render_admin_settings` here to keep an uniform look.
     * `$_POST` values can be used here to populate things when needed.
     * @param object $field might be NULL if field is new or the field that is being edited.
     * @param string $association field association.
     * @return string the HTML output.
     */
    public function render_field_settings( &$field=null, $association=null ) {
        return '';
    }

    /**
     * Called when saving fields of this type.
     * It should be used by field types to store any field type specific configuration.
     * @param object $field the field being saved.
     * @return mixed WP_Error in case of error, anything else for success.
     */
    public function process_field_settings( &$field ) {
        return;
    }


    /* Utils. */
    public static function standard_display_wrapper( $labelorfield, $content=null, $extra_classes='', $args=array() ) {
        $css_classes = '';
        $css_classes .= 'wpbdp-field-display wpbdp-field wpbdp-field-value field-display field-value ';

        if ( is_object( $labelorfield ) ) {
            if ( $labelorfield->has_display_flag( 'social' ) )
                return $content;

            $css_classes .= 'wpbdp-field-' . self::normalize_name( $labelorfield->get_label() ) . ' ';
            $css_classes .= 'wpbdp-field-' . $labelorfield->get_association() . ' ';
            $css_classes .= 'wpbdp-field-type-' . $labelorfield->get_field_type_id() . ' ';
            $css_classes .= 'wpbdp-field-association-' . $labelorfield->get_association() . ' ';
            $label = $labelorfield->has_display_flag( 'nolabel' ) ? null : $labelorfield->get_label();
        } else {
            $css_classes .= 'wpbdp-field-' . self::normalize_name( $labelorfield ) . ' ';
            $label = $labelorfield;
        }

        $html  = '';
        $tag_attrs = isset( $args['tag_attrs'] ) ? self::html_attributes( $args['tag_attrs'] ) : '';
        $html .= '<div class="' . $css_classes . ' ' . $extra_classes . '" ' . $tag_attrs . '>';

        if ( $label )
            $html .= '<label>' . esc_html( apply_filters( 'wpbdp_display_field_label', $label, $labelorfield ) ) . ':</label> ';

        if ($content)
            $html .= '<span class="value">' . $content . '</span>';

        $html .= '</div>';

        return $html;
    }

    public static function render_admin_settings( $admin_settings=array() ) {
        if ( !$admin_settings )
            return '';

        $html  = '';
        $html .= '<table class="form-table">';

        foreach ( $admin_settings as $s ) {
            $label = is_array( $s ) ? $s[0] : '';
            $content = is_array( $s ) ? $s[1] : $s;

            $html .= '<tr>';
            if ( $label ) {
                $html .= '<th scope="row">';
                $html .= '<label>' . $label . '</label>';
                $html .= '</th>';
            }

            $html .= $label ? '<td>' : '<td colspan="2">';
            $html .= $content;
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    public static function html_attributes( $attrs, $exceptions = array( 'class' ) ) {
        $html = '';

        foreach ( $attrs as $k => $v ) {
            if ( in_array( $k, $exceptions, true ) )
                continue;

            $html .= sprintf( '%s="%s" ', $k, $v );
        }

        return $html;
    }

    /**
     * @since 3.5.3
     */
    public static function normalize_name( $name ) {
        $name = strtolower( $name );
        $name = remove_accents( $name );
        $name = preg_replace( '/\s+/', '_', $name );
        $name = preg_replace( '/[^a-zA-Z0-9_-]+/', '', $name );

        return $name;
    }

}

/**
 * @deprecated Since 3.4.2. Use {@link WPBDP_Form_Field_Type} instead.
 */
class WPBDP_FormFieldType extends WPBDP_Form_Field_Type {}
