<?php
function wpbdp_admin_sidebar() {
    return wpbdp_render_page(WPBDP_PATH . 'admin/templates/sidebar.tpl.php');
}

function wpbdp_admin_header($title=null, $id=null) {
    return wpbdp_render_page(WPBDP_PATH . 'admin/templates/header.tpl.php', array('page_title' => $title, 'page_id' => $id));
}


function wpbdp_admin_footer()
{
	$html = '<!--</div>--></div><br class="clear" /></div>';
	return $html;
}

/* Admin home screen setup begin */
function wpbusdirman_home_screen()
{
	global $wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_haspaypalmodule,$wpbusdirman_hasgooglecheckoutmodule;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$listyle="style=\"width:auto;float:left;margin-right:5px;\"";
	$listyle2="style=\"width:200px;float:left;margin-right:5px;\"";
	$html = '';

	$html .= wpbdp_admin_header();
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
	$html .= "<h3 style=\"padding:10px;\">" . __("Options Menu","WPBDM") . "</h3><p>" . __("You are using version","WPBDM") . " <b>" . WPBDP_Plugin::VERSION . "</b> </p>";
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
	$html .= wpbdp_admin_footer();

	echo $html;
}
/* Admin home screen setup end */

// Manage Payments
function wpbusdirman_manage_paid()
{
	global $wpbdp;
	global $wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	$html .= wpbdp_admin_header();
	if( isset($_REQUEST['action']) && !empty($_REQUEST['action']) && ($_REQUEST['action'] == 'setaspaid'))
	{
		if(isset($_REQUEST['id']) && !empty($_REQUEST['id']))
		{
			$wpbdmposttosetaspaid=$_REQUEST['id'];
			update_post_meta($wpbdmposttosetaspaid, "_wpbdp_paymentstatus", "paid");
			$html .= "<p>" . __("The listing status has been set as paid.","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<p>" . __("No ID was provided. Please try again","WPBDM") . "</p>";
		}
	}
	if( isset($_REQUEST['action']) && !empty($_REQUEST['action']) && ($_REQUEST['action'] == 'setasnotpaid'))
	{
		if(isset($_REQUEST['id']) && !empty($_REQUEST['id']))
		{
			$wpbdmposttosetasnotpaid=$_REQUEST['id'];
			delete_post_meta($wpbdmposttosetasnotpaid, "_wpbdp_paymentstatus","pending");
			delete_post_meta($wpbdmposttosetasnotpaid, "_wpbdp_paymentstatus","refunded");
			delete_post_meta($wpbdmposttosetasnotpaid, "_wpbdp_paymentstatus","unknown");
			delete_post_meta($wpbdmposttosetasnotpaid, "_wpbdp_paymentstatus","cancelled");
			$html .= "<p>" . __("The listing status has been changed non-paying.","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<p>" . __("No ID was provided. Please try again","WPBDM") . "</p>";
		}
	}
	$html .= "<h3 style=\"padding:10px;\">" . __("Manage Paid Listings", "WPBDM") . "</h3>";
	$wpbusdirman_pending='';
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "no")
	{
		$html .= "<p>" . __("You are not currently charging any payment fees. To charge fees for listings check that option in the manage options page.","WPBDM") . "</p>";
	}
	else
	{
		global $wpbusdirman_valuetext, $wpbusdirman_labeltext,$wpbusdirman_actiontext,$wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbdmposttype;

		$wpbusdirman_catcat=get_posts('post_type='.$wpbdmposttype);

		if($wpbusdirman_catcat)
		{
			foreach($wpbusdirman_catcat as $wpbusdirman_cat)
			{
				$wpbusdirman_postsposts[]=$wpbusdirman_cat->ID;
			}
		}
		if($wpbusdirman_postsposts)
		{
			foreach($wpbusdirman_postsposts as $wpbusdirman_post)
			{
				$wpbdmisapaid=get_post_meta($wpbusdirman_post, "_wpbdp_paymentstatus",$single=true);
				if(isset($wpbdmisapaid) && ($wpbdmisapaid <> ''))
				{
					$wpbusdirman_paidlistings[]=$wpbusdirman_post;
				}
			}
		}
		if(empty($wpbusdirman_paidlistings))
		{
			$html .= "<p>" . __("Currently there are no paid listings","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<p style=\"float:right\"><a href=\"http://businessdirectoryplugin.com/wp-business-directory-manager/manage-paid-listings\">" . __("Get info on managing paid listings","WPBDM") . "</a></p>";
			$html .= "<table class=\"widefat\" cellspacing=\"0\"><thead><tr>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Title","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("ID","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Status","WPBDM") . "</th>";
			if($wpbusdirman_haspaypalmodule == 1)
			{
				$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Flag","WPBDM") . "</th>";
			}
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Gateway","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Buyer","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Payment Email","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></thead><tfoot><tr>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Title","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("ID","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Status","WPBDM") . "</th>";
			if($wpbusdirman_haspaypalmodule == 1)
			{
				$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Flag","WPBDM") . "</th>";
			}
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Gateway","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Buyer","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Payment Email","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></tfoot><tbody>";
			if($wpbusdirman_paidlistings)
			{
				foreach($wpbusdirman_paidlistings as $wpbusdirman_paidlistingsitem)
				{
					$bfn=get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_buyerfirstname", true);
					$bln=get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_buyerlastname", true);
					$pflagged=get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_paymentflag", true);
					$pstat=get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_paymentstatus", true);
					if(!isset($pflagged)
						|| empty($pflagged))
					{
						$pflagged="None";
					}
					$pemail=get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_payeremail", true);
					if(!isset($pemail)
						|| empty($pemail))
					{
						$pemail="Unavailable";
					}
					$html .= "<tr><td><a href=\"" . get_permalink($wpbusdirman_paidlistingsitem) . "\">".get_the_title($wpbusdirman_paidlistingsitem)."</a></td>";
					$html .= "<td>$wpbusdirman_paidlistingsitem</td>";
					$html .= "<td>".get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_paymentstatus", true)."</td>";
					if($wpbusdirman_haspaypalmodule == 1)
					{
						$html .= "<td>$pflagged</td>";
					}
					$html .= "<td>".get_post_meta($wpbusdirman_paidlistingsitem, "_wpbdp_paymentgateway", true)."</td>";
					$html .= "<td>$bfn $bln</td>";
					$html .= "<td>$pemail</td>";

					$html .= "<td>" . __("Set as","WPBDM") . ": ";
					if(isset($pstat) && !empty($pstat) && ($pstat != 'paid'))
					{
						$html .= "<a href=\"?page=wpbdman_c5&action=setaspaid&id=$wpbusdirman_paidlistingsitem\">" . __("Paid","WPBDM") . "</a>";
					}
					$html .= "<a href=\"?page=wpbdman_c5&action=setasnotpaid&id=$wpbusdirman_paidlistingsitem\">" . __("Not paid","WPBDM") . "</a></td></tr>";
				}
			}
		}
		$html .= "</tbody></table>";
	}
	$html .= wpbdp_admin_footer();

	echo $html;
}

function wpbusdirman_featured_pending()
{
	global $wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	$html .= wpbdp_admin_header();
	if( isset($_REQUEST['action'])
		&& !empty($_REQUEST['action'])
		&& ($_REQUEST['action'] == 'upgradefeatured'))
	{
		if(isset($_REQUEST['id'])
			&& !empty($_REQUEST['id']))
		{
			$wpbdmposttofeature=$_REQUEST['id'];
			update_post_meta($wpbdmposttofeature, "_wpbdp_sticky", "approved");
			$html .= "<p>" . __("The listing has been upgraded.","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<p>" . __("No ID was provided. Please try again","WPBDM") . "</p>";
		}
	}
	if( isset($_REQUEST['action'])
		&& !empty($_REQUEST['action'])
		&& ($_REQUEST['action'] == 'cancelfeatured'))
	{
		if(isset($_REQUEST['id'])
			&& !empty($_REQUEST['id']))
		{
			$wpbdmposttofeature=$_REQUEST['id'];
			delete_post_meta($wpbdmposttofeature, "_wpbdp_sticky","pending");
			$html .= "<p>" . __("The listing has been downgraded.","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<p>" . __("No ID was provided. Please try again","WPBDM") . "</p>";
		}
	}
	$html .= "<h3 style=\"padding:10px;\">" . __("Manage Featured Listings pending manual upgrade", "WPBDM") . "</h3>";
	$wpbusdirman_pending='';
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_31'] == "no")
	{
		$html .= "<p>" . __("You are not currently allowing sticky (featured) listings. To allow sticky listings check that option in the manage options page under the Featured/Sticky listing settings.","WPBDM") . "</p>";
	}
	else
	{
		global $wpbusdirman_valuetext, $wpbusdirman_labeltext,$wpbusdirman_actiontext,$wpbdmposttypecategory,$wpbdmposttype;
		$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');

		$wpbusdirman_catcat=get_posts('post_type='.$wpbdmposttype);
		if($wpbusdirman_catcat)
		{
			foreach($wpbusdirman_catcat as $wpbusdirman_cat)
			{
				$wpbusdirman_postsposts[]=$wpbusdirman_cat->ID;
			}
		}
		if($wpbusdirman_postsposts)
		{
			foreach($wpbusdirman_postsposts as $wpbusdirman_post)
			{
				$wpbdmsticky=get_post_meta($wpbusdirman_post, "_wpbdp_sticky",$single=true);

				if($wpbdmsticky == 'pending')
				{
					$wpbusdirman_pendingfeatured[]=$wpbusdirman_post;
				}
			}
		}
		if(empty($wpbusdirman_pendingfeatured))
		{
			$html .= "<p>" . __("Currently there are no listings waiting to be upgraded to sticky(featured) status","WPBDM") . "</p>";
		}
		else
		{
			$html .= "<table class=\"widefat\" cellspacing=\"0\"><thead><tr>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Post Title","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Post ID","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></thead><tfoot><tr>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Post Title","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . __("Post ID","WPBDM") . "</th>";
			$html .= "<th scope=\"col\" class=\"manage-column\">" . $wpbusdirman_actiontext . "</th>";
			$html .= "</tr></tfoot><tbody>";
			if($wpbusdirman_pendingfeatured)
			{
				foreach($wpbusdirman_pendingfeatured as $wpbusdirman_pendingfeatureditem)
				{
					$html .= "<tr><td><a href=\"" . get_permalink($wpbusdirman_pendingfeatureditem) . "\">" . get_the_title($wpbusdirman_pendingfeatureditem)."</a></td>";
					$html .= "<td>$wpbusdirman_pendingfeatureditem</td><td><a href=\"?page=wpbdman_c4&action=upgradefeatured&id=$wpbusdirman_pendingfeatureditem\">" . __("Upgrade","WPBDM") . "</a> | <a href=\"?page=wpbdman_c4&action=cancelfeatured&id=$wpbusdirman_pendingfeatureditem\">" . __("Downgrade","WPBDM") . "</td></tr>";
				}
			}
		}
		$html .= "</tbody></table>";
	}
	$html .= wpbdp_admin_footer();

	echo $html;
}