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
            /*'short_id' => _x('Short ID', 'form-fields admin', 'WPBDM'),*/
        	'label' => _x('Label / Association', 'form-fields admin', 'WPBDM'),
        	'type' => _x('Type', 'form-fields admin', 'WPBDM'),
        	'validator' => _x('Validator', 'form-fields admin', 'WPBDM'),
        	'tags' => '',
		);
    }

	public function prepare_items() {
		$this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

		$api = wpbdp_formfields_api();
		$this->items = $api->getFormFields();
	}

	/* Rows */
	public function column_order($field) {
		return sprintf('<a href="%s"><strong>↑</strong></a> | <a href="%s"><strong>↓</strong></a>',
					   esc_url(add_query_arg(array('action' => 'fieldup', 'id' => $field->id))),
					   esc_url(add_query_arg(array('action' => 'fielddown', 'id' => $field->id)))
					   );
	}

    public function column_short_id($field) {
        $short_names = wpbdp_formfields_api()->getShortNames();
        return $short_names[$field->id];
    }

	public function column_label($field) {
		$actions = array();
		$actions['edit'] = sprintf('<a href="%s">%s</a>',
								   esc_url(add_query_arg(array('action' => 'editfield', 'id' => $field->id))),
								   _x('Edit', 'form-fields admin', 'WPBDM'));

		if (!in_array($field->association, array('title', 'content', 'category'))) {
			$actions['delete'] = sprintf('<a href="%s">%s</a>',
										esc_url(add_query_arg(array('action' => 'deletefield', 'id' => $field->id))),
										_x('Delete', 'form-fields admin', 'WPBDM'));
		}

		$html = '';
		$html .= sprintf('<strong><a href="%s">%s</a></strong> (as <i>%s</i>)',
						 esc_url(add_query_arg(array('action' => 'editfield', 'id' => $field->id))),
						 esc_attr($field->label),
						 $field->association);
		$html .= $this->row_actions($actions);

		return $html;
	}

	public function column_type($field) {
		return ucwords($field->type);
	}

	public function column_validator($field) {
		if ($field->validator) {
			return $field->validator;
		}

		return '';
	}

	public function column_tags($field) {
		$html = '';

		$html .= sprintf('<span class="tag %s">%s</span>',
						 $field->is_required ? 'required' : 'optional',
						 $field->is_required ? _x('Required', 'form-fields admin', 'WPBDM') : _x('Optional', 'form-fields admin', 'WPBDM'));

		if ($field->display_options['show_in_excerpt']) {
			$html .= sprintf('<span class="tag in-excerpt">%s</span>',
							 _x('In Excerpt', 'form-fields admin', 'WPBDM'));
		}

		return $html;
	}

}

/* <old stuff> */
function wpbdp_admin_add_listing() {
	// FIXME
	return null;
}
/* <old stuff> */

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
		if (isset($_POST['field'])) {
			$newfield = $_POST['field'];

			if ($this->api->addorUpdateField($newfield, $errors)) {
				$this->admin->messages[] = _x('Form fields updated.', 'form-fields admin', 'WPBDM');
				return $this->fieldsTable();
			} else {
				$errmsg = '';
				foreach ($errors as $err)
					$errmsg .= sprintf('&#149; %s<br />', $err);
				
				$this->admin->messages[] = array($errmsg, 'error');
			}
		}

		$field = isset($_GET['id']) ? $this->api->getField($_GET['id']) : null;

		wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields-addoredit.tpl.php',
						  array('field' => $field),
						  true);
	}

	private function deleteField() {
		global $wpdb;

		if (isset($_POST['doit'])) {
			if (!$this->api->deleteField($_POST['id'], $errors)) {
				$errmsg = '';
				foreach ($errors as $err)
					$errmsg .= sprintf('&#149; %s<br />', $err);
				
				$this->admin->messages[] = array($errmsg, 'error');
			} else {
				$this->admin->messages[] = _x('Field deleted.', 'form-fields admin', 'WPBDM');
			}

			return $this->fieldsTable();
		} else {
			if ($field = $this->api->getField($_REQUEST['id'])) {
				wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields-confirm-delete.tpl.php',
								  array('field' => $field),
								  true);
			}
		}

	}

	private function createRequiredFields() {
		$missing = $this->api->check_for_required_fields();

		if ($missing) {
			$default_fields = $this->api->getDefaultFields();

			foreach ($missing as $field_assoc) {
				$field = $default_fields[$field_assoc];
				$this->api->addorUpdateField($field);
			}
		}

		$this->admin->messages[] = _x('Required fields created successfully.', 'form-fields admin', 'WPBDM');
		return $this->fieldsTable();
	}

}