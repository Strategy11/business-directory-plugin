<?php
/*
 * Standard form field types.
 */

require_once( WPBDP_PATH . 'api/form-fields.php' );


class WPBDP_FieldTypes_TextField extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Textfield', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textfield';
    }

    public function convert_input( &$field, $input ) {
        if ( $field->get_association() == 'tags' ) {
            return explode( ',', $input );
        }

        return parent::convert_input( $field, $input );
    } 

    public function render_field_inner( &$field, $value, $context ) {
        if ( is_array( $value ) )
            $value = implode( ',', $input );

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

    // public function render_textfield(&$field, $value=null, $display_context=null) {
    //     if ( is_array( $value ) && ( in_array( $field->type, array('social-twitter', 'social-linkedin', 'social-facebook') ) || $field->validator != 'URLValidator' ) )
    //         $value = $value[0];

    //     if ($display_context != 'search' && !in_array( $field->type, array('social-twitter', 'social-linkedin', 'social-facebook') ) &&  $field->validator == 'URLValidator') {
    //         $value_url = is_array($value) ? $value[0] : $value;
    //         $value_title = is_array($value) ? $value[1] : '';

    //         $html .= sprintf('<span class="sublabel">%s</span>', _x('URL:', 'form-fields api', 'WPBDM'));
    //         $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" />',
    //                         'wpbdp-field-' . $field->id,
    //                         'listingfields[' . $field->id . '][0]',
    //                         $field->is_required ? 'inselect required' : 'inselect',
    //                         esc_attr($value_url) );

    //         $html .= sprintf('<span class="sublabel">%s</span>', _x('Link Text (optional):', 'form-fields api', 'WPBDM'));
    //         $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox" value="%s" placeholder="" />',
    //                         'wpbdp-field-' . $field->id . '-title',
    //                         'listingfields[' . $field->id . '][1]',
    //                         esc_attr($value_title) );
    //     } else {
    //         $html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" />',
    //                         'wpbdp-field-' . $field->id,
    //                         'listingfields[' . $field->id . ']',
    //                         $field->is_required ? 'inselect required' : 'inselect',
    //                         esc_attr($value) );
    //     }

    //     return $html;
    // }

    public function get_supported_associations() {
        return array( 'title', 'excerpt', 'tags', 'meta' );
    }

}

class WPBDP_FieldTypes_Select extends WPBDP_FormFieldType {

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
        $res = is_array( $input ) ? $input : array( $input );

        if ( $field->get_association() == 'category' )
            $res = array_map( 'intval', $res );

        return $res;
    }

       // TODO: maybe this should be in get_value()
    // public function render_select(&$field, $value=null, $display_context=null, $multiselect=false) {
    //     if (!is_array($value))  
    //         return $this->render_select($field, explode("\t", $value), $display_context, $multiselect);    

    public function render_field_inner( &$field, $value, $context ) {
        $options = $field->data( 'options' ) ? $field->data( 'options' ) : array();

        $html = '';

        $html .= sprintf( '<select id="%s" name="%s" %s class="%s %s">',
                          'wpbdp-field-' . $field->get_id(),
                          'listingfields[' . $field->get_id() . ']' . ( $this->multiselect ? '[]' : '' ),
                          $this->multiselect ? 'multiple="multiple"' : '',
                          $this->multiselect ? 'inselectmultiple' : 'inselect',
                          $field->is_required() ? 'required' : '');

        if ( $context == 'search' ) {
            // add a "none" option when displaying this field in a search context
            $html .= sprintf('<option value="%s">%s</option>', '', '');
        }

        if ( $field->get_association() == 'category' || $field->get_association() == 'tags' ) {
            $terms = get_terms( $field->get_association() == 'tags' ? wpbdp_tags_taxonomy() : wpbdp_categories_taxonomy(), 'hide_empty=0&hierarchical=1' );
            $html .= walk_category_dropdown_tree( $terms, 0, array( 'show_count' => 0, 'selected' => 0 ) );
        } else {
            foreach ( $options as $option => $label ) {
                $html .= sprintf( '<option value="%s" %s>%s</option>',
                                  esc_attr( $option ),
                                  ( $this->is_multiple() && in_array( $option, $value, true ) || $option == $value ) ? 'selected="selected"' : '',
                                  esc_attr( $label ) );
            }
        }

        $html .= '</select>';              

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        if ( $association != 'meta' )
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

        $options = trim( $_POST['field']['x_options'] );

        if ( !$options )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', explode(',', $options ) );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( $this->is_multiple() && $field->get_association() == 'meta' ) {
            $value =  implode( "\t", is_array( $value ) ? $value : array( $value ) );
        }

        parent::store_field_value( $field, $post_id, $value );        
    }

    public function get_field_html_value( &$field, $post_id ) {
        if ( $this->is_multiple() && $field->get_association() == 'meta' ) {
            return esc_attr( implode( ', ', $field->value( $post_id ) ) );
        }

        return parent::get_field_html_value( $field, $post_id );
    }    

}

class WPBDP_FieldTypes_TextArea extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Textarea', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'textarea';
    }

    public function render_field_inner( &$field, $value, $context ) {
        // render textareas as textfields when searching
        if ( $context == 'search' ) {
            global $wpbdp;
            return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context );
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

}

class WPBDP_FieldTypes_RadioButton extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Radio button', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'radio';
    }

    public function render_field_inner( &$field, $value, $context ) {
        $options = $field->data( 'options' ) ? $field->data( 'options' ) : array();

        $html = '';

        foreach ( $options as $option => $label ) {
            $html .= sprintf( '<span style="padding-right: 10px;"><input type="radio" name="%s" class="%s" value="%s" %s />%s</span>',
                              'listingfields[' . $field->get_id() . ']',
                              $field->is_required() ? 'inradio required' : 'inradio',
                              $option,
                              $value == $option ? 'checked="checked"' : '',
                              esc_attr( $label )
                            );            
        }

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

    public function render_field_settings( &$field=null, $association=null ) {
        if ( $association != 'meta' )
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

        $options = trim( $_POST['field']['x_options'] );

        if ( !$options )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', explode(',', $options ) );
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

}

class WPBDP_FieldTypes_Checkbox extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Checkbox', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'checkbox';
    }

    public function get_field_value( &$field, $post_id ) {
        $value = parent::get_field_value( $field, $post_id );

        if ( is_string( $value ) ) {
            return explode( "\t", $value );
        }

        return $value; 
    }

    public function render_field_inner( &$field, $value, $context ) {
        $options = $field->data( 'options' ) ? $field->data( 'options') : array();

        $html = '';
        foreach ( $options as $option_key => $label ) {
            $html .= sprintf( '<div class="wpbdmcheckboxclass"><input type="checkbox" class="%s" name="%s" value="%s" %s/> %s</div>',
                              $field->is_required() ? 'required' : '',
                             'listingfields[' . $field->get_id() . '][]',
                              $option_key,
                              in_array( $option_key, $value ) ? 'checked="checked"' : '',
                              esc_attr( $label ) );
        }

        $html .= '<div style="clear:both;"></div>';

        return $html;
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    } 

    public function render_field_settings( &$field=null, $association=null ) {
        if ( $association != 'meta' )
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

        $options = trim( $_POST['field']['x_options'] );

        if ( !$options )
            return new WP_Error( 'wpbdp-invalid-settings', _x( 'Field list of options is required.', 'form-fields admin', 'WPBDM' ) );

        $field->set_data( 'options', explode(',', $options ) );
    }

    public function store_field_value( &$field, $post_id, $value ) {
        if ( $field->get_association() == 'meta' ) {
            $value =  implode( "\t", is_array( $value ) ? $value : array( $value ) );
        }

        parent::store_field_value( $field, $post_id, $value );        
    }

    public function get_field_html_value( &$field, $post_id ) {
        if ( $field->get_association() == 'meta' ) {
            return esc_attr( implode( ', ', $field->value( $post_id ) ) );
        }

        return parent::get_field_html_value( $field, $post_id );
    }

}

class WPBDP_FieldTypes_Twitter extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Social Site (Twitter handle)', 'form-fields api', 'WPBDM') );
    }   

    public function get_id() {
        return 'social-twitter';
    }

    public function render_field_inner( &$field, $value, $context ) {
        // twitter fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }        

// function _wpbdp_display_twitter_button($handle, $settings=array()) {
//     // in case $handle comes with a URLValidator
//     if ( is_array( $handle ) ) $handle = $handle[0];

//     $handle = str_ireplace( array('http://twitter.com/', 'https://twitter.com/', 'http://www.twitter.com/', 'https://www.twitter.com/'), '', $handle );
//     $handle = rtrim( $handle, '/' );
//     $handle = ltrim( $handle, ' @' );
    
//     $html  = '';

//     $html .= '<div class="social-field twitter">';
//     $html .= sprintf('<a href="https://twitter.com/%s" class="twitter-follow-button" data-show-count="false" data-lang="%s">Follow @%s</a>',
//                      $handle, wpbdp_getv($settings, 'lang', 'en'), $handle);
//     $html .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
//     $html .= '</div>';

//     return $html;
// }

}

class WPBDP_FieldTypes_Facebook extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Social Site (Facebook page)', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'social-facebook';
    }

    public function render_field_inner( &$field, $value, $context ) {
        // facebook fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context );
    }   

    public function get_supported_associations() {
        return array( 'meta' );
    }        

// function _wpbdp_display_facebook_button($page) {
//     if ( is_array( $page ) ) $page = $page[0];

//     $html  = '';

//     $html .= '<div class="social-field facebook">';

//     $html .= '<div id="fb-root"></div>';
//     $html .= '<script>(function(d, s, id) {
//         var js, fjs = d.getElementsByTagName(s)[0];
//         if (d.getElementById(id)) return;
//         js = d.createElement(s); js.id = id;
//         js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
//         fjs.parentNode.insertBefore(js, fjs);
//       }(document, \'script\', \'facebook-jssdk\'));</script>';

//     // data-layout can be 'box_count', 'standard' or 'button_count'
//     // ref: https://developers.facebook.com/docs/reference/plugins/like/
//     $html .= sprintf('<div class="fb-like" data-href="%s" data-send="false" data-width="200" data-layout="button_count" data-show-faces="false"></div>', $page);
//     $html .= '</div>';

//     return $html;
// }    
}

class WPBDP_FieldTypes_LinkedIn extends WPBDP_FormFieldType {

    public function __construct() {
        parent::__construct( _x('Social Site (LinkedIn profile)', 'form-fields api', 'WPBDM') );
    }

    public function get_id() {
        return 'social-linkedin';
    }

    public function render_field_inner( &$field, $value, $context ) {
        // LinkedIn fields are rendered as normal textfields
        global $wpbdp;
        return $wpbdp->formfields->get_field_type( 'textfield' )->render_field_inner( $field, $value, $context );
    }

    public function get_supported_associations() {
        return array( 'meta' );
    }    

// function _wpbdp_display_linkedin_button($value) {
//     if ( is_array( $value ) ) $value = $value[0];

//     static $js_loaded = false;

//     $html  = '';

//     if ($value) {
//         if (!$js_loaded) {
//             $html .= '<script src="//platform.linkedin.com/in.js" type="text/javascript"></script>';
//             $js_loaded = true;
//         }

//         $html .= '<script type="IN/FollowCompany" data-id="1035" data-counter="none"></script>';
//     }

//     return $html;
// }

}
