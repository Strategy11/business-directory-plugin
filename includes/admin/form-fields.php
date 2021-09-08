<?php
/**
 * Represents a single field from the database. This class can not be instantiated directly.
 *
 * @package WPBDP/Views/Includes/Admin/Form Fields
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class WPBDP_FormFieldsTable
 */
class WPBDP_FormFieldsTable extends WP_List_Table {

    public function __construct() {
        parent::__construct(
            array(
                'singular' => _x( 'form field', 'form-fields admin', 'business-directory-plugin' ),
                'plural'   => _x( 'form fields', 'form-fields admin', 'business-directory-plugin' ),
                'ajax'     => false,
            )
        );
    }

    public function get_columns() {
        return array(
            'order'     => _x( 'Order', 'form-fields admin', 'business-directory-plugin' ),
            'label'     => _x( 'Label / Association', 'form-fields admin', 'business-directory-plugin' ),
            'type'      => __( 'Type', 'business-directory-plugin' ),
            'validator' => _x( 'Validator', 'form-fields admin', 'business-directory-plugin' ),
            'tags'      => _x( 'Field Attributes', 'form-fields admin', 'business-directory-plugin' ),
        );
    }

    public function prepare_items() {
        $this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

        $formfields_api = WPBDP_FormFields::instance();
        $this->items    = $formfields_api->get_fields();
    }

    /* Rows */
    public function column_order( $field ) {
        $form_fields_url = admin_url( 'admin.php?page=wpbdp_admin_formfields' );
        return sprintf(
            '<span class="wpbdp-drag-handle" data-field-id="%s"></span> <a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
            $field->get_id(),
			wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'fieldup',
                        'id'     => $field->get_id(),
                    ),
                    $form_fields_url
				),
				'movefield'
            ),
			wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'fielddown',
                        'id'     => $field->get_id(),
                    ),
                    $form_fields_url
				),
				'movefield'
            )
        );
    }

    public function column_label( $field ) {
        $form_fields_url = admin_url( 'admin.php?page=wpbdp_admin_formfields' );
        $actions         = array();
        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
			wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'editfield',
                        'id'     => $field->get_id(),
                    ),
                    $form_fields_url
				),
				'editfield'
            ),
			esc_html__( 'Edit', 'business-directory-plugin' )
        );

        if ( ! $field->has_behavior_flag( 'no-delete' ) ) {
            $actions['delete'] = sprintf(
                '<a href="%s">%s</a>',
				wp_nonce_url(
                    add_query_arg(
                        array(
                            'action' => 'deletefield',
                            'id'     => $field->get_id(),
                        ),
                        $form_fields_url
					),
					'deletefield'
                ),
                esc_html__( 'Delete', 'business-directory-plugin' )
            );
        }

        $html  = '';
        $html .= sprintf(
            '<strong><a href="%s">%s</a></strong> (as <i>%s</i>)',
			wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'editfield',
                        'id'     => $field->get_id(),
                    ),
                    $form_fields_url
				),
				'editfield'
            ),
            esc_attr( $field->get_label() ),
            $field->get_association()
        );
        $html .= '<br/>';
        $html .= sprintf(
            '%s: %d',
            __( 'ID', 'business-directory-plugin' ),
            $field->get_id()
        );
        $html .= '<br/>';
        $html .= sprintf(
            '%s: %s',
            _x( 'Shortname', 'form-fields admin', 'business-directory-plugin' ),
            $field->get_shortname()
        );
        $html .= $this->row_actions( $actions );

        return $html;
    }

    public function column_type( $field ) {
        return esc_html( $field->get_field_type()->get_name() );
    }

    public function column_validator( $field ) {
        return esc_html( implode( ',', $field->get_validators() ) );
    }

    public function column_tags( $field ) {
        $html = '';

        $html .= sprintf(
            '<span class="tag %s">%s</span>',
            $field->is_required() ? 'required' : 'optional',
            $field->is_required() ? _x( 'Required', 'form-fields admin', 'business-directory-plugin' ) : _x( 'Optional', 'form-fields admin', 'business-directory-plugin' )
        );

        if ( $field->has_display_flag( 'private' ) ) {
            $html .= sprintf(
                '<span class="tag %s">%s</span>',
                'private',
                _x( 'Private', 'form-fields admin', 'business-directory-plugin' )
            );
        }

        if ( $field->display_in( 'excerpt' ) ) {
            $html .= sprintf(
                '<span class="tag in-excerpt" title="%s">%s</span>',
                _x( 'This field value is shown in the excerpt view of a listing.', 'form-fields admin', 'business-directory-plugin' ),
                _x( 'In Excerpt', 'form-fields admin', 'business-directory-plugin' )
            );
        }

        if ( $field->display_in( 'listing' ) ) {
            $html .= sprintf(
                '<span class="tag in-listing" title="%s">%s</span>',
                _x( 'This field value is shown in the single view of a listing.', 'form-fields admin', 'business-directory-plugin' ),
                _x( 'In Listing', 'form-fields admin', 'business-directory-plugin' )
            );
        }

        return $html;
    }

}

/**
 * Class WPBDP_FormFieldsAdmin
 */
class WPBDP_FormFieldsAdmin {

    public function __construct() {
        $this->api   = wpbdp_formfields_api();
        $this->admin = wpbdp()->admin;

        add_action( 'admin_init', array( $this, 'check_for_required_fields' ) );
    }

    /* Required fields check. */
    public function check_for_required_fields() {
        global $wpbdp;

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'wpbdp_admin_formfields' &&
             isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'createrequired' ) {
            // do not display the warning inside the page creating the required fields
            return;
        }

        if ( $missing = $wpbdp->formfields->get_missing_required_fields() ) {
            if ( count( $missing ) > 1 ) {
                $message = sprintf( _x( '<b>Business Directory Plugin</b> requires fields with the following associations in order to work correctly: <b>%s</b>.', 'admin', 'business-directory-plugin' ), join( ', ', $missing ) );
            } else {
                $message = sprintf( _x( '<b>Business Directory Plugin</b> requires a field with a <b>%s</b> association in order to work correctly.', 'admin', 'business-directory-plugin' ), array_pop( $missing ) );
            }

            $message .= '<br />';
			$message .= esc_html__( 'You can create these custom fields inside "Form Fields" or let Business Directory do it for you.', 'business-directory-plugin' );
            $message .= '<br /><br />';
            $message .= sprintf(
                '<a href="%s">%s</a> | ',
                esc_url( admin_url( 'admin.php?page=wpbdp_admin_formfields' ) ),
				esc_html__( 'Go to "Form Fields"', 'business-directory-plugin' )
            );
            $message .= sprintf(
                '<a href="%s">%s</a>',
				wp_nonce_url( admin_url( 'admin.php?page=wpbdp_admin_formfields&action=createrequired' ), 'createrequired' ),
                _x( 'Create these required fields for me', 'admin', 'business-directory-plugin' )
            );

            $this->messages[] = array( $message, 'error' );
        }
    }

    public function dispatch() {
        $action                 = wpbdp_get_var( array( 'param' => 'action' ), 'request' );
        $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'action', 'id' ), wpbdp_get_server_value( 'REQUEST_URI' ) );

        switch ( $action ) {
            case 'addfield':
            case 'editfield':
				$this->process_field_form();
                break;
            case 'deletefield':
				$this->delete_field();
                break;
            case 'fieldup':
            case 'fielddown':
				$this->move_field();
				$this->fields_table();
                break;
            case 'previewform':
				$this->preview_form();
                break;
            case 'createrequired':
				$this->create_required_fields();
                break;
            case 'updatetags':
				$this->update_field_tags();
                break;
            default:
				$this->fields_table();
                break;
        }
    }

    public static function admin_menu_cb() {
        $instance = new WPBDP_FormFieldsAdmin();
        $instance->dispatch();
    }

    public static function _render_field_settings() {
        $api = wpbdp_formfields_api();

		$association = wpbdp_get_var( array( 'param' => 'association', 'default' => false ), 'request' );
		$field_type  = wpbdp_get_var( array( 'param' => 'field_type', 'default' => false ), 'request' );
		$field_type  = $api->get_field_type( $field_type );
		$field_id    = wpbdp_get_var( array( 'param' => 'field_id', 'default' => 0 ), 'request' );

        $response = array(
            'ok'   => false,
            'html' => '',
        );

        if ( $field_type && in_array( $association, $field_type->get_supported_associations(), true ) ) {
            $field = $api->get_field( $field_id );

            $field_settings  = '';
            $field_settings .= $field_type->render_field_settings( $field, $association );

            ob_start();
            do_action_ref_array( 'wpbdp_form_field_settings', array( &$field, $association ) );
            $field_settings .= ob_get_contents();
            ob_end_clean();

            $response['ok']   = true;
            $response['html'] = $field_settings;
        }

        echo json_encode( $response );
        exit;
    }

    /* preview form */
	private function preview_form() {
        require_once WPBDP_INC . 'views/submit_listing.php';

        $html  = '';
        $html .= wpbdp_admin_header(
            _x( 'Form Preview', 'form-fields admin', 'business-directory-plugin' ),
            'formfields-preview',
            array(
                array(
                    '← ' . esc_html__( 'Go back', 'business-directory-plugin' ),
                    esc_url( remove_query_arg( 'action' ) ),
                ),
            )
        );
        $html .= '<div id="wpbdp-listing-form-preview">';
        $html .= wpbdp_admin_notices();
        $html .= wpbdp_capture_action( 'wpbdp_admin_form_fields_before_preview' );

        require_once WPBDP_INC . 'helpers/class-dummy-listing.php';
        $listing = new WPBDP__Dummy_Listing();
        do_action( 'wpbdp_preview_form_setup_listing', $listing );

        $html .= WPBDP__Views__Submit_Listing::preview_form( $listing );

        $html .= wpbdp_capture_action( 'wpbdp_admin_form_fields_after_preview' );
        $html .= '</div>';
        $html .= wpbdp_admin_footer();

        echo $html;
    }

    /* field list */
	private function fields_table() {
        $table = new WPBDP_FormFieldsTable();
        $table->prepare_items();

        wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/form-fields.tpl.php',
            array( 'table' => $table ),
            true
        );
    }

	/**
	 * @since 5.11
	 */
	private function check_permission( $action ) {
		$nonce = array( 'nonce' => $action );
		WPBDP_App_Helper::permission_check( 'manage_categories', $nonce );
	}

	private function process_field_form() {
		// Check permission.
		check_admin_referer( 'editfield' );

        $api = WPBDP_FormFields::instance();

        if ( isset( $_POST['field'] ) ) {
			$this->check_permission( 'editfield' );

			$field = new WPBDP_Form_Field( wpbdp_get_var( array( 'param' => 'field' ), 'post' ) );
            $res   = $field->save();

            if ( ! is_wp_error( $res ) ) {
                $this->admin->messages[] = _x( 'Form fields updated.', 'form-fields admin', 'business-directory-plugin' );
				$this->fields_table();
				return;
            }

			$errmsg = '';

			foreach ( $res->get_error_messages() as $err ) {
				$errmsg .= sprintf( '&#149; %s<br />', $err );
			}

			$this->admin->messages[] = array( $errmsg, 'error' );

        } else {
			$id    = wpbdp_get_var( array( 'param' => 'id' ) );
			$field = $id ? WPBDP_Form_Field::get( $id ) : new WPBDP_Form_Field( array( 'display_flags' => array( 'excerpt', 'search', 'listing' ) ) );
        }

		if ( ! $field ) {
			return;
		}

        if ( ! wpbdp_get_option( 'override-email-blocking' ) && $field->has_validator( 'email' ) && ( $field->display_in( 'excerpt' ) || $field->display_in( 'listing' ) ) ) {
            $msg = _x(
                '<b>Important</b>: Since the "<a>Display email address fields publicly?</a>" setting is disabled, display settings below will not be honored and this field will not be displayed on the frontend. If you want e-mail addresses to show on the frontend, you can <a>enable public display of e-mails</a>.',
                'form-fields admin',
                'business-directory-plugin'
            );
            $msg = str_replace(
                '<a>',
                '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=email' ) ) . '">',
                $msg
            );
            wpbdp_admin_message( $msg, 'error' );
        }

        require_once WPBDP_INC . 'admin/class-field-icon.php';
        $icon_drop_down = new WPBDP_Admin_Field_Icon();

        wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/form-fields-addoredit.tpl.php',
            array(
                'field'                   => $field,
                'field_associations'      => $api->get_associations_with_flags(),
                'field_types'             => $api->get_field_types(),
                'validators'              => $api->get_validators(),
                'association_field_types' => $api->get_association_field_types(),
                'hidden_fields'           => $this->hidden_fields_for_type( $field ),
                'icon_drop_down'          => $icon_drop_down->dropdown_fonts( $field->get_icon() )
            ),
            true
        );
    }

	/**
	 * Get a list of field settings that should be hidden.
	 *
	 * @param object $field WPBDP_Form_Field
	 *
	 * @since 5.8.3
	 */
	private function hidden_fields_for_type( $field ) {
		$mapping = $field->get_association();
		$fields = array(
			'limit_categories' => array( 'title', 'category' ),
			'private_field'    => array( 'title', 'category', 'content' ),
		);

		$hidden = array();
		foreach ( $fields as $name => $should_hide ) {
			if ( in_array( $mapping, $should_hide, true ) ) {
				$hidden[] = $name;
			}
		}

		if ( ! $field->display_in( 'search' ) ) {
			$hidden[] = 'search';
		}

		/**
		 * @since 5.8.3
		 */
		$hidden = apply_filters( 'wpbdp_hidden_field_settings', $hidden, compact( 'field' ) );

		return $hidden;
	}

    private function delete_field() {
		// Check permission.
		check_admin_referer( 'deletefield' );

        global $wpdb;

        $field = WPBDP_Form_Field::get( wpbdp_get_var( array( 'param' => 'id' ), 'request' ) );

        if ( ! $field || $field->has_behavior_flag( 'no-delete' ) ) {
            return;
        }

        if ( isset( $_POST['doit'] ) ) {
			$this->check_permission( 'deletefield' );
            $ret = $field->delete();

            if ( is_wp_error( $ret ) ) {
                $this->admin->messages[] = array( $ret->get_error_message(), 'error' );
            } else {
                $this->admin->messages[] = _x( 'Field deleted.', 'form-fields admin', 'business-directory-plugin' );

                $quick_search_fields = wpbdp_get_option( 'quick-search-fields' );
				$field_id            = wpbdp_get_var( array( 'param' => 'id' ), 'request' );
				$quick_search_fields = array_diff( $quick_search_fields, array( $field_id ) );

                wpbdp_set_option( 'quick-search-fields', $quick_search_fields );
            }

			$this->fields_table();
			return;
        }

        wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/form-fields-confirm-delete.tpl.php',
            array( 'field' => $field ),
            true
        );
    }

	/**
	 * @since 5.11
	 */
	private function move_field() {
		$this->check_permission( 'movefield' );

		$field_id = wpbdp_get_var( array( 'param' => 'id' ), 'request' );
		$field = $this->api->get_field( $field_id );
		if ( $field ) {
			$field->reorder( $action === 'fieldup' ? 1 : -1 );
		}
	}

	private function create_required_fields() {
		$this->check_permission( 'createrequired' );

        global $wpbdp;

        if ( $missing = $wpbdp->formfields->get_missing_required_fields() ) {
            $wpbdp->formfields->create_default_fields( $missing );
            $this->admin->messages[] = _x( 'Required fields created successfully.', 'form-fields admin', 'business-directory-plugin' );
        }

		$this->fields_table();
    }

    private function update_field_tags() {
        global $wpbdp;

        // Before starting, check if we need to update tags.
        $wpbdp->formfields->maybe_correct_tags();

        $special_tags = array(
            'title'    => __( 'Title', 'business-directory-plugin' ),
            'category' => __( 'Category', 'business-directory-plugin' ),
            'excerpt'  => __( 'Excerpt', 'business-directory-plugin' ),
            'content'  => __( 'Content', 'business-directory-plugin' ),
            'tags'     => __( 'Tags', 'business-directory-plugin' ),
            'address'  => __( 'Address', 'business-directory-plugin' ),
            'address2' => __( 'Address 2', 'business-directory-plugin' ),
            'city'     => __( 'City', 'business-directory-plugin' ),
            'state'    => __( 'State', 'business-directory-plugin' ),
            'country'  => __( 'Country', 'business-directory-plugin' ),
            'zip'      => __( 'ZIP Code', 'business-directory-plugin' ),
            'fax'      => __( 'FAX Number', 'business-directory-plugin' ),
            'phone'    => __( 'Phone Number', 'business-directory-plugin' ),
            'ratings'  => __( 'Ratings Field', 'business-directory-plugin' ),
            'twitter'  => __( 'Twitter', 'business-directory-plugin' ),
            'website'  => __( 'Website', 'business-directory-plugin' ),
        );
        $fixed_tags   = array( 'title', 'category', 'excerpt', 'content', 'tags', 'ratings' );
        $field_tags   = array();

        if ( isset( $_POST['field_tags'] ) ) {
			// Check permission.
			$this->check_permission( 'fieldtags' );

            global $wpdb;

			$posted = wpbdp_get_var( array( 'param' => 'field_tags' ), 'post' );

            foreach ( $posted as $tag => $field_id ) {
                if ( in_array( $tag, $fixed_tags, true ) ) {
                    continue;
                }

				$wpdb->update( $wpdb->prefix . 'wpbdp_form_fields', array( 'tag' => '' ), array( 'tag' => $tag ) );
				$wpdb->update( $wpdb->prefix . 'wpbdp_form_fields', array( 'tag' => $tag ), array( 'id' => $field_id ) );

				WPBDP_Utils::cache_delete_group( 'wpbdp_form_fields' );
            }

            wpbdp_admin_message( _x( 'Tags updated.', 'form-fields admin', 'business-directory-plugin' ) );
        }

        $missing_fields = $wpbdp->themes->missing_suggested_fields( 'label' );

        foreach ( $special_tags as $t => $td ) {
            $f = WPBDP_Form_Field::find_by_tag( $t );

            $field_tags[] = array(
                'tag'         => $t,
                'description' => $td,
                'field_id'    => ( $f ? $f->get_id() : 0 ),
                'fixed'       => ( in_array( $t, $fixed_tags, true ) ? true : false ),
            );
        }

        echo wpbdp_render_page(
            WPBDP_PATH . 'templates/admin/form-fields-tags.tpl.php',
            array(
                'field_tags'     => $field_tags,
                'missing_fields' => $missing_fields,
            )
        );
    }

}
