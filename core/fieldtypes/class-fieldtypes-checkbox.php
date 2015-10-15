<?php
if ( ! class_exists( 'WPBDP_CategoryFormInputWalker' ) )
    require_once ( WPBDP_PATH . '/core/helpers/class-category-form-input-walker.php' );


class WPBDP_FieldTypes_Checkbox extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Checkbox', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'checkbox';
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        $options = $field->data( 'options' ) ? $field->data( 'options') : array();

        if ( $field->get_association() == 'tags' && !$options ) {
            $tags = get_terms( WPBDP_TAGS_TAX, array( 'hide_empty' => false, 'fields' => 'names' ) );
            $options = array_combine( $tags, $tags );
        } elseif ( $field->get_association() == 'category' ) {
            $html = wp_list_categories( array(
                'taxonomy' => WPBDP_CATEGORY_TAX,
                'orderby' => wpbdp_get_option( 'categories-order-by' ),
                'order' => wpbdp_get_option( 'categories-sort' ),
                'hide_empty' => 0,
                'echo' => 0,
                'depth' => 0,
                'walker' => new WPBDP_CategoryFormInputWalker( 'checkbox', $value, $field ),
                'show_option_none' => '',
                'title_li' => '',
            ) );

            return $html;
        }

        $html = '';
        $i = 1;
        foreach ( $options as $option_key => $label ) {
            $css_classes = array();
            $css_classes[] = 'wpbdp-inner-checkbox';
            $css_classes[] = 'wpbdp-inner-checkbox-' . $i;
            $css_classes[] = 'wpbdp-inner-checkbox-' . WPBDP_Form_Field_Type::normalize_name( $label );

            $html .= sprintf( '<div class="wpbdmcheckboxclass %s"><input type="checkbox" class="%s" name="%s" value="%s" %s/> %s</div>',
                              implode( ' ', $css_classes ),
                              $field->is_required() ? 'required' : '',
                             'listingfields[' . $field->get_id() . '][]',
                              $option_key,
                              in_array( $option_key, is_array( $value ) ? $value : array( $value ) ) ? 'checked="checked"' : '',
                              esc_attr( $label ) );

            $i++;
        }

        $html .= '<div style="clear:both;"></div>';

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        if ( $association != 'meta' && $association != 'tags' )
            return '';

        $settings = array();

        $settings['options'][] = _x( 'Field Options (for select lists, radio buttons and checkboxes).', 'form-fields admin', 'WPBDM' ) . '<span class="description">(required)</span>';

        $content  = '<span class="description">Comma (,) separated list of options</span><br />';
        $content .= '<textarea name="field[x_options]" cols="50" rows="2">';

        if ( $field && $field->data( 'options' ) )
            $content .= implode( ',', $field->data( 'options' ) );
        $content .= '</textarea>';

        $settings['options'][] = $content;

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        if ( !array_key_exists( 'x_options', $_POST['field'] ) )
            return;

        $options = stripslashes( trim( $_POST['field']['x_options'] ) );

        if ( !$options && $field->get_association() != 'tags' )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', !empty( $options ) ? explode( ',', $options ) : array() );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( $field->get_association() == 'meta' ) {
            $value =  implode( "\t", is_array( $value ) ? $value : array( $value ) );
        }

        parent::store_field_value( $field, $post_id, $value );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );
        $value = empty( $value ) ? array() : $value;

        if ( is_string( $value ) )
            return explode( "\t", $value );

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        if ( $field->get_association() == 'meta' ) {
            return esc_attr( implode( ', ', $field->value( $post_id ) ) );
        }

        return parent::get_field_html_value( $field, $post_id );
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( $field->get_association() == 'category' || $field->get_association() == 'tags' ) {
            $term_names = get_terms( $field->get_association() == 'category' ? WPBDP_CATEGORY_TAX : WPBDP_TAGS_TAX,
                                     array( 'include' => $value, 'hide_empty' => 0, 'fields' => 'names' ) );

            return join( ', ', $term_names );
        } elseif ( $field->get_association() == 'meta' ) {
            return esc_attr( implode( ', ', $value ) );
        }

        return strval( $value );
    }

    /**
     * @since 3.4.1
     */
    public function get_field_csv_value( &$field, $post_id ) {
        if ( 'meta' != $field->get_association() )
            return $this->get_field_plain_value( $field, $post_id );

        $value = $field->value( $post_id );
        return esc_attr( implode( ',', $value ) );
    }

    /**
     * @since 3.4.1
     */
    public function convert_csv_input( &$field, $input = '', $import_settings = array() ) {
        if ( 'meta' != $field->get_association() )
            return $this->convert_input( $field, $input );

        if ( ! $input )
            return array();

        return explode( ',', $input );
    }
}

