<?php

class WPBDP_FieldTypes_Select extends WPBDP_Form_Field_Type {

    private $multiselect = false;

    public function __construct() {
        parent::__construct( _x('Select List', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'select';
    }

    public function set_multiple( $val ) {
        $this->multiselect = (bool) $val;
    }

    public function is_multiple() {
        return $this->multiselect;
    }

    public function convert_input( &$field, $input ) {
        $input = is_null( $input ) ? array() : $input;
        $res = is_array( $input ) ? $input : array( $input );

        if ( $field->get_association() == 'category' ) {
            $res = array_map( 'intval', $res );
        }

        return $res;
    }

    /**
     * @since 3.4.1
     */
    public function convert_csv_input( &$field, $input = '', $import_settings = array() ) {
        if ( 'tags' == $field->get_association() ) {
            $input = str_replace( ';', ',', $input );
            return explode( ',', $input );
        } elseif ( 'meta' != $field->get_association() ) {
            return $this->convert_input( $field, $input );
        }

        if ( ! $input )
            return array();

        if ( ! $this->is_multiple() )
            return array( str_replace( ',', '', $input ) );

        return explode( ',', $input );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null, $field_settings = array() ) {
        $options = $field->data( 'options' ) ? $field->data( 'options' ) : array();
        $value = is_array( $value ) ? $value : array( $value );

        $html = '';

        if ( $field->get_association() == 'tags' && !$options ) {
            $tags = get_terms( WPBDP_TAGS_TAX, array( 'hide_empty' => false, 'fields' => 'names' ) );
            $options = array_combine( $tags, $tags );
        }

        if ( $field->get_association() == 'category' ) {
                $html .= wp_dropdown_categories( array(
                        'taxonomy' => $field->get_association() == 'tags' ? WPBDP_TAGS_TAX : WPBDP_CATEGORY_TAX,
                        'show_option_none' => $context == 'search' ? ( $this->is_multiple() ? _x( '-- Choose Terms --', 'form-fields-api category-select', 'WPBDM' ) : _x( '-- Choose One --', 'form-fields-api category-select', 'WPBDM' ) ) : null,
                        'orderby' => wpbdp_get_option( 'categories-order-by' ),
                        'selected' => ( $this->is_multiple() ? null : ( $value ? $value[0] : null ) ),
                        'order' => wpbdp_get_option('categories-sort' ),
                        'hide_empty' => $context == 'search' && wpbdp_get_option( 'hide-empty-categories' ) ? 1 : 0,
                        'hierarchical' => 1,
                        'echo' => 0,
                        'id' => 'wpbdp-field-' . $field->get_id(),
                        'name' => 'listingfields[' . $field->get_id() . ']',
                        'class' => $field->is_required() ? 'inselect required' : 'inselect'
                    ) );

                if ( $this->is_multiple() ) {
                    $html = preg_replace( "/\\<select(.*)name=('|\")(.*)('|\")(.*)\\>/uiUs",
                                          "<select name=\"$3[]\" multiple=\"multiple\" $1 $5>",
                                          $html );

                    if ($value) {
                        foreach ( $value as $catid ) {
                            $html = preg_replace( "/\\<option(.*)value=('|\"){$catid}('|\")(.*)\\>/uiU",
                                                  "<option value=\"{$catid}\" selected=\"selected\" $1 $4>",
                                                  $html );
                        }
                    }
                }
        } else {
            $html .= sprintf( '<select id="%s" name="%s" %s class="%s %s">',
                              'wpbdp-field-' . $field->get_id(),
                              'listingfields[' . $field->get_id() . ']' . ( $this->is_multiple() ? '[]' : '' ),
                              $this->is_multiple() ? 'multiple="multiple"' : '',
                              'inselect',
                              $field->is_required() ? 'required' : '');

            if ( $field->data( 'empty_on_search' ) && $context == 'search' ) {
                $html .= sprintf( '<option value="-1">%s</option>',
                                  _x( '-- Choose One --', 'form-fields-api category-select', 'WPBDM' ) );
            }

            foreach ( $options as $option => $label ) {
                $option_data = array( 'label' => $label,
                                      'value' => esc_attr( $option ),
                                      'attributes' => array() );

                if ( in_array( $option, $value ) )
                    $option_data['attributes']['selected'] = 'selected';

                $option_data = apply_filters( 'wpbdp_form_field_select_option', $option_data, $field );

                $html .= sprintf( '<option value="%s" %s>%s</option>',
                                  esc_attr( $option_data['value'] ),
                                  $this->html_attributes( $option_data['attributes'], array( 'value', 'class' ) ),
                                  esc_attr( $option_data['label'] ) );
            }

            $html .= '</select>';
        }

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta', 'region' );
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

        $settings['empty_on_search'][] = _x('Allow empty selection on search?', 'form-fields admin', 'WPBDM');

        $content  = '<span class="description">Empty search selection means users can make this field optional in searching. Turn it off if the field must always be searched on.</span><br />';
        $content .= '<input type="checkbox" value="1" name="field[x_empty_on_search]" ' . ( !$field ? ' checked="checked"' : ($field->data( 'empty_on_search' ) ? ' checked="checked"' : '') ) . ' />';

        $settings['empty_on_search'][] = $content;

        return self::render_admin_settings( $settings );
    }

    public function process_field_settings( &$field ) {
        if ( !array_key_exists( 'x_options', $_POST['field'] ) )
            return;

        $options = stripslashes( trim( $_POST['field']['x_options'] ) );

        if ( !$options && $field->get_association() != 'tags' )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', !empty( $options ) ? explode( ',', $options ) : array() );

        if ( array_key_exists( 'x_empty_on_search', $_POST['field'] ) ) {
            $empty_on_search = (bool) intval( $_POST['field']['x_empty_on_search'] );
            $field->set_data( 'empty_on_search', $empty_on_search );
        }
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( $this->is_multiple() && $field->get_association() == 'meta' ) {
            if ( $value )
                $value =  implode( "\t", is_array( $value ) ? $value : array( $value ) );
        } elseif ( 'meta' == $field->get_association() ) {
            $value = is_array( $value ) ? $value[0] : $value;
        }

        parent::store_field_value( $field, $post_id, $value );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        if ( $this->is_multiple() && $field->get_association() == 'meta' ) {
            if ( !empty( $value ) )
                return explode( "\t", $value );
        }

        if ( !$value )
            return array();

        $value = is_array( $value ) ? $value : array( $value );
        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        if ( $field->get_association() == 'meta' ) {
            $value = $field->value( $post_id );

            return esc_attr( implode( ', ', $value ) );
        }

        return parent::get_field_html_value( $field, $post_id );
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( ! $value )
            return '';

        if ( $field->get_association() == 'category' ) {
            $args = array( 'include' => $value, 'hide_empty' => 0, 'fields' => 'names' );
            $term_names = get_terms( $field->get_association() == 'category' ? WPBDP_CATEGORY_TAX : WPBDP_TAGS_TAX,
                                     $args );
            return join( ', ', $term_names );
        } elseif ( $field->get_association() == 'tags' ) {
            return join( ', ', $value );
        } elseif ( $field->get_association() == 'meta' ) {
            return esc_attr( implode( ', ', $value ) );
        }

        return $value;
    }

    /**
     * @since 3.4.1
     */
    public function get_field_csv_value( &$field, $post_id ) {
        if ( 'meta' != $field->get_association() )
            return $field->plain_value( $post_id );

        $value = $field->value( $post_id );
        return esc_attr( implode( ',', $value ) );
    }

}
