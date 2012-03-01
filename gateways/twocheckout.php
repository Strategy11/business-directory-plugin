<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// twocheckout.php
// A module for using twocheckout to handle Business Directory Plugin payment processing
//
// Author: A Lewis
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


global $wpdb;

function wpbusdirman_gpidtwocheckout(){
	global $wpdb;
	$wpbusdirman_pageid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[WPBUSDIRMANTWOCHECKOUT]%' AND post_status='publish'  AND post_type='page'");
	return $wpbusdirman_pageid;
}

function wpbusdirman_do_twocheckout()
{
	/*echo "<h1>Get Parameter/s:</h1>";
	echo "<pre>";
	if($_GET)
		print_r($_GET);
	else
		echo "There are no get parameters.";
	echo "</pre>";
	echo "<hr/>";
	echo "<h1>Post Parameter/s:</h1>";
	echo "<pre>";
	if($_POST)
		print_r($_POST);
	else
		echo "There are no post parameters.";
	echo "</pre>";
	die;*/

	$wpbusdirman_verified=false;


	$x_login='';
	$x_amount='';
	$wpbusdirmanlistingID='';
	$x_response_code='';
	$x_sid='';
	$payment_status='';
	$payer_email='';
	$first_name='';
	$last_name='';

	if(isset($_POST['x_login']) && !empty($_POST['x_login'])){$x_login	= $_POST['x_login'];}
	if(isset($_POST['x_amount']) && !empty($_POST['x_amount'])){$x_amount = $_POST['x_amount'];}
	if(isset($_POST['x_custom']) && !empty($_POST['x_custom'])){$wpbusdirmanlistingID = $_POST['x_custom'];}
	if(isset($_POST['x_response_code']) && !empty($_POST['x_response_code'])){$x_response_code = $_POST['x_response_code'];}
	if(isset($_POST['email']) && !empty($_POST['email'])){$payer_email = $_POST['email'];}
	if(isset($_POST['x_item_number']) && !empty($_POST['x_item_number'])){$x_item_number = $_POST['x_item_number'];}
	if(isset($_POST['x_trans_id']) && !empty($_POST['x_trans_id'])){$x_trans_id = $_POST['x_trans_id'];}
	if(isset($_POST['card_holder_name']) && !empty($_POST['card_holder_name'])){$card_holder_name = $_POST['card_holder_name'];}
	if(isset($_POST['country']) && !empty($_POST['country'])){$x_Country = $_POST['Country'];}
	if(isset($_POST['city']) && !empty($_POST['city'])){$x_City = $_POST['city'];}
	if(isset($_POST['state']) && !empty($_POST['state'])){$x_State = $_POST['state'];}
	if(isset($_POST['zip']) && !empty($_POST['zip'])){$x_Zip = $_POST['zip'];}
	if(isset($_POST['street_address']) && !empty($_POST['street_address'])){$x_Address = $_POST['street_address'];}
	if(isset($_POST['phone']) && !empty($_POST['phone'])){$x_Phone = $_POST['phone'];}
	if(isset($_POST['sid']) && !empty($_POST['sid'])){$x_sid=$_POST['sid'];}
	if(isset($_POST['demo']) && !empty($_POST['demo'])){$demo=$_POST['demo'];}
	if(isset($_POST['order_number']) && !empty($_POST['order_number'])){$x_order_number=$_POST['order_number'];}
	if(isset($_POST['credit_card_processed']) && !empty($_POST['credit_card_processed'])){$x_response_code=$_POST['credit_card_processed'];}

	if($x_response_code == 'Y'){$x_response_code=1;}


	$thechname=explode(" ",$card_holder_name);
	$first_name=$thechname[0];
	$last_name=$thechname[2];



		if($x_response_code == 1)
		{
			$payment_status="Completed";
		}
		else
		{
			$payment_status="Pending";
		}

		wpbusdirmanpayment_twocheckout($wpbusdirmanlistingID,$x_sid,$x_amount,$first_name,$last_name,$payment_status,$payer_email);


}

function wpbusdirman_twocheckout_button($wpbusdirmanlistingid,$wpbusdirmanfeeoption,$wpbusdirman_imagesurl)
{

	global $permalinkstructure,$wpbusdirmanconfigoptionsprefix,$wpbusdirman_imagesurl;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_gpidtwocheckout=wpbusdirman_gpidtwocheckout();
	$wpbusdirmantwocheckoutsid=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_42'];
	$wpbusdirmanpermalink=get_permalink($wpbusdirman_gpidtwocheckout);
	$wpbusdirman_2co_return_link_url="$wpbusdirmanpermalink";


	if($wpbusdirmanfeeoption == 32)
	{
		$wpbusdirman_payment_ad_description=__("Payment for upgrading to featured listing ","WPBDM");
	}
	else
	{
		$wpbusdirman_payment_ad_description=__("Payment for listing ","WPBDM");
	}
	$wpbusdirman_payment_ad_description.=get_the_title($wpbusdirmanlistingid);
	$wpbusdirman_payment_ad_description.=__(" with listing ID: ","WPBDM");
	$wpbusdirman_payment_ad_description.=$wpbusdirmanlistingid;

	$wpbusdirmantwocheckoutbutton="";

	$wpbusdirmantwocheckoutbutton.="<form action=\"https://www.2checkout.com/checkout/purchase\" method=\"post\">";

	if($wpbusdirmanfeeoption == 32)
	{
		$wpbusdirmansavedfeelabel=__("Upgrade Listing to Featured","WPBDM");
		$wpbusdirmansavedfeeamount=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_32'];
		$wpbusdirmansavedfeeamount=number_format($wpbusdirmansavedfeeamount, 2, '.', '');
	}
	else
	{
		$wpbusdirmansavedfeelabel=get_option('wpbusdirman_settings_fees_label_'.$wpbusdirmanfeeoption);
		$wpbusdirmansavedfeeamount=get_option('wpbusdirman_settings_fees_amount_'.$wpbusdirmanfeeoption);
		$wpbusdirmansavedfeeamount=number_format($wpbusdirmansavedfeeamount, 2, '.', '');
	}
		$wpbusdirmancurrencycode=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_20'];
		$wpbusdirmansavedfeecategories=get_option('wpbusdirman_settings_fees_categories_'.$wpbusdirmanfeeoption);

	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"sid\" value=\"$wpbusdirmantwocheckoutsid\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"id_type\" value=\"1\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"fixed\" value=\"Y\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"pay_method\" value=\"CC\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"x_Receipt_Link_URL\" value=\"$wpbusdirman_2co_return_link_url\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"x_invoice_num\" value=\"1\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"x_amount\" value=\"$wpbusdirmansavedfeeamount\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"total\" value=\"$wpbusdirmansavedfeeamount\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"c_prod\" value=\"item_$wpbusdirmanlistingid\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"cart_order_id\" value=\"$wpbusdirmansavedfeelabel\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"c_name\" value=\"$wpbusdirmansavedfeelabel\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"c_description\" value=\"$wpbusdirmansavedfeelabel\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"c_tangible\" value=\"N\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"x_item_number\" value=\"item_$wpbusdirmanlistingid\" />";
	$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"x_custom\" value=\"$wpbusdirmanlistingid\" />";
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
	{
		$wpbusdirmantwocheckoutbutton.="<input type=\"hidden\" name=\"demo\" value=\"Y\" />";
	}
	$wpbusdirmantwocheckoutbutton.="<input type=\"image\" src=\"$wpbusdirman_imagesurl/twocheckoutbuynow.gif\" border=\"0\" name=\"submit\" alt=\"";
	$wpbusdirmantwocheckoutbutton.=__("Pay with 2Checkout","WPBDM");
	$wpbusdirmantwocheckoutbutton.="\" />";
	$wpbusdirmantwocheckoutbutton.="</form>";

	return $wpbusdirmantwocheckoutbutton;
}



function wpbusdirmanpayment_twocheckout($wpbusdirmanlistingID,$x_sid,$x_amount,$first_name,$last_name,$payment_status,$payer_email)
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbdmpaymentstatus=get_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", true);
	$wpbdmpaymentgateway=get_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentgateway", true);
	$wpbdmlistingsticky=get_post_meta($wpbusdirmanlistingID, "_wpbdp_sticky", true);

	if(isset($wpbdmpaymentstatus)	&& !empty ($wpbdmpaymentstatus)	&& isset($wpbdmpaymentgateway) && !empty($wpbdmpaymentgateway) )
	{
		if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
		{
			add_post_meta($wpbusdirmanlistingID, "_wpbdp_sticky", "pending", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_sticky", "pending");
			$stickythankyou=wpbudirman_sticky_payment_thankyou();
			echo $stickythankyou;
		}
	}
	elseif( (!isset($wpbdmpaymentstatus) || !empty ($wpbdmpaymentstatus)) && ( !isset($wpbdmpaymentgateway) || empty($wpbdmpaymentgateway)) )
	{
		if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
		{
			add_post_meta($wpbusdirmanlistingID, "_wpbdp_sticky", "pending", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_sticky", "pending");
			$stickythankyou=wpbudirman_sticky_payment_thankyou();
			echo $stickythankyou;
		}
	}
	else
	{

		$wpbusdirman_twocheckout_sid=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_42'];


			$wpbusdirman_my_amts=array();
			$wpbusdirman_settings_fees_ops=wpbusdirman_retrieveoptions($whichoptionvalue='wpbusdirman_settings_fees_label_');

			foreach($wpbusdirman_settings_fees_ops as $wpbusdirman_settings_fees_op)
			{
				$wpbusdirman_my_amts[]=get_option('wpbusdirman_settings_fees_amount_'.$wpbusdirman_settings_fees_op);
			}

			if(!(in_array(number_format($x_amount,2),$wpbusdirman_my_amts)))
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentflag", "amountmismatch", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentflag", "amountmismatch");
			}


			if (!(strcasecmp($x_sid, $wpbusdirman_twocheckout_sid) == 0))
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentflag", "bizemailmismatch", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentflag", "bizemailmismatch");
			}

			if(strcasecmp($payment_status, "Completed") == 0)
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "paid", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "paid");
			}
			elseif(strcasecmp($payment_status, "Refunded") == 0 || strcasecmp($payment_status, "Reversed") == 0 || strcasecmp ($payment_status, "Partially-Refunded") == 0)
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "refunded", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "refunded");
			}
			elseif(strcasecmp ($payment_status, "Pending") == 0 )
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "pending", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "pending");
			}
			else
			{
				add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "unknown", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentstatus", "unknown");
			}

			if(!isset($first_name) || empty($first_name)){$first_name="Unknown";}
			if(!isset($last_name) || empty($last_name)){$last_name="Unknown";}


			add_post_meta($wpbusdirmanlistingID, "_wpbdp_buyerfirstname", $first_name, true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_buyerfirstname", $first_name);
			add_post_meta($wpbusdirmanlistingID, "_wpbdp_buyerlastname", $last_name, true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_buyerlastname", $last_name);
			add_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentgateway", "2Checkout", true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_paymentgateway", "2Checkout");
			add_post_meta($wpbusdirmanlistingID, "_wpbdp_payeremail", $payer_email, true) or update_post_meta($wpbusdirmanlistingID, "_wpbdp_payeremail", $payer_email);

		$paymentthankyou=wpbusdirman_payment_thankyou();
		echo $paymentthankyou;
	}
}


?>