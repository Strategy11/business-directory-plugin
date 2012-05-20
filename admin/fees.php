<?php
if (!class_exists('WP_List_Table'))
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class WPBDP_FeesTable extends WP_List_Table {

	public function __construct() {
		parent::__construct(array(
			'singular' => _x('fee', 'fees admin', 'WPBDM'),
			'plural' => _x('fees', 'fees admin', 'WPBDM'),
			'ajax' => false
		));
	}

    public function get_columns() {
    	return array();
  //       return array(
  //       	'order' => _x('Order', 'form-fields admin', 'WPBDM'),
  //       	'label' => _x('Label / Association', 'form-fields admin', 'WPBDM'),
  //       	'type' => _x('Type', 'form-fields admin', 'WPBDM'),
  //       	'validator' => _x('Validator', 'form-fields admin', 'WPBDM'),
  //       	'tags' => '',
		// );
    }

	public function prepare_items() {
		// $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

		// $api = wpbdp_formfields_api();
		// $this->items = $api->getFormFields();
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
// Manage Fees
function wpbusdirman_opsconfig_fees()
{

	global $wpbusdirman_settings_config_label_21,$wpbusdirman_imagesurl,$wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule,$wpbusdirman_labeltext,$wpbusdirman_amounttext,$wpbusdirman_actiontext,$wpbusdirman_appliedtotext,$wpbusdirman_allcatstext,$wpbusdirman_daytext,$wpbusdirman_daystext,$wpbusdirman_durationtext,$wpbusdirman_imagestext,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_action='';
	$hidenolistingfeemsg='';
	$hasnomodules='';
	$html = '';

	$html .= wpbdp_admin_header();

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == 'no')
	{
		$html .= "<p>" . __("Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to 'Turn on payments' under 'General Payment Settings'","WPBDM") . "</p>";
	}
	else
	{
		$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_settings_fees_label_');
		if(!empty($wpbusdirman_field_vals))
		{
			$wpbusdirman_field_vals_max=max($wpbusdirman_field_vals);
		}
		else
		{
			$wpbusdirman_field_vals_max='';
		}
		if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
		{
			$wpbusdirman_action=$_REQUEST['action'];
		}
		if(($wpbusdirman_action == 'addnewfee') || ($wpbusdirman_action == 'editfee') )
		{
			$hidenolistingfeemsg=1;
			if(isset($_REQUEST['feeid']) && !empty($_REQUEST['feeid']))
			{
				$wpbusdirman_feeid=$_REQUEST['feeid'];
			}
			if(isset($wpbusdirman_feeid) && !empty($wpbusdirman_feeid))
			{
				$wpbusdirmansavedfeelabel=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_label_'.$wpbusdirman_feeid);
				$wpbusdirmansavedfeeamount=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$wpbusdirman_feeid);
				$wpbusdirmansavedfeeincrement=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_increment_'.$wpbusdirman_feeid);
				$wpbusdirmansavedfeeimages=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$wpbusdirman_feeid);
				$wpbusdirmansavedfeecategories=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_categories_'.$wpbusdirman_feeid);
				$whichfeeid="<input type=\"hidden\" name=\"whichfeeid\" value=\"$wpbusdirman_feeid\" />";
				$wpbusdirmanfeeadoredit="<input type=\"hidden\" name=\"wpbusdirmanfeeadoredit\" value=\"edit\" />";
			}
			else
			{
				$wpbusdirmansavedfeelabel='';
				$wpbusdirmansavedfeeamount='';
				$wpbusdirmansavedfeeincrement='';
				$wpbusdirmansavedfeeimages='';
				$wpbusdirmansavedfeecategories='';
				$whichfeeid='';
				$wpbusdirmanfeeadoredit='';
			}
			$html .= "<form method=\"post\"><p>" . __("Fee Label","WPBDM") . "<br />";
			$html .= "<input type=\"text\" name=\"wpbusdirman_fees_label\" style=\"width:50%;\" value=\"$wpbusdirmansavedfeelabel\" />";
			$html .= "</p><p>" . __("Fee Amount","WPBDM") . "<br />";
			$html .= "<input type=\"text\" name=\"wpbusdirman_fees_amount\" style=\"width:10%;\" value=\"$wpbusdirmansavedfeeamount\" />";
			$html .= "</p><p>" . __("Listing Run in days","WPBDM") . "<br />";
			$html .= "<input type=\"text\" name=\"wpbusdirman_fees_increment\" value=\"$wpbusdirmansavedfeeincrement\" style=\"width:10%;\" />";
			$html .= "</p><p>" . __("Number of Images Allowed","WPBDM") . "<br />";
			$html .= "<input type=\"text\" name=\"wpbusdirman_fees_images\" value=\"$wpbusdirmansavedfeeimages\" style=\"width:10%;\" />";
			$html .= "</p><p>" . __("Apply to Category","WPBDM") . "<br />";
			$html .= "<select name=\"wpbusdirman_fees_categories[]\" multiple=\"multiple\" style=\"width:25%;height:80px;\">";
			$html .= "<option value=\"0\">$wpbusdirman_allcatstext</option>";
			$html .= wpbusdirman_my_fee_cats();
			$html .= "</select></p>" . $whichfeeid . $wpbusdirmanfeeadoredit;
			$html .= "<input type=\"hidden\" name=\"action\" value=\"updateoptions\" />";
			$html .= "<input name=\"updateoptions\" type=\"submit\" value=\"";
			if(isset($wpbusdirman_feeid) && !empty($wpbusdirman_feeid))
			{
				$html .= __("Update Fee","WPBDM");
			}
			else
			{
				$html .= __("Add Fee","WPBDM");
			}
			$html .= "\" /></form>";
		}
		elseif($wpbusdirman_action == 'deletefee')
		{
			if(isset($_REQUEST['feeid']) && !empty($_REQUEST['feeid']))
			{
				$whichfeeid=$_REQUEST['feeid'];
				delete_option( 'wpbusdirman_settings_fees_label_'.$whichfeeid);
				delete_option( 'wpbusdirman_settings_fees_amount_'.$whichfeeid);
				delete_option( 'wpbusdirman_settings_fees_increment_'.$whichfeeid);
				delete_option( 'wpbusdirman_settings_fees_images_'.$whichfeeid);
				delete_option( 'wpbusdirman_settings_fees_categories_'.$whichfeeid);
			}
			else
			{
				$html .= "<p>" . __("Unable to determine the ID of the fee you are trying to delete. Action terminated","WPBDM") . "</p>";
			}
		}
		elseif($wpbusdirman_action == 'updateoptions')
		{
			if(isset($_REQUEST['whichfeeid']) && !empty($_REQUEST['whichfeeid']))
			{
				$whichfeeid=$_REQUEST['whichfeeid'];
			}
			$hidenolistingfeemsg=1;
			if(isset($whichfeeid) && !empty($whichfeeid))
			{
				$wpbusdirman_add_update_option="update_option";
			}
			else
			{
				$whichfeeid=($wpbusdirman_field_vals_max+1);
				$wpbusdirman_add_update_option="add_option";
			}
			$wpbusdirman_fees_categories=$_REQUEST['wpbusdirman_fees_categories'];
			$wpbusdirman_last = end($wpbusdirman_fees_categories);
			$wpbusdirmanfeecatids='';
			if(in_array(0,$wpbusdirman_fees_categories))
			{
				$wpbusdirmanfeecatids.=0;
			}
			else
			{
				if (count($wpbusdirman_fees_categories) > 0)
				{
					// loop through the array
					for ($i=0;$i<count($wpbusdirman_fees_categories);$i++)
					{
						$wpbusdirmanfeecatids.="$wpbusdirman_fees_categories[$i]";
						if(!($wpbusdirman_fees_categories[$i] == $wpbusdirman_last))
						{
							$wpbusdirmanfeecatids.=",";
						}
					}
				}
			}
			$wpbusdirman_add_update_option( 'wpbusdirman_settings_fees_label_'.$whichfeeid, $_REQUEST['wpbusdirman_fees_label']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_settings_fees_amount_'.$whichfeeid, $_REQUEST['wpbusdirman_fees_amount']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_settings_fees_increment_'.$whichfeeid, $_REQUEST['wpbusdirman_fees_increment']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_settings_fees_images_'.$whichfeeid, $_REQUEST['wpbusdirman_fees_images']  );
			$wpbusdirman_add_update_option( 'wpbusdirman_settings_fees_categories_'.$whichfeeid, $wpbusdirmanfeecatids  );
			$html .= "<p>" . __("Task completed successfully","WPBDM") . "</p>";
			$html .= "<p><a href=\"?page=wpbdman_c2\">" . __("View current listing fees","WPBDM") . "</a></p>";
		}
		if(!empty($wpbusdirman_field_vals) && (!$hidenolistingfeemsg))
		{
			$html .= "<p><a href=\"?page=wpbdman_c2&action=addnewfee\">" . __("Add New Listing Fee","WPBDM") . "</a></p>";
			$html .= "<table class=\"widefat\" cellspacing=\"0\"><thead><tr><th scope=\"col\" class=\"manage-column\">";
			$html .= $wpbusdirman_labeltext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_amounttext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_durationtext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_imagestext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_appliedtotext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></thead><tfoot><tr>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_labeltext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_amounttext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_durationtext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_imagestext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_appliedtotext . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></tfoot><tbody>";

			if($wpbusdirman_field_vals)
			{
				foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
				{
					$html .= "<tr><td>".get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_label_'.$wpbusdirman_field_val)."</td>";
					$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$wpbusdirman_field_val)."</td>";
					$html .= "<td>".get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_increment_'.$wpbusdirman_field_val);
					if(get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_increment_'.$wpbusdirman_field_val) == 1)
					{
						$html .= " " . $wpbusdirman_daytext;
					}
					else
					{
						$html .= " " . $wpbusdirman_daystext;
					}
					$html .= "</td><td>".get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$wpbusdirman_field_val)."</td><td>";
					$wpbusdirman_sfeecats=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_categories_'.$wpbusdirman_field_val);
					$wpbusdirmansfeecats=explode(",",$wpbusdirman_sfeecats);
					$wpbusdirman_sfeecatitems=array();
					for ($i=0;isset($wpbusdirmansfeecats[$i]);++$i)
					{
						$wpbusdirman_sfeecatitems[]=$wpbusdirmansfeecats[$i];
					}
					if(in_array('0',$wpbusdirman_sfeecatitems))
					{
							$wpbusdirman_thecat_nameall=$wpbusdirman_allcatstext;
					}
					else
					{
						$wpbusdirman_thecat_nameall='';
					}
					if(!(strcasecmp($wpbusdirman_thecat_nameall, $wpbusdirman_allcatstext) == 0))
					{
						$wpbusdirman_myfeecats=array();
						if($wpbusdirman_sfeecatitems)
						{
							foreach ($wpbusdirman_sfeecatitems as $wpbusdirman_sfeecatitem)
							{
								$wpbusdirman_thecat_name=&get_term( $wpbusdirman_sfeecatitem, $wpbdmposttypecategory, '', '' );
								if(!empty($wpbusdirman_thecat_name))
								{
									$wpbusdirman_myfeecats[]=$wpbusdirman_thecat_name->name;
								}
							}
						}
						$wpbusdirman_myfeecat_names = implode(',',$wpbusdirman_myfeecats);
						$html .= $wpbusdirman_myfeecat_names;
					}
					else
					{
						$html .= " " . $wpbusdirman_thecat_nameall;
					}
					$html .= "</td><td><a href=\"?page=wpbdman_c2&action=editfee&feeid=$wpbusdirman_field_val\">" . __("Edit","WPBDM") . "</a> | <a href=\"?page=wpbdman_c2&action=deletefee&feeid=$wpbusdirman_field_val\">" . __("Delete","WPBDM") . "</a></td></tr>";
				}
			}
			$html .= "</tbody></table>";
		}
		else
		{
			if(!$hidenolistingfeemsg)
			{
				if(!$hasnomodules)
				{
					$html .= "<p>" . __("You do not have any listing fees setup yet.","WPBDM") . "</p><p><a href=\"?page=wpbdman_c2&action=addnewfee\">" . __("Add New Listing Fee","WPBDM") . "</a></p>";
				}
			}
		}
	
		$html .= '<hr />';
		$html .= "<p><b>" . __("Installed Payment Gateway Modules","WPBDM") . "</b><ul>";
		if($wpbusdirman_hasgooglecheckoutmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("Google Checkout","WPBDM") . "</li>";
		}
		if($wpbusdirman_haspaypalmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("PayPal","WPBDM") . "</li>";
		}
		if($wpbusdirman_hastwocheckoutmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("2Checkout","WPBDM") . "</li>";
		}
		$html .= "</ul></p>";
		if(!$wpbusdirman_haspaypalmodule && !$wpbusdirman_hastwocheckoutmodule && !$wpbusdirman_hasgooglecheckoutmodule)
		{
			$hasnomodules=1;
			$html .= "<p>" . __("It does not appear you have any of the payment gateway modules installed. You need to purchase a payment gateway module in order to charge a fee for listings. To purchase payment gateways use the buttons below or visit","WPBDM") . "</p>";
			$html .= "<p><a href=\"http://businessdirectoryplugin.com/premium-modules/\">http://businessdirectoryplugin.com/premium-modules/</a></p>";
		}

			if($wpbusdirman_hastwocheckoutmodule != 1
				|| $wpbusdirman_haspaypalmodule != 1 )
			{
				$html .= '<div style="width:100%;padding:10px;">';
				if(!($wpbusdirman_haspaypalmodule == 1))
				{
					$html .= '<div style="float:left;width:22%;padding:10px;">' . __("You can buy the PayPal gateway module to add PayPal as a payment option for your users.","WPBDM") . '<span style="display:block;color:red;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/" style="color:green;">' . __("$49.99","WPBDM") . '</a></span></div>';
				}
				if(!($wpbusdirman_hastwocheckoutmodule == 1))
				{
					$html .= '<div style="float:left;width:22%;padding:10px;">' . __("You can buy the 2Checkout gateway module to add 2Checkout as a payment option for your users.","WPBDM") . '<span style="display:block;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/" style="color:green;">' . __("$49.99","WPBDM") . '</a></span></div>';
				}
				if($wpbusdirman_hastwocheckoutmodule
					!= 1 && $wpbusdirman_haspaypalmodule != 1 )
				{
					$html .= '<div style="float:left;width:22%;padding:10px;"><span style="color:red;font-weight:bold;text-transform:uppercase;">' . __("Save $20","WPBDM") . '</span>' . __(" on your purchase of both the Paypal and the 2Checkout gateway modules","WPBDM") . '<br/><b>' . __('(Single Site License Combo Pack)', 'WPBDM') .'</b><span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack/" style="color:green;">' . __("$79.99","WPBDM") . '</a></span></div>';
					$html .= '<div style="float:left;width:22%;padding:10px;"><span style="color:red;font-weight:bold;text-transform:uppercase;">' . __("Save","WPBDM") . '</span>' . __(" on your purchase of both the Paypal and the 2Checkout gateway modules","WPBDM") . '<br/><b>' . __('(Multi Site License Combo Pack)', 'WPBDM') .'</b><span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack-multi-site/" style="color:green;">' . __("$119.00","WPBDM") . '</a></span></div>';
				}
				$html .= '</div><div style="clear:both;"></div>';
			}
	}

	$html .= wpbdp_admin_footer();

	echo $html;
}

function wpbusdirman_my_fee_cats()
{
	global $wpbdmposttypecategory, $wpbusdirmanconfigoptionsprefix;

	$wpbusdirman_my_fee_cats='';
	$wpbusdirman_feecatitems=array();

			$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');

			if($wpbusdirman_myterms)
			{
				foreach($wpbusdirman_myterms as $wpbusdirman_myterm)
				{
					$wpbusdirman_postcatitems[]=$wpbusdirman_myterm->term_id;
				}
			}

			$wpbusdirman_feecats=array();
			$wpbusdirman_feecats=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_categories');

			if(isset($wpbusdirman_feecats) && !empty($wpbusdirman_feecats))
			{
				$wpbusdirmanfeecats=explode(",",$wpbusdirman_feecats);


				for ($i=0;isset($wpbusdirmanfeecats[$i]);++$i)
				{
					$wpbusdirman_feecatitems[]=$wpbusdirmanfeecats[$i];
				}
			}

			if($wpbusdirman_postcatitems)
			{
				foreach($wpbusdirman_postcatitems as $wpbusdirman_postcatitem)
				{
					if(in_array($wpbusdirman_postcatitem,$wpbusdirman_feecatitems)){$wpbusdirman_theselcat="selected";}else{ $wpbusdirman_theselcat='';}

					$wpbusdirman_my_fee_cats.="<option value=\"";
					$wpbusdirman_my_fee_cats.=$wpbusdirman_postcatitem;
					$wpbusdirman_my_fee_cats.="\" $wpbusdirman_theselcat>";
					$wpbdmtname=&get_term( $wpbusdirman_postcatitem, $wpbdmposttypecategory, '', '' );

					$wpbusdirman_my_fee_cats.=$wpbdmtname->name;



					$wpbusdirman_my_fee_cats.="</option>";
				}
			}

	return	$wpbusdirman_my_fee_cats;
}

/* </old stuff> */


class WPBDP_FeesAdmin {

	public function __construct() {
		$this->admin = wpbdp()->admin;
	}

    public function dispatch() {
    	$action = wpbdp_getv($_REQUEST, 'action');
    	$_SERVER['REQUEST_URI'] = remove_query_arg(array('action', 'id'), $_SERVER['REQUEST_URI']);

    	switch ($action) {
    		// case 'addfield':
    		// case 'editfield':
    		// 	$this->processFieldForm();
    		// 	break;
    		// case 'deletefield':
    		// 	$this->deleteField();
    		// 	break;
    		// case 'fieldup':
    		// case 'fielddown':
    		// 	$this->api->reorderField($_REQUEST['id'], $action == 'fieldup' ? 1 : -1);
    		// 	$this->fieldsTable();
    		// 	break;
    		// case 'previewform':
    		// 	$this->previewForm();
    		// 	break;
    		default:
    			$this->feesTable();
    			break;
    	}
    }

    public static function admin_menu_cb() {
    	$instance = new WPBDP_FeesAdmin();
    	$instance->dispatch();
    }

    /* field list */
    private function feesTable() {
    	$table = new WPBDP_FeesTable();
    	$table->prepare_items();

        wpbdp_render_page(WPBDP_PATH . 'admin/templates/fees.tpl.php',
                          array('table' => $table),
                          true);    		    	
    }

	// private function processFieldForm() {
	// 	if (isset($_POST['field'])) {
	// 		$newfield = $_POST['field'];

	// 		if ($this->api->addorUpdateField($newfield, $errors)) {
	// 			$this->admin->messages[] = _x('Form fields updated.', 'form-fields admin', 'WPBDM');
	// 			return $this->fieldsTable();
	// 		} else {
	// 			$errmsg = '';
	// 			foreach ($errors as $err)
	// 				$errmsg .= sprintf('&#149; %s<br />', $err);
				
	// 			$this->admin->messages[] = array($errmsg, 'error');
	// 		}
	// 	}

	// 	$field = isset($_GET['id']) ? $this->api->getField($_GET['id']) : null;

	// 	wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields-addoredit.tpl.php',
	// 					  array('field' => $field),
	// 					  true);
	// }

	// private function deleteField() {
	// 	global $wpdb;

	// 	if (isset($_POST['doit'])) {
	// 		if (!$this->api->deleteField($_POST['id'], $errors)) {
	// 			$errmsg = '';
	// 			foreach ($errors as $err)
	// 				$errmsg .= sprintf('&#149; %s<br />', $err);
				
	// 			$this->admin->messages[] = array($errmsg, 'error');
	// 		} else {
	// 			$this->admin->messages[] = _x('Field deleted.', 'form-fields admin', 'WPBDM');
	// 		}

	// 		return $this->fieldsTable();
	// 	} else {
	// 		if ($field = $this->api->getField($_REQUEST['id'])) {
	// 			wpbdp_render_page(WPBDP_PATH . 'admin/templates/form-fields-confirm-delete.tpl.php',
	// 							  array('field' => $field),
	// 							  true);
	// 		}
	// 	}

	}