<?php
function wpbusdirman_fields_list()
{
	global $wpbusdirmanconfigoptionsprefix,$wpbusdirman_hide_formlist, $wpbusdirman_labeltext, $wpbusdirman_typetext,$wpbusdirman_optionstext,$wpbusdirman_ordertext,$wpbusdirman_actiontext,$wpbusdirman_associationtext,$wpbusdirman_validationtext,$wpbusdirman_requiredtext,$wpbusdirman_showinexcerpttext;
	$html = '';

	if(!$wpbusdirman_hide_formlist)
	{
		$html .= "<h3 style=\"padding:10px;\">" . __("Manage Form Fields","WPBDM") . "</h3><p>" . __("Make changes to your existing form fields.","WPBDM") . "<p><a href=\"?page=wpbdman_c3&action=addnewfield\">" . __("Add New Form Field","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=viewpostform\">" . __("Preview Form","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=addnewlisting\">" . __("Add New Listing","WPBDM") . "</a></p>";
		$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
		$html .= "<table class=\"widefat\" cellspacing=\"0\"><thead><tr>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_labeltext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_typetext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_associationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_validationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_optionstext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_requiredtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_showinexcerpttext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
		$html .= "</tr></thead><tfoot><tr>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_labeltext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_typetext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_associationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_validationtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_optionstext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_requiredtext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_showinexcerpttext . "</th>";
		$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
		$html .= "</tr></tfoot><tbody>";
		if($wpbusdirman_field_vals)
		{
			foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
			{
				$wpbdm_thefieldlabel=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
				$wpbdm_thefieldassociation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
				$wpbdm_thefieldrequired=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_field_val);
				$wpbdm_thefieldshowinexcerpt=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val);
				/* New option added by Mike Bronner */ $wpbdm_thefieldhide = get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_hide_'.$wpbusdirman_field_val);
				$wpbdm_thefieldtype=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);


				$html .= "<tr><td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val)."</td><td>";
				$wpbusdirman_optypeval=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);
				switch ($wpbusdirman_optypeval)
				{
					case 1:
						$wpbusdirman_optype_descr="Text Box";
						break;
					case 2:
						$wpbusdirman_optype_descr="Select List";
						break;
					case 3:
						$wpbusdirman_optype_descr="Textarea";
						break;
					case 4:
						$wpbusdirman_optype_descr="Radio Button";
						break;
					case 5:
						$wpbusdirman_optype_descr="Multi-Select List";
						break;
					case 6:
						$wpbusdirman_optype_descr="Checkbox";
						break;
				}
				$html .= $wpbusdirman_optype_descr . "</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td>";
				$wpbusdirman_field_options=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_field_val);
				$wpbusdirman_field_options_array=explode(",",$wpbusdirman_field_options);
				for ($i=0;isset($wpbusdirman_field_options_array[$i]);++$i)
				{
					$wpbusdirman_field_options_arritems[$i]=trim($wpbusdirman_field_options_array[$i]);
					$html .= "<ul><li>" . $wpbusdirman_field_options_array[$i] . "</li></ul>";
				}
				$html .= "</td><td>" . get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_field_val) . "</td>";
				$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val)."</td>";
				$html .= "<td><a href=\"?page=wpbdman_c3&action=editfield&id=$wpbusdirman_field_val\">" . __("Edit","WPBDM") . "</a> | <a href=\"?page=wpbdman_c3&action=deletefield&id=$wpbusdirman_field_val\">" . __("Delete","WPBDM") . "</a></td></tr>";
			}
		}
		$html .= "</tbody></table>";
	}

	return $html;
}


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
			$html .= "<p>" . __("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 mintues.","WPBDM") . "</p>";
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

function wpbusdirman_buildform()
{
	global $table_prefix,$wpbusdirmanconfigoptionsprefix;;
	$wpbusdirman_error=false;
	$wpbusdirman_notify='';
	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
	$wpbusdirman_field_vals_max=max($wpbusdirman_field_vals);
	$wpbusdirman_autoincrementfieldorder=0;
	$wpbusdirman_error_message='';
	$wpbusdirmanaction='';
	$html = '';

	$html .= wpbdp_admin_header();
	if(isset($_REQUEST['action'])
		&& !empty($_REQUEST['action']))
	{
		$wpbusdirmanaction=$_REQUEST['action'];
	}
	if ( $wpbusdirmanaction == 'viewpostform')
	{
		$html .= wpbusdirman_display_postform_preview();
	}
	elseif ( $wpbusdirmanaction == 'addnewlisting')
	{
		$html .= wpbusdirman_display_postform_add();
	}
	elseif ( $wpbusdirmanaction == 'updateoptions')
	{
		$whichtext=$_REQUEST['whichtext'];
		if(isset($whichtext) && !empty($whichtext))
		{
			$wpbusdirman_add_update_option="update_option";
		}
		else
		{
			$wpbdmmissing=array();
			foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
			{
				$wpbdm_thefieldlabel=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
				$wpbdm_thefieldassociation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
				$wpbdm_thefieldtype=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);

				if(!$wpbdm_thefieldlabel && !$wpbdm_thefieldassociation && !$wpbdm_thefieldtype){
				$wpbdmmissing[]=$wpbusdirman_field_val;
				}
			}

			if($wpbdmmissing){$whichtext=$wpbdmmissing[0];$wpbusdirman_autoincrementfieldorder=0;}else {$whichtext=($wpbusdirman_field_vals_max+1);$wpbusdirman_autoincrementfieldorder=1;}
			$wpbusdirman_add_update_option="add_option";
		}
		if(!isset($_REQUEST['wpbusdirman_field_label'])
			|| empty($_REQUEST['wpbusdirman_field_label']))
		{
				$wpbusdirman_error=true;
				$wpbusdirman_error_message.="<li>";
				$wpbusdirman_error_message.=__("Field NOT added! You have submitted the form without a field label. A field label is required before the field can be added. Please try adding the field again.","WPBDM");
				$wpbusdirman_error_message.="</li>";
		}
		if(!isset($_REQUEST['wpbusdirman_field_label'])
			|| empty($_REQUEST['wpbusdirman_field_label']))
		{
				$wpbusdirman_error=true;
				$wpbusdirman_error_message.="<li>";
				$wpbusdirman_error_message.=__("Field NOT added! You have submitted the form without a field label. A field label is required before the field can be added. Please try adding the field again.","WPBDM");
				$wpbusdirman_error_message.="</li>";
		}
		else
		{
			if(!isset($_REQUEST['wpbusdirman_field_association'])
				|| empty($_REQUEST['wpbusdirman_field_association']))
			{
				$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_association_'.$whichtext, "meta"  );
			}
			elseif(isset($_REQUEST['wpbusdirman_field_association']) && !empty($_REQUEST['wpbusdirman_field_association']) )
			{
				if( $_REQUEST['wpbusdirman_field_association'] != 'meta')
				{
					if(wpbusdirman_exists_association($_REQUEST['wpbusdirman_field_association'],$_REQUEST['wpbusdirman_field_label']))
					{
						$wpbusdirman_error=true;
						$wpbusdirman_error_message.="<li>";
						$wpbusdirman_error_message.=__("You tried to associate a field with a wordpress post title, category, tag, description, excerpt but another field is already associated with the element. The field has been associated with the post meta entity instead.","WPBDM");
						$wpbusdirman_error_message.="</li>";
						$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_association_'.$whichtext, "meta"  );
					}
					else
					{
						$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_association_'.$whichtext, $_REQUEST['wpbusdirman_field_association']  );
					}
				}
				else
				{
					$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_association_'.$whichtext, $_REQUEST['wpbusdirman_field_association']  );
				}
			}
			if(!isset($_REQUEST['wpbusdirman_field_required'])
				|| empty($_REQUEST['wpbusdirman_field_required']))
			{
				$_REQUEST['wpbusdirman_field_required']="no";
			}
			if(!isset($_REQUEST['wpbusdirman_field_showinexcerpt'])
				|| empty($_REQUEST['wpbusdirman_field_showinexcerpt']))
			{
				$_REQUEST['wpbusdirman_field_showinexcerpt']="no";
			}
			if( $_REQUEST['wpbusdirman_field_association'] == 'category')
			{
				if( $_REQUEST['wpbusdirman_field_type'] == 1
					||  $_REQUEST['wpbusdirman_field_type'] == 3
					||  $_REQUEST['wpbusdirman_field_type'] == 4
					||  $_REQUEST['wpbusdirman_field_type'] == 5 )
				{
					$wpbusdirman_error=true;
					$wpbusdirman_error_message.="<li>";
					$wpbusdirman_error_message.=__("The category field can only be assigned to the single option dropdown select list or checkbox type. It has been defaulted to a select list. If you want the user to be able to select multiple categories use the checkbox field type.","WPBDM");
					$wpbusdirman_error_message.="</li>";
					$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_type_'.$whichtext, "2"  );
				}
				else
				{
					$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_type_'.$whichtext, $_REQUEST['wpbusdirman_field_type']  );
				}
			}
			if($_REQUEST['wpbusdirman_field_validation'] == 'email')
			{
				if(!wpbusdirman_exists_validation($validation='email'))
				{
					$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_validation_'.$whichtext, $_REQUEST['wpbusdirman_field_validation']  );
				}
				else
				{
					$wpbusdirman_error=true;
					$wpbusdirman_error_message.="<li>";
					$wpbusdirman_error_message.=__("You already have a field using the email validation. At this time the system will allow only 1 valid email field. Change the validation for that field to something else then try again.","WPBDM");
					$wpbusdirman_error_message.="</li>";
				}
			}
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_label_'.$whichtext, $_REQUEST['wpbusdirman_field_label']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_type_'.$whichtext, $_REQUEST['wpbusdirman_field_type']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_options_'.$whichtext, $_REQUEST['wpbusdirman_field_options']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_required_'.$whichtext, $_REQUEST['wpbusdirman_field_required']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_showinexcerpt_'.$whichtext, $_REQUEST['wpbusdirman_field_showinexcerpt']  );
			/* New option added by Mike Bronner */ $wpbusdirman_add_update_option( 'wpbusdirman_postform_field_hide_'.$whichtext, $_REQUEST['wpbusdirman_field_hide']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_postform_field_validation_'.$whichtext, $_REQUEST['wpbusdirman_field_validation']  );

		}
		$html .= wpbusdirman_fields_list();
		if($wpbusdirman_error)
		{
			$wpbusdirman_notify="<div class=\"updated fade\" style=\"padding:10px;background:#FF8484;font-weight:bold;\"><ul>";
			$wpbusdirman_notify.=$wpbusdirman_error_message;
			$wpbusdirman_notify.="</ul></div>";
			$html .= $wpbusdirman_notify;
		}
	}
	elseif (($wpbusdirmanaction == 'deletefield'))
	{
		if (isset($_REQUEST['id'])
			&& !empty($_REQUEST['id']))
		{
			$wpbusdirman_fieldid_todel=$_REQUEST['id'];
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_label_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_type_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_options_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_association_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_validation_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_required_'.$wpbusdirman_fieldid_todel);}
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_showinexcerpt_'.$wpbusdirman_fieldid_todel);}
			/* New option added by Mike Bronner */if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_hide_'.$wpbusdirman_fieldid_todel)){delete_option('wpbusdirman_postform_field_hide_'.$wpbusdirman_fieldid_todel);}
			$wpbusdirman_delete_message=__("The field has been deleted.","WPBDM");
		}
		else
		{
			$wpbusdirman_delete_message=__("There was no ID supplied for the field. No action has been taken","WPBDM");
		}
		$wpbusdirman_notify="<div class=\"updated fade\" style=\"padding:10px;\"><ul>";
		$wpbusdirman_notify.=$wpbusdirman_delete_message;
		$wpbusdirman_notify.="</ul></div>";
		$html .= $wpbusdirman_notify;
		$html .= wpbusdirman_fields_list();
	}
	elseif(($wpbusdirmanaction == 'addnewfield')
		|| ($wpbusdirmanaction == 'editfield'))
	{
		$wpbusdirman_fieldtoedit='';
		if(isset($_REQUEST['id']) && !empty($_REQUEST['id']))
		{
			$wpbusdirman_fieldtoedit=$_REQUEST['id'];
		}
		if(isset($wpbusdirman_fieldtoedit) && !empty($wpbusdirman_fieldtoedit))
		{
			$html .= "<p>" . __("Make your changes then submit the form to update the field","WPBDM") . "<p><a href=\"?page=wpbdman_c3&action=addnewfield\">" . __("Add New Form Field","WPBDM") . "</a></p>";
		}
		else
		{
			$html .= "<p>" . __("Add extra fields to the standard fields used in the form that users will fill out to submit their business directory listing.<p>Please note that your submission form <b>MUST</b> have [1] field associated with the 'Post Title', [1] field associated with the 'Post Content' and [1] field associated 'Post Category'. It cannot have more than 1 field associated with 'Post Category'. It cannot have more than 1 field associated with 'Post Title'. It cannot have more than 1 field associated with 'Post Content'. It <b>MUST</b> have 1 field that serves as the post title, 1 field that serves as the post content and 1 field that serves as the post category in order for it to work correctly. If you are submitting listings and they are not appearing on your site or on the directory management page, the primary reason for this is that your form is either missing the post title associated field, the post content association field and/or the post category association field.</p>","WPBDM") . "</p>";
		}
		$html .= "<h3 style=\"padding:10px;\">";
		if(isset($wpbusdirman_fieldtoedit) && !empty($wpbusdirman_fieldtoedit))
		{
			$html .= __("Edit Field","WPBDM");
		}
		else
		{
			$html .= __("Add New Field","WPBDM");
		}
		$html .= "</h3>";
		$wpbusdirman_currenttype=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_fieldtoedit);
		$wpbusdirman_currentassociation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_fieldtoedit);
		$wpbusdirman_currentvalidation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_fieldtoedit);
		$wpbusdirman_currentrequired=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_fieldtoedit);
		$wpbusdirman_currentshowinexcerpt=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_fieldtoedit);
		/* New option added by Mike Bronner */ $wpbusdirman_currenthide = get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_hide_'.$wpbusdirman_fieldtoedit);
		if($wpbusdirman_currentvalidation == 'email')
		{
			$wpbusdirman_validation1="selected";
		}
		else
		{
			$wpbusdirman_validation1="";
		}
		if($wpbusdirman_currentvalidation == 'url')
		{
			$wpbusdirman_validation2="selected";
		}
		else
		{
			$wpbusdirman_validation2="";
		}
		if($wpbusdirman_currentvalidation == 'missing')
		{
			$wpbusdirman_validation3="selected";
		}
		else
		{
			$wpbusdirman_validation3="";
		}
		if($wpbusdirman_currentvalidation == 'numericdeci')
		{
			$wpbusdirman_validation4="selected";
		}
		else
		{
			$wpbusdirman_validation4="";
		}
		if($wpbusdirman_currentvalidation == 'numericwhole')
		{
			$wpbusdirman_validation5="selected";
		}
		else
		{
			$wpbusdirman_validation5="";
		}
		if($wpbusdirman_currentvalidation == 'date')
		{
			$wpbusdirman_validation6="selected";
		}
		else
		{
			$wpbusdirman_validation6="";
		}
		if($wpbusdirman_currentassociation == 'title')
		{
			$wpbusdirman_associationselected1="selected";
		}
		else
		{
			$wpbusdirman_associationselected1="";
		}
		if($wpbusdirman_currentassociation == 'description')
		{
			$wpbusdirman_associationselected2="selected";
		}
		else
		{
			$wpbusdirman_associationselected2="";
		}
		if($wpbusdirman_currentassociation == 'category')
		{
			$wpbusdirman_associationselected3="selected";
		}
		else
		{
			$wpbusdirman_associationselected3="";
		}
		if($wpbusdirman_currentassociation == 'excerpt')
		{
			$wpbusdirman_associationselected4="selected";
		}
		else
		{
			$wpbusdirman_associationselected4="";
		}
		if($wpbusdirman_currentassociation == 'meta')
		{
			$wpbusdirman_associationselected5="selected";
		}
		else
		{
			$wpbusdirman_associationselected5="";
		}
		if($wpbusdirman_currentassociation == 'tags')
		{
			$wpbusdirman_associationselected6="selected";
		}
		else
		{
			$wpbusdirman_associationselected6="";
		}

		if($wpbusdirman_currenttype == 1)
		{
			$wpbusdirman_op_selected1="selected";
		}
		else
		{
			$wpbusdirman_op_selected1='';
		}
		if($wpbusdirman_currenttype == 2)
		{
			$wpbusdirman_op_selected2="selected";
		}
		else
		{
			$wpbusdirman_op_selected2='';
		}
		if($wpbusdirman_currenttype == 3)
		{
			$wpbusdirman_op_selected3="selected";
		}
		else
		{
			$wpbusdirman_op_selected3='';
		}
		if($wpbusdirman_currenttype == 4)
		{
			$wpbusdirman_op_selected4="selected";
		}
		else
		{
			$wpbusdirman_op_selected4='';
		}
		if($wpbusdirman_currenttype == 5)
		{
			$wpbusdirman_op_selected5="selected";
		}
		else
		{
			$wpbusdirman_op_selected5='';
		}
		if($wpbusdirman_currenttype == 6)
		{
			$wpbusdirman_op_selected6="selected";
		}
		else
		{
			$wpbusdirman_op_selected6='';
		}
		if($wpbusdirman_currentrequired == 'yes')
		{
			$wpbusdirman_required_selected1="selected";
		}
		else
		{
			$wpbusdirman_required_selected1='';
		}
		if($wpbusdirman_currentrequired == 'no')
		{
			$wpbusdirman_required_selected2="selected";
		}
		else
		{
			$wpbusdirman_required_selected2='';
		}
		if($wpbusdirman_currentshowinexcerpt == 'yes')
		{
			$wpbusdirman_showinexcerpt_selected1="selected";
		}
		else
		{
			$wpbusdirman_showinexcerpt_selected1='';
		}
		if($wpbusdirman_currentshowinexcerpt == 'no')
		{
			$wpbusdirman_showinexcerpt_selected2="selected";
		}
		else
		{
			$wpbusdirman_showinexcerpt_selected2='';
		}

		$wpbusdirman_hide_selected1 = '';
		$wpbusdirman_hide_selected2 = '';
		if($wpbusdirman_currenthide == 'no')
		{
			$wpbusdirman_hide_selected1 = "selected=\"selected\"";
		}
		if($wpbusdirman_currenthide == 'yes')
		{
			$wpbusdirman_hide_selected2 = "selected=\"selected\"";
		}




		$html .= "<div style=\"float:right; margin-top:-49px;margin-right:250px;border-left:1px solid#ffffff;padding:10px;\"><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c3&action=viewpostform\">" . __("Preview the form","WPBDM") . "</a></div>";
		$html .= "<form method=\"post\"><p>" . __("Field Label","WPBDM") . "<br />";
		$html .= "<input type=\"text\" name=\"wpbusdirman_field_label\" style=\"width:50%;\" value=\"" . get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_' . $wpbusdirman_fieldtoedit) . "\"></p>" . __("Field Type",'WPBDM') . " <select name=\"wpbusdirman_field_type\">";
		$html .= "<option value=\"\">" . __("Select Field Type","WPBDM") . "</option>";
		$html .= "<option value=\"1\" $wpbusdirman_op_selected1>" . __("Input Text Box","WPBDM") . "</option>";
		$html .= "<option value=\"2\" $wpbusdirman_op_selected2>" . __("Select List","WPBDM") . "</option>";
		$html .= "<option value=\"5\" $wpbusdirman_op_selected5>" . __("Multiple Select List","WPBDM") . "</option>";
		$html .= "<option value=\"4\" wpbusdirman_op_selected4>" . __("Radio Button","WPBDM") . "</option>";
		$html .= "<option value=\"6\" $wpbusdirman_op_selected6>" . __("Checkbox","WPBDM") . "</option>";
		$html .= "<option value=\"3\" $wpbusdirman_op_selected3>" . __("Textarea","WPBDM") . "</option>";
		$html .= "</select><p>" . __("Field Options","WPBDM") . " (" . __("for drop down lists, radio buttons, checkboxes ","WPBDM") . ") (" . __("separate by commas","WPBDM") . ")<br />" . __("**Do not fill in options for the Post category associated field","WPBDM") . "<input type=\"text\" name=\"wpbusdirman_field_options\" style=\"width:90%;\" value=\"" . get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_fieldtoedit) . "\">";
		$html .= "<p>" . __("Associate Field With","WPBDM") . " <select name=\"wpbusdirman_field_association\">";
		$html .= "<option value=\"\">" . __("Select Option","WPBDM") . "</option>";
		$html .= "<option value=\"title\" $wpbusdirman_associationselected1>" . __("Post Title","WPBDM") . "</option>";
		$html .= "<option value=\"description\" $wpbusdirman_associationselected2>" . __("Post Content","WPBDM") . "</option>";
		$html .= "<option value=\"category\" $wpbusdirman_associationselected3>" . __("Post Category","WPBDM") . "</option>";
		$html .= "<option value=\"excerpt\" $wpbusdirman_associationselected4>" . __("Post Excerpt","WPBDM") . "</option>";
		$html .= "<option value=\"meta\" $wpbusdirman_associationselected5>" . __("Post Meta","WPBDM") . "</option>";
		$html .= "<option value=\"tags\" $wpbusdirman_associationselected6>" . __("Post Tags","WPBDM") . "</option>";
		$html .= "</select></p><p>" . __("Validate Against","WPBDM") . " <select name=\"wpbusdirman_field_validation\">";
		$html .= "<option value=\"\">" . __("Select Option","WPBDM") . "</option>";
		$html .= "<option value=\"email\" $wpbusdirman_validation1>" . __("Email Format","WPBDM") . "</option>";
		$html .= "<option value=\"url\" $wpbusdirman_validation2>" . __("URL format","WPBDM") . "</option>";
		$html .= "<option value=\"missing\" $wpbusdirman_validation3>" . __("Missing Value","WPBDM") . "</option>";
		$html .= "<option value=\"numericwhole\" $wpbusdirman_validation4>" . __("Whole Number Value","WPBDM") . "</option>";
		$html .= "<option value=\"numericdeci\" $wpbusdirman_validation5>" . __("Decimal Value","WPBDM") . "</option>";
		$html .= "<option value=\"date\" $wpbusdirman_validation6>" . __("Date Format","WPBDM") . "</option>";
		$html .= "</select></p><p>" . __("Is Field Required?","WPBDM") . " <select name=\"wpbusdirman_field_required\">";
		$html .= "<option value=\"\">" . __("Select Option","WPBDM") . "</option>";
		$html .= "<option value=\"yes\" $wpbusdirman_required_selected1>" . __("Yes","WPBDM") . "</option>";
		$html .= "<option value=\"no\" $wpbusdirman_required_selected2>" . __("No","WPBDM") . "</option>";
		$html .= "</select></p><p>" . __("Show this value in post excerpt?","WPBDM") . " <select name=\"wpbusdirman_field_showinexcerpt\">";
		$html .= "<option value=\"\">" . __("Select Option","WPBDM") . "</option>";
		$html .= "<option value=\"yes\" $wpbusdirman_showinexcerpt_selected1>" . __("Yes","WPBDM") . "</option>";
		$html .= "<option value=\"no\" $wpbusdirman_showinexcerpt_selected2>" . __("No","WPBDM") . "</option>";
		$html .= "</select></p><p>" . __("Hide this field from public viewing?","WPBDM") . " <select name=\"wpbusdirman_field_hide\">";
		$html .= "<option value=\"no\" $wpbusdirman_hide_selected1>" . __("No","WPBDM") . "</option>";
		$html .= "<option value=\"yes\" $wpbusdirman_hide_selected2>" . __("Yes","WPBDM") . "</option>";
		$html .= "</select></p>";
		$html .= "<input type=\"hidden\" name=\"action\" value=\"updateoptions\" />";
		$html .= "<input type=\"hidden\" name=\"whichtext\" value=\"$wpbusdirman_fieldtoedit\" />";
		$html .= "<input name=\"updateoptions\" type=\"submit\" value=\"";
		if(isset($wpbusdirman_fieldtoedit) && !empty($wpbusdirman_fieldtoedit))
		{
			$html .= __("Update Field","WPBDM");
		}
		else
		{
			$html .= __("Add New Field","WPBDM");
		}
		$html .= "\" /></form>";

	}
	elseif($wpbusdirmanaction == 'post')
	{
		$html .= apply_filters('wpbdm_process-form-post', null);
	}
	else
	{
		$html .=wpbusdirman_fields_list();
	}
	$html .= wpbdp_admin_footer();

	echo $html;
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
    		default:
    			$this->fieldsTable();
    			break;
    	}
    }

    public static function admin_menu_cb() {
    	$instance = new WPBDP_FormFieldsAdmin();
    	$instance->dispatch();
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

}