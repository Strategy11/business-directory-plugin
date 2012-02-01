<?php
function wpbusdirman_retrieveoptions($whichoptions)
{
	$wpbusdirman_field_vals=array();
	global $table_prefix;

	$query="SELECT count(*) FROM {$table_prefix}options WHERE option_name LIKE '%".$whichoptions."%'";
	if (!($res=mysql_query($query)))
	{
		die(__(' Failure retrieving table data ['.$query.'].'));
	}
	while ($rsrow=mysql_fetch_row($res))
	{
		list($wpbusdirman_count_label)=$rsrow;
	}
	for ($i=0;$i<($wpbusdirman_count_label);$i++)
	{
		$wpbusdirman_field_vals[]=($i+1);
	}

	return $wpbusdirman_field_vals;
}


function get_wpbusdirman_config_options()
{
	$mywpbusdirman_config_options=array();
	global $wpbusdirmanconfigoptionsprefix;

	$pstandwpbusdirman_config_options=get_option($wpbusdirmanconfigoptionsprefix.'_settings_config');

	if(isset($pstandwpbusdirman_config_options) && !empty($pstandwpbusdirman_config_options))
	{
		foreach ($pstandwpbusdirman_config_options as $pstandoption)
		{
			if(isset($pstandoption['id']) && !empty($pstandoption['id']))
			{
				$mywpbusdirman_config_options[$pstandoption['id']]=$pstandoption['std'];
			}

		}
	}

	return $mywpbusdirman_config_options;
}

function wpbusdirman_config_check_for_wpbusdirman_config_options()
{
	global $wpbusdirmanconfigoptionsprefix,$def_wpbusdirman_config_options,$poststatusoptions,$yesnooptions,$categoryorderoptions,$categorysortoptions;
	$wpbusdirmanconfigoptions=$wpbusdirmanconfigoptionsprefix.'_settings_config';
	$mysavedthemewpbusdirman_config_options=get_option($wpbusdirmanconfigoptions);

		$wpbusdirman_config_options = $mysavedthemewpbusdirman_config_options;

		if (!isset($wpbusdirman_config_options) || empty($wpbusdirman_config_options) || !is_array($wpbusdirman_config_options))
		{
			$wpbusdirman_config_options = $def_wpbusdirman_config_options;

			if($wpbusdirman_config_options)
			{
				foreach ($wpbusdirman_config_options as $optionvalue)
				{
					if(!isset($optionvalue['id']) || empty($optionvalue['id']))
					{
						$optionvalue['id']='';
					}
					if(!isset($optionvalue['wpbusdirman_config_options']) || empty($optionvalue['wpbusdirman_config_options']))
					{
						$optionvalue['wpbusdirman_config_options']='';
					}
					if(!isset($optionvalue['std']) || empty($optionvalue['std']))
					{
						$optionvalue['std']='';
					}

						$setmywpbusdirman_config_options[]=array("name" => $optionvalue['name'],
						"id" => $optionvalue['id'],
						"std" => $optionvalue['std'],
						"type" => $optionvalue['type'],
						"options" => $optionvalue['options']);

				}
			}

			update_option($wpbusdirmanconfigoptions,$setmywpbusdirman_config_options);
		}
}

function wpbusdirman_config_reconcile_options()
{
	global $wpbusdirmanconfigoptionsprefix,$def_wpbusdirman_config_options,$poststatusoptions,$yesnooptions,$categoryorderoptions,$categorysortoptions;
	$wpbusdirmanconfigoptions=$wpbusdirmanconfigoptionsprefix.'_settings_config';
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

			$setmywpbusdirman_config_options=array();

				if($def_wpbusdirman_config_options)
				{
					foreach ($def_wpbusdirman_config_options as $optionvalue)
					{

						if(!isset($optionvalue['id']) || empty($optionvalue['id']))
						{
							$optionvalue['id']='';
						}
						if(!isset($optionvalue['wpbusdirman_config_options']) || empty($optionvalue['wpbusdirman_config_options']))
						{
							$optionvalue['wpbusdirman_config_options']='';
						}
						if(!isset($optionvalue['name']) || empty($optionvalue['name']))
						{
							$optionvalue['name']='';
						}
						if(!isset($optionvalue['std']) || empty($optionvalue['std']))
						{
							$optionvalue['std']='';
						}
						if(!isset($optionvalue['options']) || empty($optionvalue['options']))
						{
							$optionvalue['options']='';
						}


						if(isset($wpbusdirman_config_options[$optionvalue['id']]) && !empty($wpbusdirman_config_options[$optionvalue['id']]))
						{
							$savedoptionvalue=$wpbusdirman_config_options[$optionvalue['id']];
						}
						elseif(isset($optionvalue['std']) && !empty($optionvalue['std']))
						{
							$savedoptionvalue=$optionvalue['std'];
						}
						else
						{
							$savedoptionvalue='';
						}
						$setmywpbusdirman_config_options[]=array("name" => $optionvalue['name'],
						"id" => $optionvalue['id'],
						"std" => $savedoptionvalue,
						"type" => $optionvalue['type'],
						"options" => $optionvalue['options']);
					}
				}

				update_option($wpbusdirmanconfigoptions,$setmywpbusdirman_config_options);

}


/* Manage Options begin */
function wpbusdirman_config_admin()
{
	global $wpbdmposttype,$wpbdmposttypecategory,$wpbdmposttypetags,$wpbusdirmanconfigoptionsprefix, $def_wpbusdirman_config_options,$poststatusoptions,$yesnooptions,$categoryorderoptions,$categorysortoptions;

		/* Options array begin */
		global $wpbusdirmanconfigoptionsprefix;

		// Options array
		$poststatusoptions=array("pending","publish");
		$yesnooptions=array("yes","no");
		$myloginurl=get_option('siteurl').'/wp-login.php?action=login';
		$myregistrationurl=get_option('siteurl').'/wp-login.php?action=register';
		$categoryorderoptions=array('name','ID','slug','count','term_group');
		$categorysortoptions=array('ASC','DESC');
		$drafttrashoptions=array("draft","trash");
		$listingsorderoptions=array('date','title','id','author','modified');
		$listingssortorderoptions=array('ASC','DESC');



		$def_wpbusdirman_config_options = array (

		array("name" => "Miscellaneous settings",
		"type" => "titles"),

		array("name" => "Listing Duration for no-fee sites (measured in days)?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_18",
		"std" => "365",
		"type" => "text"),

		array("name" => "Hide all buy plugin module buttons?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_25",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Hide tips for use and other information?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_26",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Include listing contact form on listing pages?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_27",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Include comment form on listing pages?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_36",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Give credit to plugin author?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_34",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Turn on listing renewal option?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_38",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Use default picture for listings with no picture?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_39",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Show listings under categories on main page?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_44",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Override email Blocking?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_45",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Status of listings upon uninstalling plugin",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_46",
		"std" => "draft",
		"type" => "select",
		"options" => $drafttrashoptions),

		array("name" => "Status of deleted listings",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_47",
		"std" => "draft",
		"type" => "select",
		"options" => $drafttrashoptions),

		array("name" => "Login/Registration Settings",
		"type" => "titles"),

		array("name" => "Require login?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_3",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Login URL?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_4",
		"std" => "$myloginurl",
		"type" => "text"),

		array("name" => "Registration URL?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_5",
		"std" => "$myloginurl",
		"type" => "text"),

		array("name" => "Post/Category Settings",
		"type" => "titles"),

		array("name" => "Default new post status",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_1",
		"std" => "pending",
		"type" => "select",
		"options" => $poststatusoptions),

		array("name" => "Edit post status",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_19",
		"std" => "publish",
		"type" => "select",
		"options" => $poststatusoptions),

		array("name" => "Order Categories List By",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_7",
		"std" => "name",
		"type" => "select",
		"options" => $categoryorderoptions),

		array("name" => "Sort order for categories",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_8",
		"std" => "ASC",
		"type" => "select",
		"options" => $categorysortoptions),

		array("name" => "Show category post count?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_9",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Hide Empty Categories?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_10",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Show only parent categories category list?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_48",
		"std" => "no",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Order Directory Listings By (Featured Listings will always appear first regardless of this setting)",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_52",
		"std" => "name",
		"type" => "select",
		"options" => $listingsorderoptions),

		array("name" => "Sort Directory Listings By (ASC for ascending order A-Z, DESC for descending order Z - A)",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_53",
		"std" => "name",
		"type" => "select",
		"options" => $listingssortorderoptions),


		array("name" => "Image Settings",
		"type" => "titles"),

		array("name" => "Allow image?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_6",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Number of free images?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_2",
		"std" => "2",
		"type" => "text"),

		array("name" => "Show Thumbnail on main listings page?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_11",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Max Image File Size?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_13",
		"std" => "100000",
		"type" => "text"),

		array("name" => "Minimum Image File Size?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_14",
		"std" => "300",
		"type" => "text"),

		array("name" => "Max image width?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_15",
		"std" => "500",
		"type" => "text"),

		array("name" => "Max image height?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_16",
		"std" => "500",
		"type" => "text"),

		array("name" => "Thumbnail Width?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_17",
		"std" => "120",
		"type" => "text"),


		array("name" => "General Payment Settings",
		"type" => "titles"),

		array("name" => "Currency Code",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_20",
		"std" => "USD",
		"type" => "text"),

		array("name" => "Currency Symbol",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_12",
		"std" => "$",
		"type" => "text"),

		array("name" => "Turn On Payments?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_21",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Put payment gateways in test mode?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_22",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Thank you for payment message",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_37",
		"std" => "Thank you for your payment. Your payment is being verified and your listing reviewed. The verification and review process could take up to 48 hours.",
		"type" => "text"),

		array("name" => "Featured(Sticky) listing settings",
		"type" => "titles"),

		array("name" => "Offer Sticky Listings?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_31",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Sticky Listing Price(00.00)",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_32",
		"std" => "39.99",
		"type" => "text"),

		array("name" => "Sticky listing page description text",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_33",
		"std" => "You can upgrade your listing to featured status. Featured listings will always appear on top of regular listings.",
		"type" => "text"),


		array("name" => "Google Checkout Settings",
		"type" => "titles"),

		array("name" => "Google Checkout Merchant ID",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_23",
		"std" => "",
		"type" => "text"),

		array("name" => "Google Checkout Sandbox Seller ID",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_24",
		"std" => "",
		"type" => "text"),

		array("name" => "Hide Google Checkout?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_40",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "PayPal Gateway Settings (Will only work if paypal module installed)",
		"type" => "titles"),

		array("name" => "PayPal Business Email",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_35",
		"std" => "",
		"type" => "text"),

		array("name" => "Hide PayPal?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_41",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),


		array("name" => "2Checkout Gateway Settings (Will only work if 2checkout module installed)",
		"type" => "titles"),

		array("name" => "2Checkout Seller/Vendor ID",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_42",
		"std" => "",
		"type" => "text"),

		array("name" => "Hide 2Checkout?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_43",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "ReCaptcha Settings",
		"type" => "titles"),

		array("name" => "reCAPTCHA Public Key",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_28",
		"std" => "",
		"type" => "text"),

		array("name" => "reCAPTCHA Private Key",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_29",
		"std" => "",
		"type" => "text"),

		array("name" => "Turn on reCAPTCHA?",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_30",
		"std" => "yes",
		"type" => "select",
		"options" => $yesnooptions),

		array("name" => "Permalink Settings",
		"type" => "titles"),

		array("name" => "Directory Listings Slug",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_49",
		"std" => "$wpbdmposttype",
		"type" => "text"),

		array("name" => "Categories slug",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_50",
		"std" => "$wpbdmposttypecategory",
		"type" => "text"),

		array("name" => "Tags slug",
		"id" => $wpbusdirmanconfigoptionsprefix."_settings_config_51",
		"std" => "$wpbdmposttypetags",
		"type" => "text"),

		);

		/* Options array end */

	$html = '';

	$html .= wpbusdirman_config_reconcile_options();
	$wpbusdirmanconfigoptions=$wpbusdirmanconfigoptionsprefix.'_settings_config';
	$mysavedthemewpbusdirman_config_options=get_option($wpbusdirmanconfigoptions);
	$wpbusdirman_config_options = $mysavedthemewpbusdirman_config_options;
	if (!isset($wpbusdirman_config_options)
		|| empty($wpbusdirman_config_options)
		|| !is_array($wpbusdirman_config_options))
	{
		$wpbusdirman_config_options = $def_wpbusdirman_config_options;
		if($wpbusdirman_config_options)
		{
			foreach ($wpbusdirman_config_options as $optionvalue)
			{
				if(isset($optionvalue['id'])
					&& !empty($optionvalue['id']))
				{
					$savedoptionvalue=get_option($optionvalue['id']);
					if(!isset($savedoptionvalue)
						|| empty ($savedoptionvalue))
					{
						$savedoptionvalue=$optionvalue['std'];
					}
					$setmywpbusdirman_config_options[]=array("name" => $optionvalue['name'],
					"id" => $optionvalue['id'],
					"std" => $savedoptionvalue,
					"type" => $optionvalue['type'],
					"options" => $optionvalue['options']);
					delete_option($optionvalue['id']);
				}
			}
		}
		update_option($wpbusdirmanconfigoptions,$setmywpbusdirman_config_options);
	}
	if( isset($_REQUEST['action'])
		&& ( 'updatewpbusdirman_config_options' == $_REQUEST['action'] ))
	{
		$myoptionvalue='';
		if($wpbusdirman_config_options)
		{
			foreach ($wpbusdirman_config_options as $optionvalue)
			{
				if ((isset($optionvalue['id'])
					&& !empty($optionvalue['id']))
					&& ( isset( $_REQUEST[ $optionvalue['id'] ])))
				{
					$myoptionvalue = $_REQUEST[ $optionvalue['id'] ];
				}
				if(!isset($optionvalue['options']) || empty($optionvalue['options']))
				{
					$optionvalue['options']='';
				}
				if(!isset($optionvalue['id']) || empty($optionvalue['id']))
				{
					$optionvalue['id']='';
				}
				if(!isset($optionvalue['std']) || empty($optionvalue['std'] ))
				{
					$optionvalue['std']='';
				}
				$mywpbusdirman_config_options[]=array("name" => $optionvalue['name'],
				"id" => $optionvalue['id'],
				"std" => $myoptionvalue,
				"type" => $optionvalue['type'],
				"options" => $optionvalue['options']);
			}
		}
		update_option($wpbusdirmanconfigoptions,$mywpbusdirman_config_options);
		$wpbusdirman_config_optionsupdated=true;
	}
	else if( isset($_REQUEST['action']) && ( 'reset' == $_REQUEST['action'] ))
	{
		update_option($wpbusdirmanconfigoptions,$def_wpbusdirman_config_options);
		$wpbusdirman_config_optionsreset=true;
	}
	if( isset($_REQUEST['saved'])
		&& !empty( $_REQUEST['saved'] ))
	{
		$html .= '<div id="message" class="updated fade"><p><strong>'.$myasfwpname.' settings saved.</strong></p></div>';
	}
	if ( isset($_REQUEST['reset'])
		&& !empty( $_REQUEST['reset'] ))
	{
		$html .= '<div id="message" class="updated fade"><p><strong>'.$myasfwpname.' settings reset.</strong></p></div>';
	}
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_config_saved_options = get_option($wpbusdirmanconfigoptionsprefix.'_settings_config');
	if (!isset($wpbusdirman_config_saved_options) || empty($wpbusdirman_config_saved_options) || !is_array($wpbusdirman_config_saved_options))
	{
		$wpbusdirman_config_options = $def_wpbusdirman_config_options;
	}
	else
	{
		$wpbusdirman_config_options=$wpbusdirman_config_saved_options;
	}
	$html .= '<div class="wrap"><h2>' . __('WP Business Directory Main Settings','WPBDM') . '</h2><form method="post">';
	foreach ($wpbusdirman_config_options as $value)
	{
		if ($value['type'] == "text")
		{
			$html .= '<div style="float: left; width: 880px; background-color:#E4F2FD; border-left: 1px solid #C2D6E6; border-right: 1px solid #C2D6E6;  border-bottom: 1px solid #C2D6E6; padding: 10px;"><div style="width: 200px; float: left;">' . $value['name'] . '</div><div style="width: 680px; float: left;"><input name="' . $value['id'] . '" id="' . $value['id'] . '" style="width: 400px;" type="' . $value['type'] . '" value="';
			if ( isset($wpbusdirman_config_options[ $value['id'] ]) && $wpbusdirman_config_options[ $value['id'] ] != "")
			{
				$html .= stripslashes($wpbusdirman_config_options[ $value['id'] ]);
			}
			else
			{
				$html .= $value['std'];
			}
			$html .= '" /></div></div>';
		}
		elseif ($value['type'] == "text2")
		{
			$html .= '<div style="float: left; width: 880px; background-color:#E4F2FD; border-left: 1px solid #C2D6E6; border-right: 1px solid #C2D6E6;  border-bottom: 1px solid #C2D6E6; padding: 10px;"><div style="width: 200px; float: left;">' . $value['name'] . '</div><div style="width: 680px; float: left;"><textarea name="' . $value['id'] . '" id="' . $value['id'] . '" style="width: 400px; height: 200px;" type="' . $value['type'] . '">';
			if ( $wpbusdirman_config_options[ $value['id'] ] != "")
			{
				$html .= stripslashes($wpbusdirman_config_options[ $value['id'] ]);
			}
			else
			{
				$html .= $value['std'];
			}
			$html .= '</textarea></div></div>';
		}
		elseif ($value['type'] == "select")
		{
			$html .= '<div style="float: left; width: 880px; background-color:#E4F2FD; border-left: 1px solid #C2D6E6; border-right: 1px solid #C2D6E6;  border-bottom: 1px solid #C2D6E6; padding: 10px;"><div style="width: 200px; float: left;">' . $value['name'] . '</div><div style="width: 680px; float: left;"><select name="' . $value['id'] . '" id="' . $value['id'] . '" style="width: 400px;">';
			foreach ($value['options'] as $option)
			{
				$html .= '<option';
				if ( isset($wpbusdirman_config_options[ $value['id'] ]) && $wpbusdirman_config_options[ $value['id'] ] == $option)
				{
					$html .= ' selected="selected"';
				}
				elseif ($option == $value['std'])
				{
					$html .= ' selected="selected"';
				}
				$html .= '>' . $option . '</option>';
			}
			$html .= '</select></div></div>';
		}
		elseif ($value['type'] == "titles")
		{
			$html .= '<div style="float: left; width: 870px; padding: 15px; background-color:#2583AD; border: 1px solid #2583AD; color: #fff; font-size: 16px; font-weight: bold; margin-top: 25px;">' . $value['name'] . '</div>';
		}
	}
	$html .= '<div style="clear: both;"></div><p style="float: left;" class="submit"><input name="save" type="submit" value="Save changes" /><input type="hidden" name="action" value="updatewpbusdirman_config_options" /></p></form><form method="post"><p style="float: left;" class="submit"><input name="reset" type="submit" value="Reset" /><input type="hidden" name="action" value="reset" /></p></form>';

	echo $html;
}

/* Manage Options End */
?>