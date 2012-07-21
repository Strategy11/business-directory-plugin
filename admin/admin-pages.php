<?php
function wpbdp_admin_sidebar() {
    return wpbdp_render_page(WPBDP_PATH . 'admin/templates/sidebar.tpl.php');
}

function wpbdp_admin_header($title_=null, $id=null, $h2items=array()) {
	global $title;

	if (!$title_) $title_ = $title;
    return wpbdp_render_page(WPBDP_PATH . 'admin/templates/header.tpl.php', array('page_title' => $title_, 'page_id' => $id, 'h2items' => $h2items));
}


function wpbdp_admin_footer()
{
	$html = '<!--</div>--></div><br class="clear" /></div>';
	return $html;
}

/* Admin home screen setup begin */
function wpbusdirman_home_screen()
{
	$listyle="style=\"width:auto;float:left;margin-right:5px;\"";
	$listyle2="style=\"width:200px;float:left;margin-right:5px;\"";
	$html = '';

	$html .= wpbdp_admin_header();
	$wpbusdirman_myterms = get_terms(wpbdp_categories_taxonomy(), 'orderby=name&hide_empty=0');
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
			$wpbusdirman_tlincat=&get_term( $wpbusdirman_postcatitem, wpbdp_categories_taxonomy(), '', '' );
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
	
	if( !wpbdp_get_option('googlecheckout') && !wpbdp_get_option('paypal') && wpbdp_get_option('payments-on') ) {
							$html .= "<p style=\"padding:10px;background:#ff0000;color:#ffffff;font-weight:bold;\">";
							$html.=__("You have payments turned on but all your gateways are set to hidden. Your system will run as if payments are turned off until you fix the problem. To fix go to <i>Manage options > Payment</i> and unhide at least 1 payment gateway, or if it is your intention not to charge a payment fee set payments to off instead of on.","WPBDM");
							$html.="</p>";
	}
	$html .= "<ul><li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdp_admin_settings\">" . __("Configure/Manage Options","WPBDM") . "</a></li>";
	$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdp_admin_fees\">" . __("Setup/Manage Fees","WPBDM") . "</a></li>";
	$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"?page=wpbdp_admin_formfields\">" . __("Setup/Manage Form Fields","WPBDM") . "</a></li>";
	if(wpbdp_get_option('featured-on'))
	{
		$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"" . admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=pendingupgrade', WPBDP_Plugin::POST_TYPE)) . "\">" . __("Featured Listings Pending Upgrade","WPBDM") . "</a></li>";
	}
	if(wpbdp_get_option('payments-on'))
	{
		$html .= "<li class=\"button\" $listyle><a style=\"text-decoration:none;\" href=\"" . admin_url(sprintf('edit.php?post_type=%s&wpbdmfilter=unpaid', WPBDP_Plugin::POST_TYPE)) . "\">" . __("Manage Paid Listings","WPBDM") . "</a></li>";
	}
	$html .= "</ul><br /><div style=\"clear:both;\"></div><ul>";
	$html .= "<li $listyle2>" . __("Listings in directory","WPBDM") . ": (<b>$wpbusdirman_totallistings</b>)</li>";
	$html .= "<li $listyle2>" . __("Categories In Directory","WPBDM") . ": (<b>$wpbusdirman_totalcatsindir</b>)</li></ul><div style=\"clear:both;\"></div>";
	if(!wpbdp_get_option('hide-tips'))
	{
		$html .= "<h4>" . __("Tips for Use and other information","WPBDM") . "</h4>";
		$html .= "<ol>";
		if(wpbdp_get_option('payments-on'))
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