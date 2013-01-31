<?php
/**
 * Form fields API.
 */

if (!class_exists('WPBDP_FormFields')) {


class WPBDP_FormFieldType {

    private $name = null;
    private $associations = array();
    private $display_contexts = array();

    public function __construct( $name ) {
        $this->name = trim( $name );
    }

    public function get_id() {
        return get_class( $this );
    }

    public function get_name() {
        return $this->name;
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
                $value = get_the_terms( $post_id, wpbdp_categories_taxonomy() );
                break;
            case 'tags':
                $value = get_the_terms( $post_id, wpbdp_tags_taxonomy() );
                break;
            case 'meta':
            default:
                $value = get_post_meta( $post_id, '_wpbdp[fields][' . $field->get_id() . ']', true );
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
                $value = get_the_term_list( $post_id, wpbdp_categories_taxonomy(), '', ', ', '' );
                break;
            case 'tags':
                $value = get_the_term_list( $post_id, wpbdp_tags_taxonomy(), '', ', ', '' );
                break;
            case 'meta':
            default:
                $value = $this->get_field_value( $field, $post_id );
                break;
        }

        return $value;
    }

    public function get_field_plain_value( &$field, $post_id ) {
        return $this->get_field_value( $field, $post_id );
    }

    public function is_empty_value( $value ) {
        return empty( $value );
    }

    public function convert_input( &$field, $input ) {
        return $input;
    }

    // this function should not try to hide values depending on field, context or value itself.
    public function display_field( &$field, $post_id, $display_context ) {
        return self::standard_display_wrapper( $field, $field->html_value() );
    }

    public function render_field_inner( &$field, $value, $render_context ) {
        return '';
    }

    public function render_field( &$field, $value, $render_context ) {
        $html = '';

        switch ( $render_context ) {
            case 'search':
                $html .= sprintf( '<div class="search-filter %s">', $field->get_field_type()->get_id() );
                $html .= sprintf( '<div class="label"><label>%s</label></div>', esc_attr( $field->get_label() ) );
                $html .= '<div class="field inner">';
                $html .= $this->render_field_inner( $field, $value, $render_context );
                $html .= '</div>';
                $html .= '</div>';

                break;

            case 'submit':
            case 'edit':
            default:
                $html .= sprintf( '<div class="wpbdp-form-field %s %s %s">',
                                  $field->get_field_type()->get_id(),
                                  $field->get_description() ? 'with-description' : '',
                                  implode( ' ', $field->get_validators() ) );
                $html .= '<div class="wpbdp-form-field-label">';
                $html .= sprintf( '<label for="%s">%s</label>', 'wpbdp-field-' . $field->get_id(), esc_attr( $field->get_label() ) );

                if ( $field->get_description() )
                    $html .= sprintf( '<span class="field-description">(%s)</span>', $field->get_description() );

                $html .= '</div>';
                $html .= '<div class="wpbdp-form-field-html wpbdp-form-field-inner">';
                $html .= $this->render_field_inner( $field, $value, $render_context );
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
            $wpdb->query( $wpdb->prepare( "DELETE * FROM {$wpdb->postmeta} WHERE meta_key = %s", '_wpbdp[fields][' . $field->get_id() . ']' ) );
        }
    }

    /* Utils. */
    public static function standard_display_wrapper( $labelorfield, $content=null, $extra_classes='', $args=array() ) {
        $css_classes = 'field-value ';

        if ( is_object( $labelorfield ) ) {
            $css_classes .= 'wpbdp-field-' . strtolower( str_replace( array( ' ', '/' ), '', $labelorfield->get_label() ) ) . ' ' . $labelorfield->get_association() . ' ';
            $label = $labelorfield->get_label();
        } else {
            $label = $labelorfield;
        }

        $html  = '';
        $html .= '<div class="' . $css_classes . '">';
        $html .= '<label>' . esc_html( $label ) . ':</label> ';
        
        if ($content)
            $html .= '<span class="value">' . $content . '</span>';
        
        $html .= '</div>';

        return $html;
    }

}

/**
 * Represents a single field from the database. This class can not be instantiated directly.
 *
 * @since 2.3
 */
class WPBDP_FormField {

    private $id;
    private $type;
    private $association;

    private $label;
    private $description;    

    private $weight = 0;

    private $validators = array();
    
    private $display_flags = array();
    private $extra_data = null;

    private $settings = array(); /* field-type specific settings or options */
    

    public function __construct( $attrs=array() ) {
        $defaults = array(
            'id' => 0,
            'label' => '',
            'description' => '',
            'type' => 'textfield',
            'association' => 'meta',
            'weight' => 0,
            'validators' => array(),
            'display_options' => array( 'excerpt', 'listing', 'search' ),
            'field_data' => array()
        );
        $attrs = wp_parse_args( $attrs, $defaults );

        $formfields = WPBDP_FormFields::instance();

        $this->id = intval( $attrs['id'] );
        $this->label = $attrs['label'];
        $this->description = $attrs['description'];
        $this->type = is_object( $attrs['type'] ) ? $attrs['type'] : WPBDP_FormFields::instance()->get_field_type( $attrs['type'] );

        if ( !$this->type )
            throw new Exception( _x( 'Invalid form field type', 'form-fields-api', 'WPBDM' ) );

        $this->association = $attrs['association'];
        $this->weight = intval( $attrs['weight'] );

        /* Validators */
        if ( isset( $attrs['validator'] ) )
            $this->validators[] = $attrs['validator'];

        // TODO: make sure validators are valid here
        if ( is_array( $attrs['validators'] ) ) {
            foreach ( $attrs['validators'] as $validator ) {
                if ( !in_array( $validator, $this->validators, true ) )
                    $this->validators[] = $validator;
            }
        }

        if ( isset( $attrs['is_required'] ) && $attrs['is_required'] )
            $this->validators[] = 'required';

        /* display_options */
        if ( !wpbdp_getv( $attrs['display_options'], 'hide_field', false ) ) {
            // compatible with display_options from < 2.3 and > 2.1.3
            foreach ( array( 'show_in_excerpt' => 'excerpt', 'show_in_listing' => 'listing', 'show_in_search' => 'search' ) as $oldkey => $newval ) {
                if ( in_array( $newval , $attrs['display_options'], true ) || ( isset( $attrs['display_options'][$oldkey] ) && $attrs['display_options'][$oldkey] ) )
                    $this->display_flags[] = $newval;
            }
        }

        if ( isset( $attrs['field_data'] ) ) {
            $this->extra_data = $attrs['field_data'];
        }

        if ( in_array( $this->association, array( 'category', 'tags' ), true ) ) {

            $terms = get_terms( $this->association == 'tags' ? wpbdp_tags_taxonomy() : wpbdp_categories_taxonomy(), 'hide_empty=0&hierarchical=1' );
            $options = array();

            // wpbdp_debug_e( $terms );

            foreach ( $terms as &$term ) {
                $k = $this->association == 'tags' ? $term->slug : $term->term_id;
                $options [ $k ] = $term->name;
            }

            $this->settings['options'] = $options;
        } else {
            // handle some special extra data from previous BD versions
            if ( isset( $attrs['field_data'] ) && isset( $attrs['field_data']['options'] )  ) {
                $options = array();

                foreach ( $attrs['field_data']['options'] as $option_value ) {
                    if ( is_array( $option_value ) )
                        $options[ $option_value[0] ] = $option_value[1];
                    else
                        $options[ $option_value ] = $option_value;
                }

                $this->settings['options'] = $options;
            }
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function &get_field_type() {
        return $this->type;
    }    

    public function get_association() {
        return $this->association;
    }

    public function get_label() {
        return $this->label;
    }

    public function get_description() {
        return $this->description;
    }

    public function get_short_name() {
        // TODO: change this to use new APIs
        $short_names = wpbdp_formfields_api()->getShortNames();
        return $short_names[$field->id];
    }

    public function &get_validators() {
        return $this->validators;
    }

    public function has_validator( $validator ) {
        return in_array( $validator, $this->validators, true );
    }

    public function is_required() {
        return in_array( 'required', $this->validators, true );
    }

    public function display_in( $context ) {
        return in_array( $context, $this->display_flags, true);
    }

    /**
     * Returns field-type specific configuration options for this field.
     * @param string $key configuration key name
     * @return mixed|array if $key is ommitted an array of all key/values will be returned
     */
    public function settings( $key=null ) {
        if ( !$key )
            return $this->settings;

        return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
    }

    /**
     * Returns this field's raw value for the given post.
     * @param int|object $post_id post ID or object.
     * @return mixed
     */
    public function value( $post_id ) {
        if ( !get_post_type( $post_id ) == wpbdp_post_type() )
            return null;

        $value = $this->type->get_field_value( $this, $post_id );
        $value = apply_filters( 'wpbdp_formfield_value', $value, $post_id, $this );

        return $value;
    }

    /**
     * Returns this field's HTML value for the given post. Useful for display.
     * @param int|object $post_id post ID or object.
     * @return string valid HTML.
     */
    public function html_value( $post_id ) {
        return $this->type->get_field_html_value( $this, $post_id );
    }

    /**
     * Returns this field's value as plain text. Useful for emails or cooperation between modules.
     * @param int|object $post_id post ID or object.
     * @return string
     */
    public function plain_value( $post_id ) {
        return $this->type->get_field_plain_value( $this, $post_id );
    }

    /**
     * Converts input from forms to a value useful for this field.
     * @param mixed $input form input.
     * @return mixed
     */
    public function convert_input( $input=null ) {
        return $this->type->convert_input( $field, $input );
    }

    /**
     * Returns HTML apt for display of this field's value.
     * @param int|object $post_id post ID or object
     * @param string $display_context the display context. defaults to 'listing'.
     * @return string
     */
    public function display( $post_id, $display_context='listing' ) {
        if ( in_array( 'email', $this->validators, true ) && !wpbdp_get_option('override-email-blocking') )
            return '';

        if ( $this->type->is_empty_value( $this->value( $post_id ) ) )
            return '';
        
        return $this->type->display_field( $this, $post_id, $display_context );
    }

    /**
     * Returns HTML apt for displaying this field in forms.
     * @param mixed $value the value to be displayed. defaults to null.
     * @param string $display_context the rendering context. defaults to 'submit'.
     * @return string
     */
    public function render( $value=null, $display_context='submit' ) {
        return $this->type->render_field( $this, $value, $display_context );
    }

    /**
     * Tries to save this field to the database. If successfully, sets the new id too.
     * @return mixed True if successfully created, WP_Error in the other case
     */
    public function save() {
        return new WP_Error( 'wpbdp-save-error', '' );
    }

    /**
     * Tries to delete this field from the database.
     * @return mixed True if successfully deleted, WP_Error in the other case
     */
    public function delete() {
        if ( !$this->id )
            return new WP_Error( 'wpbdp-delete-error', _x( 'Invalid field ID', 'form-fields-api', 'WPBDM' ) );

        // TODO
        // if ( in_array( $field->association, WPBDP_FormFields::get_required_field_associations(), true ) )
        //     return new WP_Error( 'wpbdp-delete-error', _x( "This form field can't be deleted because it is required for the plugin to work.", 'form-fields api', 'WPBDM' ) );

        global $wpdb;

        if ( $wpdb->query( $wpdb->prepare( "DELETE FROM  {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $this->id ) ) !== false ) {
            $this->type->cleanup( $this );
            $this->id = 0;
        } else {
            return new WP_Error( 'wpbdp-delete-error', _x( 'An error occurred while trying to delete this field.', 'form-fields-api', 'WPBDM' ) );
        }

        return true;
    }

    /**
     * Creates a WPBDP_FormField from a database record.
     * @param int $id the database record ID.
     * @return WPBDP_FormField a valid WPBDP_FormField if the record exists or null if not.
     */
    public static function get( $id ) {
        global $wpdb;

        $field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $id ) );

        if ( !$field )
            return null;

        $field = (array) $field;

        if ( isset( $field['display_options'] ) )
            $field['display_options'] = unserialize( $field['display_options'] );

        if ( isset( $field['field_data'] ) )
            $field['field_data'] = unserialize( $field['field_data'] );

        try {
            return new WPBDP_FormField( $field );
        } catch (Exception $e) {
            return null;
        }
    }

}

require_once( WPBDP_PATH . 'api/form-fields-types.php' );

class WPBDP_FormFields {

    private $associations = array();
    private $association_flags = array();

    private $field_types = array();

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }   

    private function __construct() {
        // register core associations
        $this->register_association( 'title', _x( 'Post Title', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'content', _x( 'Post Content', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'excerpt', _x( 'Post Excerpt', 'form-fields api', 'WPBDM' ) );
        $this->register_association( 'category', _x( 'Post Category', 'form-fields api', 'WPBDM' ), array( 'required', 'unique' ) );
        $this->register_association( 'tags', _x( 'Post Tags', 'form-fields api', 'WPBDM' ) );
        $this->register_association( 'meta', _x( 'Post Metadata', 'form-fields api', 'WPBDM' ) );

        // register core field types
        $this->field_types['textfield'] = new WPBDP_FieldTypes_TextField();
        $this->field_types['select'] = new WPBDP_FieldTypes_Select();
        $this->field_types['textarea'] = new WPBDP_FieldTypes_TextArea();
        $this->field_types['radio'] = new WPBDP_FieldTypes_RadioButton();
        $this->field_types['multiselect'] = new WPBDP_FieldTypes_MultiSelect();
        $this->field_types['checkbox'] = new WPBDP_FieldTypes_Checkbox();
        $this->field_types['social-twitter'] = new WPBDP_FieldTypes_Twitter();
        $this->field_types['social-facebook'] = new WPBDP_FieldTypes_Facebook();
        $this->field_types['social-linkedin'] = new WPBDP_FieldTypes_LinkedIn();
    }

    /**
     * Registers a new association within the form fields API.
     * @param string $association association id
     * @param string $name human-readable name
     * @param array $flags association flags
     */
    public function register_association( $association, $name='', $flags=array() ) {
        if ( isset( $this->associations[$association] ) )
            return false;

        $this->associations[$association] = $name ? $name : $association;
        $this->association_flags[$association] = is_array( $flags ) ? $flags : array( strval( $flags ) );
    }

    /**
     * Returns the known form field associations.
     * @return array associative array with key/name pairs
     */
    public function &get_associations() {
        return $this->associations;
    }

    /**
     * Returns associations marked with the given flags.
     * @param string|array $flags flags to be checked
     * @param boolean $any if True associations marked with any (and not all) of the flags will also be returned
     * @return array
     */
    public function &get_associations_with_flag( $flags, $any=false ) {
        if ( is_string( $flags ) )
            $flags = array( $flags );

        $res = array();

        foreach ( $this->association_flags as $association => $association_flags ) {
            $intersection = array_intersect( $flags, $association_flags );

            if ( ( $any && ( count( $intersection ) > 0 ) ) || ( !$any && ( count( $intersection ) == count( $flags ) )  ) )
                $res[] = $association;
        }

        return $res;
    }    

    public function &get_required_field_associations() {
        return $this->get_associations_with_flag( 'required' );
    }

    public function &get_field_type( $field_type ) {
        $field_type_obj = wpbdp_getv( $this->field_types, $field_type, null );
        return $field_type_obj;
    }

    public function &get_field_types() {
        return $this->field_types;
    }

    public function get_validators() {
        $validators = WPBDP_FieldValidation::instance()->get_validators();
        return $validators;
    }

    public function register_field_type( $field_type_class ) {
        $this->field_types[ $field_type_class ] = new $field_type_class();
    }

    public function &get_fields() {
        global $wpdb;

        $res = array();

        $field_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC" );

        foreach ( $field_ids as $field_id ) {
            if ( $field = WPBDP_FormField::get( $field_id ) )
                $res[] = $field;
        }

        return $res;
    }

    public function get_missing_required_fields() {
        global $wpdb;

        $missing = $this->get_required_field_associations();

        $sql_in = '(\'' . implode( '\',\'', $missing ) . '\')';
        $res = $wpdb->get_col( "SELECT association FROM {$wpdb->prefix}wpbdp_form_fields WHERE association IN {$sql_in} GROUP BY association" );

        return array_diff( $missing, $res );
    }

    public function create_default_fields( $identifiers=array() ) {
        $default_fields = array(
            'title' => array( 'label' => __('Business Name', 'WPBDM'), 'type' => 'textfield', 'association' => 'title', 'weight' => 9,
                              'validators' => array( 'required' ), 'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'category' => array( 'label' => __('Business Genre', 'WPBDM'), 'type' => 'select', 'association' => 'category', 'weight' => 8,
                                 'validators' => array( 'required' ), 'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'excerpt' => array( 'label' => __('Short Business Description', 'WPBDM'), 'type' => 'textarea', 'association' => 'excerpt', 'weight' => 7,
                                'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'content' => array( 'label' => __("Long Business Description","WPBDM"), 'type' => 'textarea', 'association' => 'content', 'weight' => 6,
                                'validators' => array( 'required' ), 'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'meta0' => array( 'label' => __("Business Website Address","WPBDM"), 'type' => 'textfield', 'association' => 'meta', 'weight' => 5,
                              'validators' => array( 'url' ), 'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'meta1' => array( 'label' => __("Business Phone Number","WPBDM"), 'type' => 'textfield', 'association' => 'meta', 'weight' => 4,
                              'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'meta2' => array( 'label' => __("Business Fax","WPBDM"), 'type' => 'textfield', 'association' => 'meta', 'weight' => 3,
                              'display_options' => array( 'excerpt', 'listing', 'search' ) ),
            'meta3' => array( 'label' => __("Business Contact Email","WPBDM"), 'type' => 'textfield', 'association' => 'meta', 'weight' => 2,
                             'validators' => array( 'email', 'required' ), 'display_options' => array( 'excerpt', 'listing' ) ),
            'meta4' => array( 'label' => __("Business Tags","WPBDM"), 'type' => 'textfield', 'association' => 'tags', 'weight' => 1,
                              'display_options' => array( 'excerpt', 'listing', 'search' ) )
        );      

        $fields_to_create = $identifiers ? array_intersect_key( $default_fields, array_flip ( $identifiers ) ) : $default_fields;

        foreach ( $fields_to_create as &$f) {
            $field = new WPBDP_FormField( $f );
            $field->save();
        }
    }

}

/*
 * Validation.
 */

function WPBDP_ValidationError( $msg, $stop_validation=false ) {
    if ( $stop_validation )
        return new WP_Error( 'wpbdp-validation-error-stop', $msg );

    return new WP_Error( 'wpbdp-validation-error', $msg );
}


class WPBDP_FieldValidation {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Get the set of publicly available validators.
     * @return array associative array with validator name as key and display name as value
     */
    public function get_validators() {
        $validators = array(
            'email' => _x('Email Validator', 'form-fields-api', 'WPBDM'),
            'url' => _x('URL Validator', 'form-fields-api', 'WPBDM'),
            'integer_number' => _x('Whole Number Validator', 'form-fields-api', 'WPBDM'),
            'decimal_number' => _x('Decimal Number Validator', 'form-fields-api', 'WPBDM'),
            'date_' => _x('Date Validator', 'form-fields-api', 'WPBDM')
        );

        return $validators;
    }

    public function validate_value( $value, $validator, $args=array() ) {
        $dummyfield = new StdClass();
        $dummyfield->label = 'Unlabeled Field';
        $dummyfield->data = $value; 
        
        $res = self::validate_field( $dummyfield, $validator, $args );

        if ( is_wp_error( $res ) )
            return false;

        return true;
    }

    public function validate_field( $field, $validator, $args=array() ) {
        return call_user_func( array( $this, $validator ) , $field, $args );
    }

    /* Required validator */
    private function required( $field, $args=array() ) {
        $args = wp_parse_args( $args, array( 'allow_whitespace' => false ) );

        if ( !$field->data || ( is_string( $field->data ) && !$args['allow_whitespace'] && !trim( $field->data ) ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
    }

    /* URL Validator */
    private function url( $field, $args=array() ) {
        if ( !preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $field->data ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid URL format required. Include http://', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) )  );
    }

    /* EmailValidator */
    private function email( $field, $args=array() ) {
        if ( !wpbusdirman_isValidEmailAddress( $field->data ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is badly formatted. Valid Email format required.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
    }

    /* IntegerNumberValidator */
    private function integer_number( $field, $args=array() ) {
        if ( !ctype_digit( $field->data ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be a number. Decimal values are not allowed.', 'form-fields-api validation', 'WPBDM' ), esc_att ( $field->label) ) );
    }

    /* DecimalNumberValidator */
    private function decimal_number( $field, $args=array() ) {
        if ( !is_numeric( $field->data ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be a number.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label) ) );
    }

    /* DateValidator */
    private function date_( $field, $args=array() ) {
        $args = wp_parse_args( $args, array( 'format' => 'm/d/Y' ) );

        $value = $field->data;

        // TODO: validate with format
        list( $m, $d, $y ) = explode( '/', $value );

        if ( !is_numeric( $m ) || !is_numeric( $d ) || !is_numeric( $y ) || !checkdate( $m, $d, $y ) )
            return WPBDP_ValidationError( sprintf( _x( '%s must be in the format MM/DD/YYYY.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ) ) );
    }

    private function any_of( $field, $args=array() ) {
        $args = wp_parse_args( $args, array( 'values' => array(), 'formatter' => create_function( '$x', 'return join(",", $x);' ) ) );
        extract( $args, EXTR_SKIP );

        if ( is_string( $values ) )
            $values = explode( ',', $values );

        if ( !in_array( $field->data, $values ) )
            return WPBDP_ValidationError( sprintf( _x( '%s is invalid. Value most be one of %s.', 'form-fields-api validation', 'WPBDM' ), esc_attr( $field->label ), call_user_func( $formatter, $values ) ) );        
    }

}

}