<?php
if (!class_exists('WP_List_Table'))
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_FormFieldsTable extends WP_List_Table {

	public function __construct() {
		parent::__construct(array(
			'singular' => _x('form field', 'form-fields admin', 'WPBDM'),
			'plural' => _x('form fields', 'form-fields admin', 'WPBDM'),
			'ajax' => false
		));
	}

    public function get_columns() {
        return array(
        	'order' => _x('Order', 'form-fields admin', 'WPBDM'),
        	'label' => _x('Label / Association', 'form-fields admin', 'WPBDM'),
        	'type' => _x('Type', 'form-fields admin', 'WPBDM'),
        	'validator' => _x('Validator', 'form-fields admin', 'WPBDM'),
        	'tags' => '',
		);
    }

	public function prepare_items() {
		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

		$formfields_api = WPBDP_FormFields::instance();
		$this->items = $formfields_api->get_fields();
	}

	/* Rows */
	public function column_order($field) {
		return sprintf( '<a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
					    esc_url( add_query_arg( array('action' => 'fieldup', 'id' => $field->get_id() ) ) ) ,
					    esc_url( add_query_arg( array('action' => 'fielddown', 'id' => $field->get_id() ) ) )
					   );
	}

	public function column_label( $field ) {
		$actions = array();
		$actions['edit'] = sprintf( '<a href="%s">%s</a>',
								    esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $field->get_id() ) ) ),
								    _x( 'Edit', 'form-fields admin', 'WPBDM' ) );

		$actions['delete'] = sprintf( '<a href="%s">%s</a>',
									 esc_url( add_query_arg( array( 'action' => 'deletefield', 'id' => $field->get_id() ) ) ),
									 _x( 'Delete', 'form-fields admin', 'WPBDM') );

		$html = '';
		$html .= sprintf( '<strong><a href="%s">%s</a></strong> (as <i>%s</i>)',
						  esc_url( add_query_arg( array( 'action' => 'editfield', 'id' => $field->get_id() ) ) ),
						  esc_attr( $field->get_label() ),
						  $field->get_association() );
		$html .= $this->row_actions( $actions );

		return $html;
	}

	public function column_type( $field ) {
		return esc_html( $field->get_field_type()->get_name() );
	}

	public function column_validator( $field ) {
		return esc_html( implode( ',',  $field->get_validators() ) );
	}

	public function column_tags( $field ) {
		$html = '';

		$html .= sprintf( '<span class="tag %s">%s</span>',
						  $field->is_required() ? 'required' : 'optional',
						  $field->is_required() ? _x( 'Required', 'form-fields admin', 'WPBDM' ) : _x( 'Optional', 'form-fields admin', 'WPBDM' ) );

		if ( $field->display_in( 'excerpt' ) ) {
			$html .= sprintf( '<span class="tag in-excerpt" title="%s">%s</span>',
                              _x( 'This field value is shown in the excerpt view of a listing.', 'form-fields admin', 'WPBDM' ),
							  _x( 'In Excerpt', 'form-fields admin', 'WPBDM' ) );
		}

        if ( $field->display_in( 'listing' ) ) {
            $html .= sprintf( '<span class="tag in-listing" title="%s">%s</span>',
                              _x( 'This field value is shown in the single view of a listing.', 'form-fields admin', 'WPBDM' ),
                              _x( 'In Listing', 'form-fields admin', 'WPBDM' ) );
        }        

		return $html;
	}

}

class WPBDP_FormFieldsAdmin {

	public function __construct() {
		$this->api = wpbdp_formfields_api();
		$this->admin = wpbdp()->admin;
	}

    public function dispatch() {
    	$action = wpbdp_getv($_REQUEST, 'action');
    	$_SERVER['REQUEST_URI'] = remove_query_arg(array('action', 'id'), $_SERVER['REQUEST_URI']);

    	switch ($action) {
    		case 'addfield':
    		case 'editfield':
    			$this->processFieldForm();
    			break;
    		case 'deletefield':
    			$this->deleteField();
    			break;
    		case 'fieldup':
    		case 'fielddown':
    			$this->api->reorderField($_REQUEST['id'], $action == 'fieldup' ? 1 : -1);
    			$this->fieldsTable();
    			break;
    		case 'previewform':
    			$this->previewForm();
    			break;
    		case 'createrequired':
    			$this->createRequiredFields();
    			break;
    		default:
    			$this->fieldsTable();
    			break;
    	}
    }

    public static function admin_menu_cb() {
    	$instance = new WPBDP_FormFieldsAdmin();
    	$instance->dispatch();
    }

    /* preview form */
    private function previewForm() {
    	$html = '';

    	$html .= wpbdp_admin_header(_x('Form Preview', 'form-fields admin', 'WPBDM'), 'formfields-preview', array(
			array(_x('← Return to "Manage Form Fields"', 'form-fields admin', 'WPBDM'), esc_url(remove_query_arg('action')))
    	));

        $controller = wpbdp()->controller;
        $html .= $controller->submit_listing();
    	$html .= wpbdp_admin_footer();

    	echo $html;
    }

    /* field list */
    private function fieldsTable() {
    	$table = new WPBDP_FormFieldsTable();
    	$table->prepare_items();

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields.tpl.php',
                          array('table' => $table),
                          true);
    }

	private function processFieldForm() {
		$api = WPBDP_FormFields::instance();

/*		if (isset($_POST['field'])) {
			$newfield = $_POST['field'];

            if (!isset($newfield['display_options']))
                $newfield['display_options'] = array('show_in_excerpt' => 0, 'show_in_listing' => 0, 'show_in_search' => 0);

			if ($this->api->addorUpdateField($newfield, $errors)) {
				$this->admin->messages[] = _x('Form fields updated.', 'form-fields admin', 'WPBDM');
				return $this->fieldsTable();
			} else {
				$errmsg = '';
				foreach ($errors as $err)
					$errmsg .= sprintf('&#149; %s<br />', $err);
				
				$this->admin->messages[] = array($errmsg, 'error');
			}
		}*/

		$field = isset( $_GET['id'] ) ? WPBDP_FormField::get( $_GET['id'] ) : new WPBDP_FormField();

		wpbdp_render_page( WPBDP_PATH . 'admin/templates/form-fields-addoredit.tpl.php',
						   array(
						   	'field' => $field,
						   	'field_associations' => $api->get_associations(),
						   	'field_types' => $api->get_field_types(),
                            'validators' => $api->get_validators()
						   ),
						   true );
	}

	private function deleteField() {
		global $wpdb;

        $field = WPBDP_FormField::get( $_REQUEST['id'] );

        if ( !$field )
            return;

		if ( isset( $_POST['doit'] ) ) {
            $ret = $field->delete();

            if ( is_wp_error( $ret ) ) {
                $this->admin->messages[] = array( $ret->get_error_message(), 'error' );
            } else {
                $this->admin->messages[] = _x( 'Field deleted.', 'form-fields admin', 'WPBDM' );
            }

			return $this->fieldsTable();
		}

		wpbdp_render_page( WPBDP_PATH . 'admin/templates/form-fields-confirm-delete.tpl.php',
						   array( 'field' => $field ),
						   true );
	}

	private function createRequiredFields() {
		global $wpbdp;

		$wpbdp->formfields->create_default_fields( $wpbdp->formfields->get_missing_required_fields() );

		$this->admin->messages[] = _x('Required fields created successfully.', 'form-fields admin', 'WPBDM');

		return $this->fieldsTable();
	}

}