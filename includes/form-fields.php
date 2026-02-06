<?php
/**
 * Form fields API.
 *
 * @package BDP/Form Fields API
 */
if ( ! class_exists( 'WPBDP_FormFields' ) ) {

	require_once WPBDP_PATH . 'includes/fields/class-form-field.php';
	require_once WPBDP_PATH . 'includes/fields/form-fields-types.php';

	class WPBDP_FormFields {

		private $associations            = array();
		private $association_flags       = array();
		private $association_field_types = array();

		private $field_types = array();

		private static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			// register core associations
			$this->register_association( 'title', __( 'Post Title', 'business-directory-plugin' ), array( 'required', 'unique' ) );
			$this->register_association( 'content', __( 'Post Content', 'business-directory-plugin' ), array( 'required', 'unique', 'optional' ) );
			$this->register_association( 'excerpt', __( 'Post Excerpt', 'business-directory-plugin' ), array( 'unique' ) );
			$this->register_association( 'category', __( 'Post Category', 'business-directory-plugin' ), array( 'required', 'unique' ) );
			$this->register_association( 'tags', __( 'Post Tags', 'business-directory-plugin' ), array( 'unique' ) );
			$this->register_association( 'meta', __( 'Post Metadata', 'business-directory-plugin' ) );

			$this->register_association( 'custom', __( 'Custom', 'business-directory-plugin' ), array( 'private' ) );

			// register core field types
			$this->register_field_type( 'WPBDP_FieldTypes_TextField', 'textfield' );
			$this->register_field_type( 'WPBDP_FieldTypes_Select', 'select' );
			$this->register_field_type( 'WPBDP_FieldTypes_URL', 'url' );
			$this->register_field_type( 'WPBDP_FieldTypes_TextArea', 'textarea' );
			$this->register_field_type( 'WPBDP_FieldTypes_RadioButton', 'radio' );
			$this->register_field_type( 'WPBDP_FieldTypes_MultiSelect', 'multiselect' );
			$this->register_field_type( 'WPBDP_FieldTypes_Checkbox', 'checkbox' );
			$this->register_field_type( 'WPBDP_FieldTypes_Twitter', 'social-twitter' );
			$this->register_field_type( 'WPBDP_FieldTypes_Facebook', 'social-facebook' );
			$this->register_field_type( 'WPBDP_FieldTypes_LinkedIn', 'social-linkedin' );
			$this->register_field_type( 'WPBDP_FieldTypes_Social', 'social-network' );
			$this->register_field_type( 'WPBDP_FieldTypes_Image', 'image' );
			$this->register_field_type( 'WPBDP_FieldTypes_Date', 'date' );
			$this->register_field_type( 'WPBDP_FieldTypes_Phone_Number' );
		}

		/**
		 * Registers a new association within the form fields API.
		 *
		 * @param string $association association id
		 * @param string $name human-readable name
		 * @param array  $flags association flags
		 */
		public function register_association( $association, $name = '', $flags = array() ) {
			if ( isset( $this->associations[ $association ] ) ) {
				return false;
			}

			$this->associations[ $association ]      = $name ? $name : $association;
			$this->association_flags[ $association ] = $flags;

			if ( ! isset( $this->association_field_types[ $association ] ) ) {
				$this->association_field_types[ $association ] = array();
			}
		}

		/**
		 * Returns the known form field associations.
		 *
		 * @return array associative array with key/name pairs
		 */
		public function &get_associations() {
			return $this->associations;
		}

		public function get_association_field_types( $association = null ) {
			if ( $association ) {
				if ( in_array( $association, array_keys( $this->associations ), true ) ) {
					return $this->association_field_types[ $association ];
				} else {
					return null;
				}
			}

			return $this->association_field_types;
		}

		public function get_association_flags( $association ) {
			if ( array_key_exists( $association, $this->associations ) ) {
				return $this->association_flags[ $association ];
			}

			return array();
		}

		/**
		 * Returns associations marked with the given flags.
		 *
		 * @param array|string $flags flags to be checked
		 * @param bool         $any if True associations marked with any (and not all) of the flags will also be returned
		 *
		 * @return array
		 */
		public function &get_associations_with_flag( $flags, $any = false ) {
			if ( is_string( $flags ) ) {
				$flags = array( $flags );
			}

			$res = array();

			foreach ( $this->association_flags as $association => $association_flags ) {
				$intersection = array_intersect( $flags, $association_flags );

				if ( ( $any && ( count( $intersection ) > 0 ) ) || ( ! $any && ( count( $intersection ) == count( $flags ) ) ) ) {
					$res[] = $association;
				}
			}

			return $res;
		}

		/**
		 * Get associations with their flags at the same time.
		 *
		 * @since 3.4
		 */
		public function &get_associations_with_flags() {
			$res = array();

			foreach ( $this->associations as $assoc_id => $assoc_label ) {
				$flags            = $this->association_flags[ $assoc_id ];
				$res[ $assoc_id ] = (object) array(
					'id'    => $assoc_id,
					'label' => $assoc_label,
					'flags' => $flags,
				);
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

		public function register_field_type( $field_type_class, $alias = null ) {
			$field_type = new $field_type_class();

			if ( ! $alias ) {
				$alias = $field_type->get_id();
			}

			if ( ! $alias ) {
				$alias = $field_type_class;
			}

			$this->field_types[ $alias ? $alias : $field_type_class ] = $field_type;

			foreach ( $field_type->get_supported_associations() as $association ) {
				$this->association_field_types[ $association ] = array_merge( isset( $this->association_field_types[ $association ] ) ? $this->association_field_types[ $association ] : array(), array( $alias ? $alias : $field_type_class ) );
			}
		}

		public function &get_field( $id = 0 ) {
			$field = WPBDP_Form_Field::get( $id );
			return $field;
		}

		public function &get_fields( $lightweight = false ) {
			global $wpdb;

			if ( $lightweight ) {
				$sql     = "SELECT * FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC";
				$results = WPBDP_Utils::check_cache(
					array(
						'cache_key' => 'get_fields_light',
						'group'     => 'wpbdp_form_fields',
						'query'     => $sql,
						'type'      => 'get_results',
					)
				);
				return $results;
			}

			$res       = array();
			$sql       = "SELECT ID FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC";
			$field_ids = WPBDP_Utils::check_cache(
				array(
					'cache_key' => 'get_field_ids',
					'group'     => 'wpbdp_form_fields',
					'query'     => $sql,
					'type'      => 'get_col',
				)
			);

			foreach ( $field_ids as $field_id ) {
				$field = WPBDP_Form_Field::get( $field_id );
				if ( $field ) {
					$res[] = $field;
				}
			}

			return $res;
		}

		public function &find_fields( $args = array(), $one = false ) { // phpcs:ignore SlevomatCodingStandard.Complexity
			global $wpdb;
			$res = array();

			$args = wp_parse_args(
				$args,
				array(
					'association'   => null,
					'field_type'    => null,
					'validators'    => null,
					'display_flags' => null,
					'output'        => 'object',
					'unique'        => false,
				)
			);

			if ( $one == true ) {
				$args['unique'] = true;
			}

			extract( $args );

			$validators    = $validators ? ( ! is_array( $validators ) ? array( $validators ) : $validators ) : array();
			$display_flags = $display_flags ? ( ! is_array( $display_flags ) ? array( $display_flags ) : $display_flags ) : array();

			$where = '';
			if ( $args['association'] ) {
				$associations_in     = array();
				$associations_not_in = array();

				$association = ! is_array( $association ) ? explode( ',', $association ) : $association;

				foreach ( $association as &$assoc ) {
					if ( wpbdp_starts_with( $assoc, '-' ) ) {
						$associations_not_in[] = substr( $assoc, 1 );
					} else {
						$associations_in[] = $assoc;
					}
				}

				if ( $associations_in ) {
					$format = implode( ', ', array_fill( 0, count( $associations_in ), '%s' ) );
					$where .= $wpdb->prepare( " AND ( association IN ( $format ) ) ", $associations_in );
				}

				if ( $associations_not_in ) {
					$format = implode( ', ', array_fill( 0, count( $associations_not_in ), '%s' ) );
					$where .= $wpdb->prepare( " AND ( association NOT IN ( $format ) ) ", $associations_not_in );
				}
			}

			if ( $args['field_type'] ) {
				$field_types_in     = array();
				$field_types_not_in = array();

				$field_type = ! is_array( $field_type ) ? array( $field_type ) : $field_type;

				foreach ( $field_type as $f ) {
					if ( wpbdp_starts_with( $f, '-' ) ) {
						$field_types_not_in[] = substr( $f, 1 );
					} else {
						$field_types_in[] = $f;
					}
				}

				if ( $field_types_in ) {
					$format = implode( ', ', array_fill( 0, count( $field_types_in ), '%s' ) );
					$where .= $wpdb->prepare( " AND ( field_type IN ( $format ) ) ", $field_types_in );
				}

				if ( $field_types_not_in ) {
					$format = implode( ', ', array_fill( 0, count( $field_types_not_in ), '%s' ) );
					$where .= $wpdb->prepare( " AND ( field_type NOT IN ( $format ) ) ", $field_types_not_in );
				}
			}

			foreach ( $display_flags as $f ) {
				if ( substr( $f, 0, 1 ) == '-' ) {
					$where .= $wpdb->prepare( ' AND ( display_flags IS NULL OR display_flags NOT LIKE %s )', '%%' . $wpdb->esc_like( substr( $f, 1 ) ) . '%%' );
				} else {
					$where .= $wpdb->prepare( ' AND ( display_flags LIKE %s )', '%%' . $wpdb->esc_like( $f ) . '%%' );
				}
			}

			foreach ( $validators as $v ) {
				if ( substr( $v, 0, 1 ) == '-' ) {
					$where .= $wpdb->prepare( ' AND ( validators IS NULL OR validators NOT LIKE %s )', '%%' . $wpdb->esc_like( substr( $v, 1 ) ) . '%%' );
				} else {
					$where .= $wpdb->prepare( ' AND ( validators LIKE %s )', '%%' . $wpdb->esc_like( $v ) . '%%' );
				}
			}

			if ( $where ) {
				$sql = "SELECT id FROM {$wpdb->prefix}wpbdp_form_fields WHERE 1=1 {$where} ORDER BY weight DESC";
			} else {
				$sql = "SELECT id FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC";
			}

			$ids = WPBDP_Utils::check_cache(
				array(
					'cache_key' => json_encode( array_filter( $args ) ) . '.' . $one,
					'group'     => 'wpbdp_form_fields',
					'query'     => $sql,
					'type'      => 'get_col',
				)
			);

			if ( 'ids' == $output ) {
				return $ids;
			}

			foreach ( $ids as $id ) {
				$field = WPBDP_Form_Field::get( $id );
				if ( $field && in_array( $field->get_association(), array_keys( $this->associations ), true ) ) {
					$res[] = $field;
				}
			}

			$res = $unique ? ( $res ? $res[0] : null ) : $res;
			return $res;
		}

		public function get_missing_required_fields() {
			global $wpdb;

			$missing = $this->get_required_field_associations();

			$sql_in = '(\'' . implode( '\',\'', $missing ) . '\')';
			$res    = $wpdb->get_col( "SELECT association FROM {$wpdb->prefix}wpbdp_form_fields WHERE association IN {$sql_in} GROUP BY association" );

			return array_diff( $missing, $res );
		}

		/**
		 * @since 3.6.9
		 */
		public function get_default_fields( $id = '' ) {
			$default_fields = array(
				'title'    => array(
					'label'         => __( 'Listing Title', 'business-directory-plugin' ),
					'field_type'    => 'textfield',
					'association'   => 'title',
					'weight'        => 9,
					'validators'    => array( 'required' ),
					'display_flags' => array( 'excerpt', 'listing', 'search', 'privacy' ),
					'tag'           => 'title',
				),
				'category' => array(
					'label'         => __( 'Listing Category', 'business-directory-plugin' ),
					'field_type'    => 'select',
					'association'   => 'category',
					'weight'        => 8,
					'validators'    => array( 'required' ),
					'display_flags' => array( 'excerpt', 'listing', 'search' ),
					'tag'           => 'category',
				),
				'excerpt'  => array(
					'label'         => __( 'Short Description', 'business-directory-plugin' ),
					'field_type'    => 'textarea',
					'association'   => 'excerpt',
					'weight'        => 7,
					'display_flags' => array( 'excerpt', 'listing', 'search' ),
					'tag'           => 'excerpt',
				),
				'content'  => array(
					'label'         => __( 'Description', 'business-directory-plugin' ),
					'field_type'    => 'textarea',
					'association'   => 'content',
					'weight'        => 6,
					'validators'    => array( 'required' ),
					'display_flags' => array( 'listing', 'search' ),
					'tag'           => 'content',
				),
				'website'  => array(
					'label'         => __( 'Website', 'business-directory-plugin' ),
					'field_type'    => 'url',
					'association'   => 'meta',
					'weight'        => 5,
					'validators'    => array( 'url' ),
					'display_flags' => array( 'excerpt', 'listing', 'search', 'privacy' ),
					'tag'           => 'website',
				),
				'phone'    => array(
					'label'         => __( 'Phone', 'business-directory-plugin' ),
					'field_type'    => 'phone_number',
					'association'   => 'meta',
					'weight'        => 4,
					'display_flags' => array( 'excerpt', 'listing', 'search', 'privacy' ),
					'tag'           => 'phone',
				),
				'email'    => array(
					'label'         => __( 'Email', 'business-directory-plugin' ),
					'field_type'    => 'textfield',
					'association'   => 'meta',
					'weight'        => 2,
					'validators'    => array( 'email', 'required' ),
					'display_flags' => array( 'excerpt', 'listing', 'privacy' ),
					'tag'           => 'email',
				),
				'tags'     => array(
					'label'         => __( 'Listing Tags', 'business-directory-plugin' ),
					'field_type'    => 'textfield',
					'association'   => 'tags',
					'weight'        => 1,
					'display_flags' => array( 'excerpt', 'listing', 'search' ),
					'tag'           => 'tags',
				),
				'address'  => array(
					'label'         => __( 'Address', 'business-directory-plugin' ),
					'field_type'    => 'textarea',
					'association'   => 'meta',
					'weight'        => 1,
					'display_flags' => array( 'excerpt', 'listing', 'search', 'privacy' ),
					'tag'           => 'address',
				),
				'zip'      => array(
					'label'         => __( 'ZIP Code', 'business-directory-plugin' ),
					'field_type'    => 'textfield',
					'association'   => 'meta',
					'weight'        => 1,
					'display_flags' => array( 'excerpt', 'listing', 'search', 'privacy' ),
					'tag'           => 'zip',
				),
			);

			if ( $id ) {
				if ( isset( $default_fields[ $id ] ) ) {
					return $default_fields[ $id ];
				} else {
					return null;
				}
			}

			return $default_fields;
		}

		public function create_default_fields( $identifiers = array() ) {
			if ( empty( $this->get_missing_required_fields() ) ) {
				return;
			}

			$default_fields   = $this->get_default_fields();
			$fields_to_create = $identifiers ? array_intersect_key( $default_fields, array_flip( $identifiers ) ) : $default_fields;

			foreach ( $fields_to_create as &$f ) {
				$field = new WPBDP_Form_Field( $f );
				$field->save();
			}
		}

		/**
		 * @deprecated since 4.0.
		 */
		public function get_short_names( $fieldid = null ) {
			//_deprecated_function( __FUNCTION__, '4.0' );

			$fields     = $this->get_fields();
			$shortnames = array();

			foreach ( $fields as $f ) {
				$shortnames[ $f->get_id() ] = $f->get_shortname();
			}

			if ( $fieldid ) {
				return isset( $shortnames[ $fieldid ] ) ? $shortnames[ $fieldid ] : null;
			}

			return $shortnames;
		}

		public function _calculate_short_names() {
			$fields = $this->get_fields();
			$names  = array();

			foreach ( $fields as $field ) {
				$name = WPBDP_Form_Field_Type::normalize_name( $field->get_label() );

				if ( $name == 'images' || $name == 'image' || $name == 'username' || $name == 'featured_level' || $name == 'expires_on' || $name == 'sequence_id' || in_array( $name, $names, true ) ) {
					$name = $name . '-' . $field->get_id();
				}

				$names[ $field->get_id() ] = $name;
			}

			update_option( 'wpbdp-field-short-names', $names, 'no' );

			return $names;
		}

		public function set_fields_order( $fields_order = array() ) {
			if ( ! $fields_order ) {
				return false;
			}

			global $wpdb;

			$total = count( $fields_order );

			foreach ( $fields_order as $i => $field_id ) {
				$wpdb->update(
					$wpdb->prefix . 'wpbdp_form_fields',
					array( 'weight' => $total - $i ),
					array( 'id' => $field_id )
				);
			}
			WPBDP_Utils::cache_delete_group( 'wpbdp_form_fields' );

			return true;
		}

		/**
		 * @since 4.0
		 */
		public function maybe_correct_tags() {
			$fields = wpbdp_get_form_fields();

			foreach ( $fields as $f ) {
				if ( $f->get_tag() ) {
					continue;
				}

				$f->save();
			}
		}
	}
}
/**
 * @since 2.3
 * @see WPBDP_FormFields::find_fields()
 */
function &wpbdp_get_form_fields( $args = array() ) {
	global $wpdb;
	global $wpbdp;

	$fields = array();

	if ( $wpbdp->get_db_version() ) {
		$fields = $wpbdp->form_fields->find_fields( $args );
	}

	if ( ! $fields ) {
		$fields = array();
	}

	return $fields;
}

/**
 * @since 2.3
 * @see WPBDP_FormFields::get_field()
 */
function wpbdp_get_form_field( $id ) {
	global $wpbdp;
	return $wpbdp->form_fields->get_field( $id );
}
