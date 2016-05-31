<?php
/**
 * Represents a single field from the database. This class can not be instantiated directly.
 *
 * @since 2.3
 */
class WPBDP_Form_Field {

    private $id;
    private $type;
    private $association;

    private $shortname;
    private $label;
    private $description;
    private $tag;

    private $weight = 0;

    private $validators = array();

    private $display_flags = array();
    private $field_data = array();

    public $css_classes = array();
    public $html_attributes = array();


    public function __construct( $attrs=array() ) {
        $defaults = array(
            'id' => 0,
            'shortname' => '',
            'label' => '',
            'tag' => '',
            'description' => '',
            'field_type' => 'textfield',
            'association' => 'meta',
            'weight' => 0,
            'validators' => array(),
            'display_flags' => array(),
            /*'display_flags' => array( 'excerpt', 'listing', 'search' ),*/
            'field_data' => array()
        );

        $attrs = wp_parse_args( $attrs, $defaults );

        $formfields = WPBDP_FormFields::instance();

        $this->id = intval( $attrs['id'] );
        $this->shortname = $attrs['shortname'];
        $this->label = $attrs['label'];
        $this->description = $attrs['description'];
        $this->type = is_object( $attrs['field_type'] ) ? $attrs['field_type'] : WPBDP_FormFields::instance()->get_field_type( $attrs['field_type'] );

        if ( !$this->type )
            throw new Exception( _x( 'Invalid form field type', 'form-fields-api', 'WPBDM' ) );

/*        if ( !$this->type ) // temporary workaround related to 3.0 upgrade issues (issue #365)
            $this->type = WPBDP_FormFields::instance()->get_field_type( 'textfield' );*/

        $this->association = $attrs['association'];
        $this->weight = intval( $attrs['weight'] );

        /* Validators */
        if ( is_array( $attrs['validators'] ) ) {
            foreach ( $attrs['validators'] as $validator ) {
                if ( $validator && !in_array( $validator, $this->validators, true ) )
                    $this->validators[] = $validator;
            }
        }

        /* display_options */
        $this->display_flags = $attrs['display_flags'];
        $this->field_data = $attrs['field_data'];
        $this->tag = trim( $attrs['tag'] );

        if ( $this->association == 'category' ) {
            $this->field_data['options'] = array();
        // } elseif ( $this->association == 'category' ) {
        //     // TODO: make this hierarchical (see https://codex.wordpress.org/Function_Reference/Walker_Class)
        //     $terms = get_terms( $this->association == 'tags' ? WPBDP_TAGS_TAX : wpbdp_categories_taxonomy(), 'hide_empty=0&hierarchical=1' );
        //     $options = array();

        //     foreach ( $terms as &$term ) {
        //         $k = $this->association == 'tags' ? $term->slug : $term->term_id;
        //         $options [ $k ] = $term->name;
        //     }

        //     $this->field_data['options'] = $options;
        } else {
            // handle some special extra data from previous BD versions
            // TODO: this is not needed anymore since the 3.2 upgrade routine
            if ( isset( $attrs['field_data'] ) && isset( $attrs['field_data']['options'] )  ) {
                $options = array();

                foreach ( $attrs['field_data']['options'] as $option_value ) {
                    if ( is_array( $option_value ) )
                        $options[ $option_value[0] ] = $option_value[1];
                    else
                        $options[ $option_value ] = $option_value;
                }

                $this->field_data['options'] = $options;
            }
        }

        $this->type->setup_field( $this );
        do_action_ref_array( 'wpbdp_form_field_setup', array( &$this ) );
    }

    public function get_id() {
        return $this->id;
    }

    public function &get_field_type() {
        return $this->type;
    }

    public function get_field_type_id() {
        return $this->type->get_id();
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

    /**
     * @since 4.0
     */
    public function get_shortname() {
        static $protected_shortnames = array( 'images', 'image', 'username', 'featured_level', 'expires_on', 'sequence_id' );

        if ( $this->shortname )
            return $this->shortname;

                // $name = $name . '-' . $field->get_id();
        if ( ! $this->label ) {
            $this->shortname = 'field_' . $this->id;
        } else {
            $shortname = WPBDP_Form_Field_Type::normalize_name( $this->label );

            if ( in_array( $shortname, $protected_shortnames, true ) )
                $shortname .= '__' . $this->id;
        }

        $this->shortname = $shortname;

        if ( $this->id ) {
            global $wpdb;
            $wpdb->update( $wpdb->prefix . 'wpbdp_form_fields', array( 'shortname' => $shortname ), array( 'id' => $this->id ) );
        }

        return $shortname;
    }

    /**
     * @since 4.0.4
     */
    public function shortname_noconflict( $shortname ) {
        global $wpdb;

        $in_use = false;

        if ( ! $this->id )
            $in_use = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_form_fields WHERE shortname = %s LIMIT 1", $shortname ) );
        else
            $in_use = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_form_fields WHERE shortname = %s AND id != %d LIMIT 1", $shortname, $this->id ) );

        if ( ! $in_use )
            return $shortname;

        $n = 1;

        // Find an alternative name.
        while ( true ) {
            $check = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 AS x FROM {$wpdb->prefix}wpbdp_form_fields WHERE shortname = %s LIMIT 1", $shortname . '_' . $n ) );

            if ( ! $check ) {
                $shortname = $shortname . '_' . $n;
                break;
            }

            $n++;
        }

        return $shortname;
    }

    public function get_short_name() {
        return $this->get_shortname();
    }

    /**
     * @since 3.6.10
     */
    public function get_tag() {
        return $this->tag;
    }

    public function &get_validators() {
        return $this->validators;
    }

    public function get_weight() {
        return $this->weight;
    }

    public function has_validator( $validator ) {
        return in_array( $validator, $this->validators, true );
    }

    public function add_validator( $validator ) {
        if ( !$this->has_validator( $validator ) )
            $this->validators[] = $validator;
    }

    public function is_required() {
        return in_array( 'required', $this->validators, true );
    }

    public function display_in( $context ) {
        return in_array( $context, $this->display_flags, true);
    }

    public function add_display_flag( $flagorflags ) {
        $flagorflags = is_array( $flagorflags ) ? $flagorflags : array( $flagorflags );

        foreach ( $flagorflags as $flag ) {
            if ( !$this->has_display_flag( $flag ) ) {
                $this->display_flags[] = $flag;    
            }
        }
    }

    public function remove_display_flag( $flagorflags ) {
        $flagorflags = is_array( $flagorflags ) ? $flagorflags : array( $flagorflags );
        
        foreach ( $flagorflags as $flag )
            wpbdp_array_remove_value( $this->display_flags, $flag );
    }

    public function has_display_flag( $flag ) {
        return in_array( $flag, $this->display_flags, true );
    }

    public function set_display_flags( $flags ) {
        $this->display_flags = is_array( $flags ) ? $flags : array();
    }

    public function get_display_flags() {
        return $this->display_flags;
    }

    /**
     * @since 3.5.3
     */
    public function get_css_classes( $render_context = '' ) {
        $css_classes = array();
        $css_classes[] = 'wpbdp-form-field';
        $css_classes[] = 'wpbdp-form-field-id-' . $this->get_id();
        $css_classes[] = 'wpbdp-form-field-type-' . $this->get_field_type()->get_id();
        $css_classes[] = 'wpbdp-form-field-label-' . WPBDP_Form_Field_Type::normalize_name( $this->get_label() );
        $css_classes[] = 'wpbdp-form-field-association-' . $this->get_association();

        // For backwards compatibility.
        $css_classes[] = $this->get_field_type()->get_id();

        if ( $this->get_description() )
            $css_classes[] = 'with-description';

        if ( $this->get_validators() )
            $css_classes = array_merge( $css_classes, $this->get_validators() );

        // Add own custom CSS classes.
        $css_classes = array_merge( $css_classes, $this->css_classes );

        return apply_filters( 'wpbdp_form_field_css_classes', $css_classes, $this, $render_context );
    }

    /**
     * TODO: dodoc.
     * Valid behavior (override default behavior) flags: display-only, no-delete, no-validation
     * @since 3.4
     */
    public function get_behavior_flags() {
        return apply_filters( 'WPBDP_Form_Field::get_behavior_flags', $this->type->get_behavior_flags( $this ), $this );
    }

    /**
     * TODO: dodoc.
     * @since 3.4
     */
    public function has_behavior_flag( $flag ) {
        return in_array( $flag, $this->get_behavior_flags(), true );
    }

    /**
     * Returns field-type specific configuration options for this field.
     * @param string $key configuration key name
     * @return mixed|array if $key is ommitted an array of all key/values will be returned
     */
    public function data( $key=null ) {
        if ( !$key )
            return $this->field_data;

        $res = isset( $this->field_data[$key] ) ? $this->field_data[$key] : null;
        return apply_filters( 'wpbdp_form_field_data', $res, $key, $this );
    }

    /**
     * Saves field-type specific configuration options for this field.
     * @param string $key configuration key name.
     * @param mixed $value data value.
     * @return mixed data value.
     */
    public function set_data( $key, $value=null ) {
        $this->field_data[ $key ] = $value;
    }

    /**
     * Removes any field-type specific configuration option from this field. Use with caution.
     */
    public function clear_data() {
        $this->field_data = array();
    }

    /**
     * Returns this field's raw value for the given post.
     * @param int|object $post_id post ID or object.
     * @return mixed
     */
    public function value( $post_id, $raw = false ) {
        if ( !get_post_type( $post_id ) == WPBDP_POST_TYPE )
            return null;        

        $value = $this->type->get_field_value( $this, $post_id );

        if ( ! $raw )
            $value = apply_filters( 'wpbdp_form_field_value', $value, $post_id, $this );

        return $value;
    }

    /**
     * Returns this field's HTML value for the given post. Useful for display.
     * @param int|object $post_id post ID or object.
     * @return string valid HTML.
     */
    public function html_value( $post_id ) {
        $value = $this->type->get_field_html_value( $this, $post_id );

        if ( $value && in_array( 'email', $this->validators, true ) && wpbdp_get_option('override-email-blocking') ) {
            // At least obfuscate the address if we're going to show it.
            $out = '';

            for ( $i = 0; $i < strlen( $value ); $i++ ) {
                if ( '.' == $value[ $i ] || '@' == $value[ $i ] )
                    $out .= $value [ $i ];
                else
                    $out .= '&#' . ord( $value[ $i ] ) . ';';
            }

            $value = sprintf( '<a href="mailto:%s">%s</a>', $out, $out );
        }

        return apply_filters( 'wpbdp_form_field_html_value', $value , $post_id, $this );
    }

    /**
     * Returns this field's value as plain text. Useful for emails or cooperation between modules.
     * @param int|object $post_id post ID or object.
     * @return string
     */
    public function plain_value( $post_id ) {
        $value = $this->type->get_field_plain_value( $this, $post_id );
        return apply_filters( 'wpbdp_form_field_plain_value', $value, $post_id, $this );
    }

    /**
     * @since 3.4.1
     */
    public function csv_value( $post_id ) {
        $value = $this->type->get_field_csv_value( $this, $post_id );
        return apply_filters( 'wpbdp_form_field_csv_value', $value, $post_id, $this );
    }

    /**
     * Converts input from forms to a value useful for this field.
     * @param mixed $input form input.
     * @return mixed
     */
    public function convert_input( $input=null ) {
        return $this->type->convert_input( $this, $input );
    }

    /**
     * @since 3.4.1
     */
    public function convert_csv_input( $input = '', $import_settings = array() ) {
        return $this->type->convert_csv_input( $this, $input, $import_settings );
    }

    public function store_value( $post_id, $value ) {
        $this->type->store_field_value( $this, $post_id, $value );
        do_action_ref_array( 'wpbdp_form_field_store_value', array( &$this, $post_id, $value ) );
    }

    public function is_empty_value( $value ) {
        return $this->type->is_empty_value( $value );
    }

    public function validate( $value, &$errors=null ) {
        $errors = !is_array( $errors ) ? array() : $errors;

        $validation_api = WPBDP_FieldValidation::instance();

        if ( !$this->is_required() && $this->type->is_empty_value( $value ) )
            return true;

        foreach ( $this->validators as $validator ) {
            $args = $this->type->setup_validation( $this, $validator, $value );
            $args = is_array( $args ) ? $args : array();

            $res = $validation_api->validate_field( $this, $value, $validator, $args );

            if ( is_wp_error( $res ) ) {
                $errors[] = $res->get_error_message();
            }
        }

        if ( !$errors )
            return true;

        return false;
    }

    /**
     * Returns HTML apt for display of this field's value.
     * @param int|object $post_id post ID or object
     * @param string $display_context the display context. defaults to 'listing'.
     * @return string
     */
    public function display( $post_id, $display_context='listing' ) {
        if ( in_array( 'email', $this->validators, true ) ) {
            if ( ! wpbdp_get_option('override-email-blocking') )
                return '';
        }

        if ( $this->type->is_empty_value( $this->value( $post_id ) ) )
            return '';

        $html = $this->type->display_field( $this, $post_id, $display_context );
        $html = apply_filters_ref_array( 'wpbdp_form_field_display', array( $html, &$this, $display_context, $post_id ) );
        return $html;
    }

    /**
     * Returns HTML apt for displaying this field in forms.
     * @param mixed $value the value to be displayed. defaults to null.
     * @param string $display_context the rendering context. defaults to 'submit'.
     * @return string
     */
    public function render( $value = null, $display_context = 'submit', &$extra = null, $field_settings = array() ) {
        do_action_ref_array( 'wpbdp_form_field_pre_render', array( &$this, $value, $display_context ) );

        if ( $this->has_behavior_flag( 'display-only' ) )
            return '';

        if ( 'submit' == $display_context && $this->has_behavior_flag( 'no-submit' ) )
            return '';

        return $this->type->render_field( $this, $value, $display_context, $extra, $field_settings );
    }

    /**
     * Tries to save this field to the database. If successfully, sets the new id too.
     * @return mixed True if successfully created, WP_Error in the other case
     */
    public function save() {
        global $wpdb;

        $api = wpbdp_formfields_api();

        if ( !$this->label || trim( $this->label ) == '' )
            return new WP_Error( 'wpbdp-save-error', _x('Field label is required.', 'form-fields-api', 'WPBDM') );

        // If performing a field conversion, make sure the types are compatible.
        if ( $this->id ) {
            $orig_type = $wpdb->get_var( $wpdb->prepare( "SELECT field_type FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $this->id ) );
            $new_type = $this->type->get_id();

            if ( $orig_type != $new_type ) {
                if ( 'url' == $new_type || 'image' == $new_type || 'url' == $orig_type || 'image' == $orig_type ) {
                    $this->type = WPBDP_FormFields::instance()->get_field_type( $orig_type );
                    return new WP_Error( 'wpbdp-field-error', _x( 'Requested field type change is incompatible. Type will not be modified.', 'form-fields-api', 'WPBDM' ) );
                }
            }
        }

        if ( isset( $_POST['field'] ) ) {
            $res = $this->type->process_field_settings( $this );
            do_action_ref_array( 'wpbdp_form_field_settings_process', array( &$this ) );

            if ( is_wp_error( $res ) )
                return $res;
        }

        // enforce association constraints
        global $wpbdp;
        $flags = $wpbdp->formfields->get_association_flags( $this->association );
        
        if ( in_array( 'unique', $flags ) ) {
            if ( $otherfields = wpbdp_get_form_fields( 'association=' . $this->association ) ) {
                if ( ( count( $otherfields ) > 1 ) || ( $otherfields[0]->get_id() != $this->id ) ) {
                    return new WP_Error( 'wpbdp-field-error', sprintf( _x( 'There can only be one field with association "%s". Please select another association.', 'form-fields-api', 'WPBDM' ), $this->association ) );
                }
            }
        }

        if ( in_array( 'required', $flags ) && ! in_array( 'optional', $flags ) ) {
            $this->add_validator( 'required' );
        }

      if ( !in_array( $this->type->get_id(), (array) $wpbdp->formfields->get_association_field_types( $this->association ) ) ) {
            return new WP_Error( 'wpbdp-field-error', sprintf( _x( '"%s" is an invalid field type for this association.', 'form-fields-api', 'WPBDM' ), $this->type->get_name() ) );
        }

        $data = array();

        $data['label'] = $this->label;
        $data['shortname'] = $this->shortname_noconflict( $this->get_shortname() );
        $data['description'] = trim( $this->description );
        $data['field_type'] = $this->type->get_id();
        $data['association'] = $this->association;
        $data['validators'] = implode( ',', $this->validators );
        $data['weight'] = $this->weight;
        $data['display_flags'] = implode( ',', $this->display_flags );
        $data['field_data'] = serialize( $this->field_data );

        if ( in_array( $this->association, array( 'title', 'excerpt', 'content', 'category', 'tags' ), true ) )
            $data['tag'] = $this->association;
        else if ( 'ratings' == $this->type->get_id() )
            $data['tag'] = 'ratings';
        else
            $data['tag'] = $this->tag;

        if ( $this->id ) {
            $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", $data, array( 'id' => $this->id ) );
        } else {
            $wpdb->insert( "{$wpdb->prefix}wpbdp_form_fields", $data );
            $this->id = intval( $wpdb->insert_id );
        }

        wp_cache_delete( $this->id, 'wpbdp formfields' );
    }

    /**
     * Tries to delete this field from the database.
     * @return mixed True if successfully deleted, WP_Error in the other case
     */
    public function delete() {
        if ( !$this->id )
            return new WP_Error( 'wpbdp-delete-error', _x( 'Invalid field ID', 'form-fields-api', 'WPBDM' ) );

        global $wpbdp;
        $flags = $wpbdp->formfields->get_association_flags( $this->association );

        if ( in_array( 'required', $flags ) ) {
            $otherfields = wpbdp_get_form_fields( array( 'association' => $this->association ) );

            if ( !$otherfields || ( $otherfields[0]->get_id() == $this->id ) )
               return new WP_Error( 'wpbdp-delete-error', _x( "This form field can't be deleted because it is required for the plugin to work.", 'form-fields api', 'WPBDM' ) ); 
        }

        global $wpdb;

        if ( $wpdb->query( $wpdb->prepare( "DELETE FROM  {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $this->id ) ) !== false ) {
            $this->type->cleanup( $this );

            wp_cache_delete( $this->id, 'wpbdp formfields' );

            $this->id = 0;
        } else {
            return new WP_Error( 'wpbdp-delete-error', _x( 'An error occurred while trying to delete this field.', 'form-fields-api', 'WPBDM' ) );
        }

        return true;
    }

    /**
     * Reorders this field within the list of fields.
     * @param int $delta if positive, field is moved up. else is moved down.
     */
    public function reorder( $delta=0 ) {
        global $wpdb;

        $delta = intval( $delta );

        if ( !$delta )
            return;

        if ( $delta > 0 ) {
            $fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight >= %d ORDER BY weight ASC", $this->weight ) );

            if ( $fields[count($fields) - 1]->id == $this->id )
                return;

            for ( $i = 0; $i < count( $fields ); $i++ ) {
                $fields[ $i ]->weight = intval( $this->weight ) + $i;

                if ($fields[ $i ]->id == $this->id ) {
                    $fields[ $i ]->weight += 1;
                    $fields[ $i+1 ]->weight -= 1;
                    $i += 1;
                } 
            }

            foreach ( $fields as &$f ) {
                $wpdb->update( "{$wpdb->prefix}wpbdp_form_fields", array( 'weight' => $f->weight ), array( 'id' => $f->id ) );
            }
        } else {
            $fields = $wpdb->get_results( $wpdb->prepare( "SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight <= %d ORDER BY weight ASC", $this->weight ) );

            if ( $fields[0]->id == $this->id )
                return;

            foreach ( $fields as $i => $f ) {
                if ( $f->id == $this->id ) {
                    self::get( $fields[ $i-1 ]->id )->reorder( 1 );
                    return;
                }
            }

        }
    }

    public function build_quick_search_query( $q = '', &$pieces, $search_term = '', $w_no = 0, &$optimization ) {
        global $wpdb;

        $association = $this->get_association();
        $id = $this->get_id();

        // Do not allow quick search to be performed on e-mail fields for security.
        if ( $this->has_validator( 'email' ) )
            return;

        if ( $this->has_behavior_flag( 'quick-search-external' ) ) {
            do_action_ref_array( 'WPBDP_Form_Field::build_quick_search_query', array( $this, $q, &$pieces, $search_term, $w_no, &$optimization ) );
            return;
        }

        switch ( $association ) {
            case 'title':
            case 'excerpt':
            case 'content':
                $pieces['fields'] .= "";
                $pieces['where'] .= $wpdb->prepare( " OR ({$wpdb->posts}.post_{$association} LIKE '%%%s%%')", $q );
                break;
            case 'category':
            case 'tags':
            case 'region':
                $tax = WPBDP_CATEGORY_TAX;
                if ( 'tags' == $association )
                    $tax = WPBDP_TAGS_TAX;
                elseif ( 'region' == $association && function_exists( 'wpbdp_regions_taxonomy' ) )
                    $tax = wpbdp_regions_taxonomy();

                $pieces['fields'] .= "";
                $pieces['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS trel{$id}_{$w_no} ON {$wpdb->posts}.ID = trel{$id}_{$w_no}.object_id LEFT JOIN {$wpdb->term_taxonomy} AS ttax{$id}_{$w_no} ON trel{$id}_{$w_no}.term_taxonomy_id = ttax{$id}_{$w_no}.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS tterms{$id}_{$w_no} ON ttax{$id}_{$w_no}.term_id = tterms{$id}_{$w_no}.term_id";
                $pieces['where'] .= $wpdb->prepare( " OR (ttax{$id}_{$w_no}.taxonomy = %s AND (tterms{$id}_{$w_no}.name LIKE '%%%s%%'))",
                                                    $tax, $q, $q );
                break;
            case 'meta':
                if ( ! isset( $optimization['words'][ $w_no ]['postmeta'] ) ) {
                    $optimization['words'][ $w_no ]['postmeta'] = 'pm' . $w_no;

                    $pieces['join'] .= " LEFT JOIN {$wpdb->postmeta} AS pm{$w_no} ON {$wpdb->posts}.ID = pm{$w_no}.post_id";
                }

                $join_table = $optimization['words'][ $w_no ]['postmeta'];

                $pieces['where'] .= $wpdb->prepare( " OR ({$join_table}.meta_key = %s AND {$join_table}.meta_value LIKE '%%%s%%') ",
                                                   '_wpbdp[fields][' . $id . ']',
                                                   $q );
                break;
        }

        do_action_ref_array( 'WPBDP_Form_Field::build_quick_search_query', array( $this, $q, &$pieces, $search_term, $w_no, &$optimization ) );
    }

    /**
     * Creates a WPBDP_FormField from a database record.
     * @param int $id the database record ID.
     * @return WPBDP_FormField a valid WPBDP_FormField if the record exists or null if not.
     */
    public static function get( $id ) {
        global $wpdb;

        $id = absint( $id );

        if ( ! $id )
            return null;

        $_field = wp_cache_get( $id, 'wpbdp formfields' );

        if ( ! $_field ) {
            $_field = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $id ) );

            if ( ! $_field )
                return null;

            $_field = (array) $_field;

            $_field['display_flags'] = explode( ',', $_field['display_flags'] ); 
            $_field['validators'] = explode( ',', $_field['validators'] );
            $_field['field_data'] = unserialize( $_field['field_data'] );

            wp_cache_set( $id, $_field, 'wpbdp formfields' );
        }

        try {
            return new WPBDP_FormField( $_field );
        } catch (Exception $e ) {
            return null;
        }
    }

    public static function find_by_tag( $tag ) {
        global $wpdb;

        $field_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpbdp_form_fields WHERE tag = %s LIMIT 1", $tag ) ) );

        if ( ! $field_id )
            return null;

        return self::get( $field_id );
    }

}

/**
 * @deprecated Since 3.4.2. Use {@link WPBDP_Form_Field} instead.
 */
class WPBDP_FormField extends WPBDP_Form_Field {}
