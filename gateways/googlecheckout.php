<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// googlecheckout.php
// A module for using google checkout to handle Business Directory Plugin payment processing
//
// Author: A Lewis
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


global $wpdb;

//if(!wpbusdirmanfindpage($shortcode='[WPBUSDIRMANGOOGLECHECKOUT]'))
//{
//	wpbusdirmanmakepage($wpbdmpagename='Google Checkout',$shortcode='[WPBUSDIRMANGOOGLECHECKOUT]');
//}


function wpbusdirman_googlecheckout_button($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirmanlistingcost)
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

	$wpbusdirmangooglecheckoutmerchantid=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_23'];
	$wpbusdirmangooglecheckoutmerchantidsandbox=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_24'];
	$wpbusdirman_gpidgooglecheckout=wpbusdirman_gpidgooglecheckout();
	$wpbusdirmanpermalink=get_permalink($wpbusdirman_gpidgooglecheckout);

	$wpbusdirman_gc_return_link_url="$wpbusdirmanpermalink";

	if($wpbusdirmanfeeoption == 32)
	{
		$wpbusdirman_payment_ad_description=__("Payment for upgrading to featured listing ","WPBDM");
	}
	else
	{
		$wpbusdirman_payment_ad_description=__("Payment for listing ","WPBDM");
	}
	$wpbusdirman_payment_ad_description.=get_the_title($wpbusdirmanlistingpostid);
	$wpbusdirman_payment_ad_description.=__(" with listing ID: ","WPBDM");
	$wpbusdirman_payment_ad_description.=$wpbusdirmanlistingpostid;

	$wpbusdirmangooglecheckoutbutton="";

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
	{
		$wpbdmgooglecheckouturl="https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/$wpbusdirmangooglecheckoutmerchantidsandbox";
	}
	else
	{
		$wpbdmgooglecheckouturl="https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/$wpbusdirmangooglecheckoutmerchantid";
	}

	$wpbusdirmangooglecheckoutbutton.="<form action=\"$wpbdmgooglecheckouturl\" id=\"BB_BuyButtonForm\" method=\"post\" name=\"BB_BuyButtonForm\" accept-charset=\"utf-8\">";

		if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
		{
			$thegooglebuttonurl="https://sandbox.google.com/checkout/buttons/buy.gif?merchant_id=$wpbusdirmangooglecheckoutmerchantidsandbox&w=117&h=48&style=white&variant=text";
		}
		else
		{
			$thegooglebuttonurl="https://checkout.google.com/buttons/buy.gif?merchant_id=$wpbusdirmangooglecheckoutmerchantid&w=117&h=48&style=white&variant=text";
		}

	if($wpbusdirmanfeeoption == 32)
	{
		$wpbusdirmanfeelabel=__("Upgrade Listing to Featured","WPBDM");
		$wpbusdirmanfeeamount=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_32'];
		$wpbusdirmanfeeamount=number_format($wpbusdirmanfeeamount, 2, '.', '');
	}
	else
	{
		$lastfeeoption=end($wpbusdirmanfeeoption);
		$wpbusdirmanfeelabel="";

		foreach($wpbusdirmanfeeoption as $feeoption)
		{

			$wpbusdirmanfeelabel.=get_option('wpbusdirman_settings_fees_label_'.$feeoption);
			if(!($feeoption == $lastfeeoption)){$wpbusdirmanfeelabel.="|";}
		}

		//$wpbusdirmanfeeamount=get_option('wpbusdirman_settings_fees_amount_'.$wpbusdirmanfeeoption);

		$wpbusdirmanfeeamount=$wpbusdirmanlistingcost;
		$wpbusdirmanfeeamount=number_format($wpbusdirmanfeeamount, 2, '.', '');
	}
		$wpbusdirmancurrencycode=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_20'];
		//$wpbusdirmansavedfeecategories=get_option('wpbusdirman_settings_fees_categories_'.$wpbusdirmanfeeoption);




	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"item_name_1\" value=\"$wpbusdirmanfeelabel\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"item_description_1\" value=\"$wpbusdirman_payment_ad_description\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"item_price_1\" value=\"$wpbusdirmanfeeamount\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"item_currency_1\" value=\"$wpbusdirmancurrencycode\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"item_quantity_1\" value=\"1\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"shopping-cart.items.item-1.digital-content.display-disposition\" value=\"OPTIMISTIC\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"shopping-cart.items.item-1.digital-content.description\" value=\"";
	$wpbusdirmangooglecheckoutbutton.=__("Your listing has not been fully submitted yet. To complete the process you need to click the link below and enter your license key upon request.", "WPBDM");
	$wpbusdirmangooglecheckoutbutton.="\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\" name=\"shopping-cart.items.item-1.digital-content.key\" value=\"$wpbusdirmanlistingpostid\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"hidden\"  name=\"shopping-cart.items.item-1.digital-content.url\" value=\"$wpbusdirman_gc_return_link_url\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input name=\"_charset_\" type=\"hidden\" value=\"utf-8\"/>";
	$wpbusdirmangooglecheckoutbutton.="<input type=\"image\" src=\"$thegooglebuttonurl\" alt=\"";
	$wpbusdirmangooglecheckoutbutton.=__("Pay With Google Checkout","WPBDM");
	$wpbusdirmangooglecheckoutbutton.="\" /></form>";

	return $wpbusdirmangooglecheckoutbutton;
}

function wpbusdirman_do_googlecheckout()
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

	$wpbusdirman_gpidgooglecheckout=wpbusdirman_gpidgooglecheckout();
	$wpbusdirmanpermalink=get_permalink($wpbusdirman_gpidgooglecheckout);

	$wpbusdirman_gc_errors=array();
	if(isset($_REQUEST['action']) && !empty($_REQUEST['action']))
	{
		$wpbusdirmanaction=$_REQUEST['action'];
	}
	else
	{
		$wpbusdirmanaction='';
	}

	if($wpbusdirmanaction == 'updatepaymentstatus')
	{
		$error=false;

		if(isset($_REQUEST['wpbdmlistingid']) && !empty($_REQUEST['wpbdmlistingid']))
		{
			$wpbusdirmanlistingpostid=$_REQUEST['wpbdmlistingid'];

			$wpbdmpaymentstatus=get_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentstatus", true);
			$wpbdmpaymentgateway=get_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentgateway", true);
			$wpbdmlistingsticky=get_post_meta($wpbusdirmanlistingpostid, "_wpbdp_sticky", true);

			if(isset($wpbdmpaymentstatus)	&& !empty ($wpbdmpaymentstatus)	&& isset($wpbdmpaymentgateway) && !empty($wpbdmpaymentgateway) )
			{
				if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
				{
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_sticky", "pending", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_sticky", "pending");
					$stickythankyou=wpbudirman_sticky_payment_thankyou();
					echo $stickythankyou;
				}
			}
			elseif( (!isset($wpbdmpaymentstatus) || !empty ($wpbdmpaymentstatus)) && ( !isset($wpbdmpaymentgateway) || empty($wpbdmpaymentgateway)) )
			{
				if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
				{
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_sticky", "pending", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_sticky", "pending");
					$stickythankyou=wpbudirman_sticky_payment_thankyou();
					echo $stickythankyou;
				}
			}
			else
			{
				if(!isset($wpbdmpaymentstatus) || empty($wpbdmpaymentstatus))
				{
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentstatus", "pending", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentstatus", "pending");
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_buyerfirstname", "Unknown", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_buyerfirstname", $first_name);
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_buyerlastname", "Unknown", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_buyerlastname", $last_name);
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentgateway", "Google Checkout", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_paymentgateway", "PayPal");
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_payeremail", "Unknown", true) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_payeremail", "Unknown");


					$paymentthankyou=wpbusdirman_payment_thankyou();
					print($paymentthankyou);

				}

			}
		}
		else
		{
			$error=true;
			$wpbusdirman_gc_errors[]="<p class=\"wpbusdirmanerroralert\">";
			$wpbusdirman_gc_errors[]=__("Please enter your license key.","WPBDM");
			$wpbusdirman_gc_errors[]="</p>";

			wpbusdirman_do_googlecheckout_form($wpbusdirmanlistingpostid='',$wpbusdirman_gc_errors,$wpbusdirmanpermalink);
		}


	}
	else
	{
		wpbusdirman_do_googlecheckout_form($wpbusdirmanlistingpostid='',$wpbusdirman_gc_errors='',$wpbusdirmanpermalink);
	}

}

function wpbusdirman_do_googlecheckout_form($wpbdmlistingid_gc,$wpbusdirman_gc_errors,$wpbusdirmanpermalink)
{
	if(isset($wpbusdirman_gc_errors) && !empty($wpbusdirman_gc_errors))
	{
		echo "<p>";
		foreach($wpbusdirman_gc_errors as $wpbusdirman_gc_error)
		{
			echo $wpbusdirman_gc_error;
		}
			echo "</p>";
	}
		echo "<p>";
		_e("Please enter your license key to proceed","WPBDM");
		echo "</p>";
		echo "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
		echo "<input type=\"text\" name=\"wpbdmlistingid\" value=\"$wpbdmlistingid_gc\"/>";
		echo "<input type=\"hidden\" name=\"action\" value=\"updatepaymentstatus\"/>";
		echo "<input type=\"submit\" class=\"insubmitbutton\" value=\"";
		_e("Proceed","WPBDM");
		echo "\"/></form>";
}

function wpbusdirman_gpidgooglecheckout(){
	global $wpdb;
	$wpbusdirman_pageid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[WPBUSDIRMANGOOGLECHECKOUT]%' AND post_status='publish' AND post_type='page'");
	return $wpbusdirman_pageid;
}

?>