<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// paypal.php
// A module for using paypal to handle Business Directory Plugin payment processing
//
// Author: A Lewis
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


global $wpdb;

function wpbusdirman_gpidpaypal(){
	global $wpdb;
	$wpbusdirman_pageid = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[WPBUSDIRMANPAYPAL]%' AND post_status='publish'  AND post_type='page'");
	return $wpbusdirman_pageid;
}

function wpbusdirman_do_paypal()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

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
	echo "</pre>";*/


	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';

	$wpbusdirman_verified=false;


	foreach ($_POST as $wpbusdirman_key => $wpbusdirman_value)
	{
		$wpbusdirman_value = urlencode(stripslashes($wpbusdirman_value));
		$req .= "&$wpbusdirman_key=$wpbusdirman_value";
	}

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
	{
		$wpbusdirmwpplink="www.sandbox.paypal.com";
	}
	else
	{
		$wpbusdirmwpplink="www.paypal.com";
	}
	// post back to PayPal system to validate
	$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Host: $wpbusdirmwpplink\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n";
	$header.="Connection: close\r\n\r\n";
	$fp = fsockopen($wpbusdirmwpplink, 80, $errno, $errstr, 30);

	$receiver_email='';
	$mc_gross='';
	$payment_gross='';
	$txn_id='';
	$txn_type='';
	$wpbusdirmanlistingID='';
	$first_name='';
	$last_name='';
	$payer_email='';

	// assign posted variables to local variables
	if(isset($_POST['receiver_email']) && !empty($_POST['receiver_email'])){$receiver_email	= $_POST['receiver_email'];}
	if(isset($_POST['mc_gross']) && !empty($_POST['mc_gross'])){$mcgross = $_POST['mc_gross'];}
	if(isset($_POST['payment_gross']) && !empty($_POST['payment_gross'])){$payment_gross = $_POST['payment_gross'];}
	if(isset($_POST['txn_id']) && !empty($_POST['txn_id'])){$txn_id	= $_POST['txn_id'];}
	if(isset($_POST['txn_type']) && !empty($_POST['txn_type'])){$txn_type = $_POST['txn_type'];}
	if(isset($_POST['custom']) && !empty($_POST['custom'])){$wpbusdirmanlistingID = $_POST['custom'];}
	if(isset($_POST['first_name']) && !empty($_POST['first_name'])){$first_name = $_POST['first_name'];}
	if(isset($_POST['last_name']) && !empty($_POST['last_name'])){$last_name = $_POST['last_name'];}
	if(isset($_POST['payment_status']) && !empty($_POST['payment_status'])){$payment_status = $_POST['payment_status'];}
	if(isset($_POST['payer_email']) && !empty($_POST['payer_email'])){$payer_email = $_POST['payer_email'];}


	if ($fp)
	{
		fputs ($fp, $header . $req."\r\n\r\n");
		$reply='';
		$headerdone=false;
			while(!feof($fp))
			{
				$line=fgets($fp);
				if (strcmp($line,"\r\n")==0)
				{
					// read the header
					$headerdone=true;
				}
				elseif ($headerdone)
				{
					// header has been read. now read the contents
					$reply.=$line;
				}
			}

		fclose($fp);
		$reply=trim($reply);

		if (strcasecmp($reply,'VERIFIED')!=0)
		{
			$payment_status="unverified";
		}


		wpbusdirmanpayment_paypal($wpbusdirmanlistingID,$receiver_email,$mcgross,$payment_gross,$txn_type,$txn_id,$first_name,$last_name,$payment_status,$payer_email);


	}
}

function wpbusdirman_paypal_button($wpbusdirmanlistingid,$wpbusdirmanfeeoption,$wpbusdirman_imagesurl,$wpbusdirmanlistingcost)
{

	global $permalinkstructure,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_gpidpaypal=wpbusdirman_gpidpaypal();
	$wpbusdirmanpaypalbusinessemail=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_35'];
	$wpbusdirmanpermalink=get_permalink($wpbusdirman_gpidpaypal);
	$wpbusdirman_pp_return_link_url="$wpbusdirmanpermalink";


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

	$wpbusdirmanpaypalbutton="";

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
	{
		$wpbdmpaypalurl="https://www.sandbox.paypal.com/cgi-bin/webscr";
	}
	else
	{
		$wpbdmpaypalurl="https://www.paypal.com/cgi-bin/webscr";
	}

	$wpbusdirmanpaypalbutton.="<form action=\"$wpbdmpaypalurl\" method=\"post\">";


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

		$wpbusdirmanfeeamount=$wpbusdirmanlistingcost;
		$wpbusdirmanfeeamount=number_format($wpbusdirmanfeeamount, 2, '.', '');
	}
		$wpbusdirmancurrencycode=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_20'];
		//$wpbusdirmansavedfeecategories=get_option('wpbusdirman_settings_fees_categories_'.$wpbusdirmanfeeoption);




	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"cmd\" value=\"_xclick\" />";
	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_22'] == "yes")
	{
		$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"test_ipn\" value=\"1\" />";
	}
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"business\" value=\"$wpbusdirmanpaypalbusinessemail\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"no_shipping\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"return\" value=\"$wpbusdirman_pp_return_link_url\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"notify_url\" value=\"$wpbusdirman_pp_return_link_url\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"cancel_return\" value=\"$wpbusdirman_pp_return_link_url\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"no_note\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"quantity\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"no_shipping\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"rm\" value=\"2\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"item_name\" value=\"$wpbusdirmanfeelabel\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"item_number\" value=\"item_$wpbusdirmanlistingid\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"amount\" value=\"$wpbusdirmanfeeamount\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"currency_code\" value=\"$wpbusdirmancurrencycode\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"custom\" value=\"$wpbusdirmanlistingid\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"src\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"hidden\" name=\"sra\" value=\"1\" />";
	$wpbusdirmanpaypalbutton.="<input type=\"image\" src=\"$wpbusdirman_imagesurl/paypalbuynow.gif\" border=\"0\" name=\"submit\" alt=\"";
	$wpbusdirmanpaypalbutton.=__("Make payments with PayPal - it's fast, free and secure!","WPBDM");
	$wpbusdirmanpaypalbutton.="\" />";
	$wpbusdirmanpaypalbutton.="</form>";

	return $wpbusdirmanpaypalbutton;
}


function wpbusdirmanpayment_paypal($wpbusdirmanlistingID,$receiver_email,$mcgross,$payment_gross,$txn_type,$txn_id,$first_name,$last_name,$payment_status,$payer_email)
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

	$wpbdmpaymentstatus=get_post_meta($wpbusdirmanlistingID, "paymentstatus", true);
	$wpbdmpaymentgateway=get_post_meta($wpbusdirmanlistingID, "paymentgateway", true);
	$wpbdmlistingsticky=get_post_meta($wpbusdirmanlistingID, "sticky", true);

	if(isset($wpbdmpaymentstatus)	&& !empty ($wpbdmpaymentstatus)	&& isset($wpbdmpaymentgateway) && !empty($wpbdmpaymentgateway) )
	{
		if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
		{
			add_post_meta($wpbusdirmanlistingID, "sticky", "pending", true) or update_post_meta($wpbusdirmanlistingID, "sticky", "pending");
			$stickythankyou=wpbudirman_sticky_payment_thankyou();
			echo $stickythankyou;
		}
	}
	elseif( (!isset($wpbdmpaymentstatus) || !empty ($wpbdmpaymentstatus)) && ( !isset($wpbdmpaymentgateway) || empty($wpbdmpaymentgateway)) )
	{
		if(isset($wpbdmlistingsticky) && !empty($wpbdmlistingsticky) && ($wpbdmlistingsticky == 'not paid'))
		{
			add_post_meta($wpbusdirmanlistingID, "sticky", "pending", true) or update_post_meta($wpbusdirmanlistingID, "sticky", "pending");
			$stickythankyou=wpbudirman_sticky_payment_thankyou();
			echo $stickythankyou;
		}
	}
	else
	{

		$wpbusdirman_paypal_email=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_35'];

		if (strcasecmp($txn_type, 'subscr-cancel') == 0)
		{
			add_post_meta($wpbusdirmanlistingID, "paymentstatus", "cancelled", true) or update_post_meta($wpbusdirmanlistingID, "paymentstatus", "cancelled");
		}
		else
		{

			/*$wpbusdirman_my_amts=array();
			$wpbusdirman_settings_fees_ops=wpbusdirman_retrieveoptions($whichoptionvalue='wpbusdirman_settings_fees_label_');

			foreach($wpbusdirman_settings_fees_ops as $wpbusdirman_settings_fees_op)
			{
				$wpbusdirman_my_amts[]=get_option('wpbusdirman_settings_fees_amount_'.$wpbusdirman_settings_fees_op);
			}

			if(!(in_array(number_format($mcgross,2),$wpbusdirman_my_amts) || in_array(number_format($payment_gross,2),$wpbusdirman_my_amts)))
			{
				add_post_meta($wpbusdirmanlistingID, "paymentflag", "amountmismatch", true) or update_post_meta($wpbusdirmanlistingID, "paymentflag", "amountmismatch");
			}*/


			if (!(strcasecmp($receiver_email, $wpbusdirman_paypal_email) == 0))
			{
				add_post_meta($wpbusdirmanlistingID, "paymentflag", "bizemailmismatch", true) or update_post_meta($wpbusdirmanlistingID, "paymentflag", "bizemailmismatch");
			}

			if(strcasecmp($payment_status, "Completed") == 0)
			{
				add_post_meta($wpbusdirmanlistingID, "paymentstatus", "paid", true) or update_post_meta($wpbusdirmanlistingID, "paymentstatus", "paid");
			}
			elseif(strcasecmp($payment_status, "Refunded") == 0 || strcasecmp($payment_status, "Reversed") == 0 || strcasecmp ($payment_status, "Partially-Refunded") == 0)
			{
				add_post_meta($wpbusdirmanlistingID, "paymentstatus", "refunded", true) or update_post_meta($wpbusdirmanlistingID, "paymentstatus", "refunded");
			}
			elseif(strcasecmp ($payment_status, "Pending") == 0 )
			{
				add_post_meta($wpbusdirmanlistingID, "paymentstatus", "pending", true) or update_post_meta($wpbusdirmanlistingID, "paymentstatus", "pending");
			}
			else
			{
				add_post_meta($wpbusdirmanlistingID, "paymentstatus", "unknown", true) or update_post_meta($wpbusdirmanlistingID, "paymentstatus", "unknown");
			}
		}

			add_post_meta($wpbusdirmanlistingID, "buyerfirstname", $first_name, true) or update_post_meta($wpbusdirmanlistingID, "buyerfirstname", $first_name);
			add_post_meta($wpbusdirmanlistingID, "buyerlastname", $last_name, true) or update_post_meta($wpbusdirmanlistingID, "buyerlastname", $last_name);
			add_post_meta($wpbusdirmanlistingID, "paymentgateway", "PayPal", true) or update_post_meta($wpbusdirmanlistingID, "paymentgateway", "PayPal");
			add_post_meta($wpbusdirmanlistingID, "payeremail", $payer_email, true) or update_post_meta($wpbusdirmanlistingID, "payeremail", $payer_email);

		$paymentthankyou=wpbusdirman_payment_thankyou();
		echo $paymentthankyou;
	}


}


?>