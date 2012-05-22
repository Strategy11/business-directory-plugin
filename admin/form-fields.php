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
function wpbusdirman_display_postform_preview()
{
	$html = '';

	$html .= "<h3 style=\"padding:10px;\">" . __("Previewing the post form","WPBDM") . "</h3>";
	$html .= "<div style=\"float:right; margin-top:-49px;margin-right:250px;border-left:1px solid#ffffff;padding:10px;\"><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c3\">" . __("Manage Form Fields","WPBDM") . "</a></div>";
	$html .= apply_filters('wpbdm_show-add-listing-form', '-1', '', '', '');

	return $html;
}

function wpbusdirman_display_postform_add()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_3'] == "yes")
	{
		if(!is_user_logged_in())
		{
			$wpbusdirman_loginurl=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_4'];
			if(!isset($wpbusdirman_loginurl) || empty($wpbusdirman_loginurl))
			{
				$wpbusdirman_loginurl=get_option('siteurl').'/wp-login.php';
			}
			$html .= "<p>" . __("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 minutes.","WPBDM") . "</p>";
			$html .= "<form method=\"post\" action=\"$wpbusdirman_loginurl\"><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Login or Register First","WPBDM") . "\"></form>";
		}
		else
		{
			$html .= "<h3 style=\"padding:10px;\">" . __("Add New Listing","WPBDM") . "</h3>";
			$html .= "<div style=\"float:right; margin-top:-49px;margin-right:250px;border-left:1px solid#ffffff;padding:10px;\"><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c3\">" . __("Manage Form Fields","WPBDM") . "</a></div><p>";
			$html .= apply_filters('wpbdm_show-add-listing-form', 1, '', '', '');
		}
	}
	else
	{
		$html .= apply_filters('wpbdm_show-add-listing-form', 1, '', '', '');
	}

	return $html;
}

function wpbdp_admin_add_listing() {
	// FIXME
	return null;
}

function wpbusdirman_exists_association($association,$label)
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_exists_association=false;

	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_association_');

	if($wpbusdirman_field_vals)
	{
		foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
		{

			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val) == $association)
			{
				$wpbdmassocid=$wpbusdirman_field_val;
				$wpbusdirman_ftitle=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbdmassocid);


				//If the field label value is the same as the association value then return false
				if($wpbusdirman_ftitle == $label)
				{
					$wpbusdirman_exists_association=false;
				}
				else
				{
					//Otherwise return true
					$wpbusdirman_exists_association=true;
				}
			}
		}
	}

	return $wpbusdirman_exists_association;
}

function wpbusdirman_exists_validation($validation)
{

	global $wpbusdirmanconfigoptionsprefix;;
	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_validation_');

	if($wpbusdirman_field_vals)
	{
		foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
		{

			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_val) == $validation)
			{
				$wpbusdirman_exists_validation=true;
			}
			else
			{
				$wpbusdirman_exists_validation=false;
			}

		}
	}

	return $wpbusdirman_exists_validation;
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

    	$html .= wpbdp_admin_header(_x('Form Preview', 'form-fields admin', 'WPBDM'), null, array(
			array(_x('← Return to "Manage Form Fields"', 'form-fields admin', 'WPBDM'), esc_url(remove_query_arg('action')))
    	));
    	$html .= apply_filters('wpbdm_show-add-listing-form', '-1', '', '', '');
    	$html .= wpbdp_admin_footer();

    	echo $html;
    }

	/*echo wpbdp_admin_header(null, null, array(
		array(_x('Add New Form Field', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfield'))),
		array(_x('Preview Form', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'previewform'))),
	));*/

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

}