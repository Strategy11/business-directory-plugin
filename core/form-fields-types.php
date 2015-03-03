<?php
/*
 * Standard form field types.
 */

require_once( WPBDP_PATH . '/core/class-form-field-type.php' );


class WPBDP_FieldTypes_TextField extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Textfield', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textfield';
    }

    public function convert_input( &$field, $input ) {
        $input = strval( $input );

        if ( $field->get_association() == 'tags' ) {
            return explode( ',', $input );
        }

        return $input;
    }

    public function get_field_value( &$field, $value ) {
        $value = parent::get_field_value( $field, $value );

        if ( $field->get_association() == 'tags' ) {
            $tags = implode( ',', $value );
            return $tags;
        }

        return $value;
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        if ( is_array( $value ) )
            $value = implode( ',', $value );

        $html = '';

        if ( $field->has_validator( 'date' ) )
            $html .= _x( 'Format 01/31/1969', 'form-fields api', 'WPBDM' );

        $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" />',
                          'wpbdp-field-' . $field->get_id(),
                          'listingfields[' . $field->get_id() . ']',
                          $field->is_required() ? 'inselect required' : 'inselect',
                          esc_attr( $value ) );

        return $html;
    }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'tags', 'meta' );
    }

}

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

        $url = strtolower( trim( is_array( $input ) ? $input[0] : $input ) );
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

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        if ( $context == 'search' ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value[0], $context, $extra );
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
        if ( 'meta' != $field->get_association() )
            return $this->convert_input( $field, $input );

        if ( ! $input )
            return array();

        if ( ! $this->is_multiple() )
            return array( str_replace( ',', '', $input ) );

        return explode( ',', $input );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
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

class WPBDP_FieldTypes_TextArea extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Textarea', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textarea';
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        // render textareas as textfields when searching
        if ( $context == 'search' ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra );
        }

        return sprintf('<textarea id="%s" name="%s" class="intextarea textarea %s">%s</textarea>',
                       'wpbdp-field-' . $field->get_id(),
                       'listingfields[' . $field->get_id() . ']',
                       $field->is_required() ? 'required' : '',
                       $value ? esc_attr( $value ) : '' );

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
            } elseif ( $field->data( 'allow_shortcodes' ) ) {
                $value = do_shortcode( nl2br( $value ) );
            } else {
                $value = nl2br( $value );
            }
        } else {
            $value = nl2br( $value );
        }

        return $value;
    }

}

class WPBDP_FieldTypes_RadioButton extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Radio button', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'radio';
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        $options = $field->data( 'options' ) ? $field->data( 'options' ) : array();

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
                'walker' => new CategoryFormInputWalker( 'radio', $value, $field ),
                'show_option_none' => '',
                'title_li' => '',
            ) );

            return $html;
        }

        $html = '';
        $i = 1;
        foreach ( $options as $option => $label ) {
            $css_classes = array();
            $css_classes[] = 'wpbdp-inner-radio';
            $css_classes[] = 'wpbdp-inner-radio-' . $i;
            $css_classes[] = 'wpbdp-inner-radio-' . WPBDP_Form_Field_Type::normalize_name( $label );

            $html .= sprintf( '<span class="%s" style="padding-right: 10px;"><input type="radio" name="%s" class="%s" value="%s" %s />%s</span>',
                              implode( ' ', $css_classes ),
                              'listingfields[' . $field->get_id() . ']',
                              $field->is_required() ? 'inradio required' : 'inradio',
                              $option,
                              $value == $option ? 'checked="checked"' : '',
                              esc_attr( $label )
                            );
            $i++;
        }

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
            if ( $association != 'meta' && $association != 'tags' )
            return '';

        $label = _x( 'Field Options (for select lists, radio buttons and checkboxes).', 'form-fields admin', 'WPBDM' ) . '<span class="description">(required)</span>';

        $content  = '<span class="description">Comma (,) separated list of options</span><br />';
        $content .= '<textarea name="field[x_options]" cols="50" rows="2">';

        if ( $field && $field->data( 'options' ) )
            $content .= implode( ',', $field->data( 'options' ) );
        $content .= '</textarea>';

        return self::render_admin_settings( array( array( $label, $content ) ) );
    }

    public function process_field_settings( &$field ) {
        if ( !array_key_exists( 'x_options', $_POST['field'] ) )
            return;

        $options = stripslashes( trim( $_POST['field']['x_options'] ) );

        if ( !$options && $field->get_association() != 'tags' )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', !empty( $options ) ? explode( ',', $options ) : array() );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );
        return is_array( $value ) ? $value[0] : $value;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        if ( $field->get_association() == 'category' || $field->get_association() == 'tags' ) {
            $term = get_term( is_array( $value ) ? $value[0] : $value,
                              $field->get_association() == 'category' ? WPBDP_CATEGORY_TAX : WPBDP_TAGS_TAX );
            return esc_attr( $term->name );
        }

        return strval( $value );
    }


}

class WPBDP_FieldTypes_MultiSelect extends WPBDP_FieldTypes_Select {

    public function __construct() {
        parent::__construct( _x('Multiple select list', 'form-fields api', 'WPBDM') );
        $this->set_multiple( true );
    }

    public function get_name() {
        return _x( 'Multiselect List', 'form-fields api', 'WPBDM' );
    }

    public function get_id() {
        return 'multiselect';
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

}

class WPBDP_FieldTypes_Checkbox extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Checkbox', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'checkbox';
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
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
                'walker' => new CategoryFormInputWalker( 'checkbox', $value, $field ),
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

class WPBDP_FieldTypes_Twitter extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Social Site (Twitter handle)', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'social-twitter';
    }

    public function setup_field( &$field ) {
        $field->add_display_flag( 'social' );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        // twitter fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        $value = str_ireplace( array('http://twitter.com/', 'https://twitter.com/', 'http://www.twitter.com/', 'https://www.twitter.com/'), '', $value );
        $value = rtrim( $value, '/' );
        $value = ltrim( $value, ' @' );

        return $value;
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        $html  = '';
        $html .= '<div class="social-field twitter">';
        $html .= sprintf('<a href="https://twitter.com/%s" class="twitter-follow-button" data-show-count="false" data-lang="%s">Follow @%s</a>',
                         $value, substr( get_bloginfo( 'language' ), 0, 2 ), $value);
        $html .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
        $html .= '</div>';

        return $html;
    }

}

class WPBDP_FieldTypes_Facebook extends WPBDP_Form_Field_Type {

    public function __construct() {
        parent::__construct( _x('Social Site (Facebook page)', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'social-facebook';
    }

    public function setup_field( &$field ) {
        $field->add_display_flag( 'social' );
    }

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        // facebook fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        $html  = '';
        $html .= '<div class="social-field facebook">';
        $html .= '<div id="fb-root"></div>';
        $html .= '<script>(function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
            fjs.parentNode.insertBefore(js, fjs);
          }(document, \'script\', \'facebook-jssdk\'));</script>';

        // data-layout can be 'box_count', 'standard' or 'button_count'
        // ref: https://developers.facebook.com/docs/reference/plugins/like/
        $html .= sprintf( '<div class="fb-like" data-href="%s" data-send="false" data-width="200" data-layout="button_count" data-show-faces="false"></div>', $value );
        $html .= '</div>';

        return $html;
    }

}

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

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        // LinkedIn fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context, $extra );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }

    public function get_field_html_value( &$field, $post_id ) {
        $value = $field->value( $post_id );

        static $js_loaded = false;

        $html  = '';
        if ( $value ) {
            if ( !$js_loaded ) {
                $html .= '<script src="//platform.linkedin.com/in.js" type="text/javascript"></script>';
                $js_loaded = true;
            }

            $html .= '<script type="IN/FollowCompany" data-id="' . intval( $value ) . '" data-counter="none"></script>';
        }

        return $html;
    }

}


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

    public function render_field_inner( &$field, $value, $context, &$extra=null ) {
        if ( $context == 'search' )
            return '';

        $html = '';
        $html .= sprintf( '<input type="hidden" name="listingfields[%d]" value="%s" />',
                          $field->get_id(),
                          $value
                        );

        $html .= '<div class="preview">';
        if ($value)
            $html .= wp_get_attachment_image( $value, 'thumb', false );

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
        $value = $field->value( $post_id );

        return '<br />' . wp_get_attachment_image( $value, 'thumb', false );
    }

}

// Custom category walker (used when rendering category fields using radios or checkboxes)
class CategoryFormInputWalker extends Walker {
    var $tree_type = 'category';
    var $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

    private $input_type;
    private $selected;
    private $field;

    public function __construct( $input_type='radio', $selected=null, &$field=null ) {
        $this->input_type = $input_type;
        $this->selected = $selected;
        $this->field = $field;
    }

    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        switch ( $this->input_type ) {
            case 'checkbox':
                $output .= '<div class="wpbdmcheckboxclass">';
                $output .= sprintf( '<input type="checkbox" class="%s" name="%s" value="%s" %s style="margin-left: %dpx;" />%s',
                                    $this->field->is_required() ? 'required' : '',
                                    'listingfields[' . $this->field->get_id() . '][]',
                                    $category->term_id,
                                    in_array( $category->term_id, is_array( $this->selected ) ? $this->selected : array( $this->selected ) ) ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name )
                                  );
                $output .= '</div>';
                break;
            case 'radio':
            default:
                $output .= sprintf( '<input type="radio" name="%s" class="%s" value="%s" %s style="margin-left: %dpx;"> %s<br />',
                                    'listingfields[' . $this->field->get_id() . ']',
                                    $this->field->is_required() ? 'inradio required' : 'inradio',
                                    $category->term_id,
                                    $this->selected == $category->term_id ? 'checked="checked"' : '',
                                    $depth * 10,
                                    esc_attr( $category->name )
                                  );
                break;
        }

    }
}
