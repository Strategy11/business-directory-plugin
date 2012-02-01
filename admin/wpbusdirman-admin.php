<?php

function wpbusdirman_admin_head()
{
	$html = '';

	$html .= "<div class=\"wrap\"><div id=\"icon-edit-pages\" class=\"icon32\"><br></div><h2>" . WPBUSDIRMAN . "</h2><div id=\"dashboard-widgets-wrap\"><div class=\"postbox\" style=\"padding:20px;width:90%;\">";

	return $html;
}


function wpbusdirman_admin_foot()
{
	$html = '';

	$html .= "</div></div></div>";

	return $html;
}

/* Admin home screen setup begin */
function wpbusdirman_home_screen()
{
	global $wpbusdirman_db_version,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_haspaypalmodule,$wpbusdirman_hasgooglecheckoutmodule;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$listyle="style=\"width:auto;float:left;margin-right:5px;\"";
	$listyle2="style=\"width:200px;float:left;margin-right:5px;\"";
	$html = '';

	$html .= wpbusdirman_admin_head();
	$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');
	if($wpbusdirman_myterms)
	{
		foreach($wpbusdirman_myterms as $wpbusdirman_myterm)
		{
			$wpbusdirman_postcatitems[]=$wpbusdirman_myterm->term_id;
		}
	}
	if(!empty($wpbusdirman_postcatitems))
	{
		foreach($wpbusdirman_postcatitems as $wpbusdirman_postcatitem)
		{
			$wpbusdirman_tlincat=&get_term( $wpbusdirman_postcatitem, $wpbdmposttypecategory, '', '' );
			$wpbusdirman_totallistingsincat[]=$wpbusdirman_tlincat->count;
		}
		$wpbusdirman_totallistings=array_sum($wpbusdirman_totallistingsincat);
		$wpbusdirman_totalcatsindir=count($wpbusdirman_postcatitems);
	}
	else
	{
		$wpbusdirman_totallistings=0;
		$wpbusdirman_totalcatsindir=0;
	}
	$html .= "<h3 style=\"padding:10px;\">" . __("Options Menu","WPBDM") . "</h3><p>" . __("You are using version","WPBDM") . " <b>$wpbusdirman_db_version</b> </p>";
if( $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_40'] == "yes"
		&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_41'] == "yes"
			&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_43'] == "yes"
				&& $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes" )
						{
							$html .= "<p style=\"padding:10px;background:#ff0000;color:#ffffff;font-weight:bold;\">";
							$html.=__("You have payments turned on but all your gateways are set to hidden. Your system will run as if payments are turned off until you fix the problem. To fix go to Configure/Manage options and unhide at least 1 payment gateway, or if it is your intention not to charge a payment fee set payments to off instead of on.","WPBDM");
							$html.="</p>";
						}
	$html .= "<ul><li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c1\">" . __("Configure/Manage Options","WPBDM") . "</a></li>";
	$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c2\">" . __("Setup/Manage Fees","WPBDM") . "</a></li>";
	$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c3\">" . __("Setup/Manage Form Fields","WPBDM") . "</a></li>";
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_31'] == "yes")
	{
		$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c4\">" . __("Featured Listings Pending Upgrade","WPBDM") . "</a></li>";
	}
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes")
	{
		$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdman_c5\">" . __("Manage Paid Listings","WPBDM") . "</a></li>";
	}
	$html .= "</ul><br /><div style=\"clear:both;\"></div><ul>";
	$html .= "<li $listyle2>" . __("Listings in directory","WPBDM") . ": (<b>$wpbusdirman_totallistings</b>)</li>";
	$html .= "<li $listyle2>" . __("Categories In Directory","WPBDM") . ": (<b>$wpbusdirman_totalcatsindir</b>)</li></ul><div style=\"clear:both;\"></div>";
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_26'] == "yes")
	{
		$html .= "<h4>" . __("Tips for Use and other information","WPBDM") . "</h4>";
		$html .= "<ol>";
		if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes")
		{
			$html .= "<li>" . __("Leave default post status set to pending to avoid misuse","WPBDM") . "<br />" . __("Listing payment status is not automatically updated after payment has been made. For this reason it is best to leave the listing default post status set to pending so you can verify that a listing has been paid for before it gets publised.","WPBDM") . "</li>";
			$html .= "<li>" . __("Valid Merchant ID and sandbox seller ID required for Google checkout payment processing ","WPBDM") . "</li>";
		}
		$html .= "<li>" . __("The plugin uses it's own page template to display single posts and category listings. You can modify the templates to make them match your site by editing the template files in the posttemplates folder which you will find inside the plugin folder. ","WPBDM") . "</li>";
		$html .= "<li>" . __("To protect user privacy Email addresses are not displayed in listings. ","WPBDM") . "</li>";
		$html .= "<li>" . __("reCaptcha human verification is built into the plugin contact form but comes turned off by default. To use it you need to turn it on. You also need to have a recaptcha public and private key. To obtain these visit recaptcha.net then enter the keys into he related boxes from the manage options page. ","WPBDM") . "</li>";
		$html .= "<li>" . __("You can hide these tips by going to Configure/Manage Options and checking the box next to 'Hide tips for use and other information'","WPBDM") .  "</li></ol>";
	}
	$html .= wpbusdirman_admin_foot();

	echo $html;
}
/* Admin home screen setup end */

// Manage Fees
function wpbusdirman_opsconfig_fees()
{

	global $wpbusdirman_settings_config_label_21,$wpbusdirman_imagesurl,$wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule,$wpbusdirman_labeltext,$wpbusdirman_amounttext,$wpbusdirman_actiontext,$wpbusdirman_appliedtotext,$wpbusdirman_allcatstext,$wpbusdirman_daytext,$wpbusdirman_daystext,$wpbusdirman_durationtext,$wpbusdirman_imagestext,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_action='';
	$hidenolistingfeemsg='';
	$hasnomodules='';
	$html = '';

	$html .= wpbusdirman_admin_head();
	$html .= "<h3 style=\"padding:10px;\">" . __("Manage Fees","WPBDM") . "</h3><p>";

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == 'no')
	{
		$html .= "<p>" . __("Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to 'Turn on payments' under 'General Payment Settings'","WPBDM") . "</p>";
	}
	else
	{
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
			$html .= "<p><a href=\"http://businessdirectoryplugin.com/about/payment-gateway-modules/\">http://businessdirectoryplugin.com/about/payment-gateway-modules/</a></p>";
		}
		if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_25'] != "yes")
		{
			if($wpbusdirman_hastwocheckoutmodule != 1
				|| $wpbusdirman_haspaypalmodule != 1 )
			{
				$html .= '<div style="width:100%;padding:10px;">';
				if(!($wpbusdirman_haspaypalmodule == 1))
				{
					$html .= '<div style="float:left;width:30%;padding:10px;">' . __("You can buy the PayPal gateway module to add PayPal as a payment option for your users.","WPBDM") . '<span style="display:block;color:red;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;">' . __("$49.99","WPBDM") . '</span><a href="http://businessdirectoryplugin.com/store/paypal-gateway-module-for-wp-business-directory-manager-1-8/"><center><img src="http://businessdirectoryplugin.com/wp-content/uploads/2011/07/paypalgm.jpg" alt="PayPal Gateway Module for Business Directory Plugin 1.8+"/></center></a></div>';
				}
				if(!($wpbusdirman_hastwocheckoutmodule == 1))
				{
					$html .= '<div style="float:left;width:30%;padding:10px;">' . __("You can buy the 2Checkout gateway module to add 2Checkout as a payment option for your users.","WPBDM") . '<span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;">' . __("$49.99","WPBDM") . '</span><a href="http://businessdirectoryplugin.com/store/2checkout-gateway-module-for-wp-business-directory-manager-1-8/"><center><img src="http://businessdirectoryplugin.com/wp-content/uploads/2011/07/twocheckoutgm.jpg" alt="2Checkout Gateway Module for Business Directory Plugin 1.8+"/></center></a></div>';
				}
				if($wpbusdirman_hastwocheckoutmodule
					!= 1 && $wpbusdirman_haspaypalmodule != 1 )
				{
					$html .= '<div style="float:left;width:30%;padding:10px;"><span style="color:red;font-weight:bold;text-transform:uppercase;">' . __("Save $20","WPBDM") . '</span>' . __(" on your purchase of both the Paypal and the 2Checkout gateway modules","WPBDM") . '<span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;">' . __("$79.98","WPBDM") . '</span><a href="http://businessdirectoryplugin.com/store/paypal-2checkout-gateway-modules-for-wp-business-directory-manager-1-8/"><center><img src="http://businessdirectoryplugin.com/wp-content/uploads/2011/07/paypaltwocheckoutgm.jpg" alt="PayPal plus 2Checkout Gateway Modules for Business Directory Plugin 1.8+"/></center></a></div>';
				}
				$html .= '</div><div style="clear:both;"></div>';
			}
		}
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
	}
	$html .= wpbusdirman_admin_foot();

	echo $html;
}

function wpbusdirman_my_fee_cats()
{
	global $wpbdmposttypecategory;

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

?>