<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
Plugin Name: Business Directory Plugin
Plugin URI: http://www.businessdirectoryplugin.com
Description: Provides the ability to maintain a free or paid business directory on your WordPress powered site.
Version: 2.0.3
Author: D. Rodenbaugh
Author URI: http://businessdirectoryplugin.com
License: GPLv2 or any later version
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Business Directory Plugin (Formerly WP Business Directory Manager) provides the ability for you to add a business directory to your wordpress blog and charge a fee for users
// to submit their listing
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*  Copyright 2009-2012, Skyline Consulting and D. Rodenbaugh

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2 or later, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    reCAPTCHA used with permission of Mike Crawford & Ben Maurer, http://recaptcha.net
*/


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' ); // no trailing slash, full paths only - WP_CONTENT_URL is defined further down

if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content'); // no trailing slash, full paths only - WP_CONTENT_URL is defined further down

$wpcontenturl=WP_CONTENT_URL;
$wpcontentdir=WP_CONTENT_DIR;
$wpinc=WPINC;


$wpbusdirman_plugin_path = WP_CONTENT_DIR.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
$wpbusdirman_plugin_url = WP_CONTENT_URL.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
$wpbusdirman_plugin_dir = basename(dirname(__FILE__));
$wpbusdirman_haspaypalmodule=0;
$wpbusdirman_hastwocheckoutmodule=0;
$wpbusdirman_hasgooglecheckoutmodule=0;

$wpbusdirman_imagespath = WP_CONTENT_DIR.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'images';
$wpbusdirman_imagesurl = WP_CONTENT_URL.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'images';

$uploaddir=get_option('upload_path');
if(!isset($uploaddir) || empty($uploaddir))
{
	$uploaddir=ABSPATH;
	$uploaddir.="wp-content/uploads";
}


$wpbusdirmanimagesdirectory=$uploaddir;
$wpbusdirmanimagesdirectory.="/wpbdm";
$wpbusdirmanthumbsdirectory=$wpbusdirmanimagesdirectory;
$wpbusdirmanthumbsdirectory.="/thumbnails";

$wpbdmimagesurl="$wpcontenturl/uploads/wpbdm";

$nameofsite=get_option('blogname');
$siteurl=get_option('siteurl');
$thisadminemail=get_option('admin_email');

$wpbdmposttype="wpbdm-directory";
$wpbdmposttypecategory="wpbdm-category";
$wpbdmposttypetags="wpbdm-tags";

	if( file_exists("$wpbusdirman_plugin_path/gateways/googlecheckout.php") )
	{
		require("$wpbusdirman_plugin_path/gateways/googlecheckout.php");
		$wpbusdirman_hasgooglecheckoutmodule=1;
	}

	if($wpbusdirman_hasgooglecheckoutmodule == 1)
	{
		add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout');
	}

	if( file_exists("$wpbusdirman_plugin_path/wpbusdirman-maintenance-functions.php") )
	{
		require("$wpbusdirman_plugin_path/wpbusdirman-maintenance-functions.php");
	}
	
	if( file_exists("$wpbusdirman_plugin_path/admin/manage-options.php") )
	{
		require("$wpbusdirman_plugin_path/admin/manage-options.php");
	}

define('WPBDP_PATH', plugin_dir_path(__FILE__));
define('WPBDP_URL', plugins_url('/', __FILE__));
define('WPBDP_TEMPLATES_PATH', WPBDP_PATH . 'templates');

require_once(WPBDP_PATH . 'api.php');


define('WPBUSDIRMANURL', $wpbusdirman_plugin_url );
define('WPBUSDIRMANPATH', $wpbusdirman_plugin_path );
define('WPBUSDIRPLUGINDIR', 'wp-business-directory-manager');
define('WPBUSDIRMAN_TEMPLATES_PATH', $wpbusdirman_plugin_path . '/deprecated/templates');

$wpbusdirman_gpid=wpbusdirman_gpid();
$permalinkstructure=get_option('permalink_structure');
$wpbusdirmanconfigoptionsprefix="wpbusdirman";


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add actions and filters etc
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	add_action('wpbusdirman_listingexpirations_hook', 'wpbusdirman_listings_expirations' );
	add_action('wp_print_styles', 'wpbusdirman_addcss');

	add_shortcode('WPBUSDIRMANADDLISTING', 'wpBusDirManUi_addListingForm');
	add_shortcode('WPBUSDIRMANMANAGELISTING', 'wpbusdirman_managelistings');
	add_shortcode('WPBUSDIRMANMVIEWLISTINGS', 'wpbusdirman_viewlistings');

	add_filter('search_template', 'wpbusdirman_search_template');

	add_filter("wp_footer", "wpbusdirman_display_ac");


function wpBusDirManUi_addListingForm() {
	$controller = wpbdp()->controller;
	return $controller->submit_listing();
}

function wpbusdirman_get_the_business_email($post_id) {
	$api = wpbdp_formfields_api();

	// try first with the listing fields
	foreach ($api->getFieldsByAssociation('meta') as $field) {
		$value = wpbdp_get_listing_field_value($post_id, $field);

		if (wpbusdirman_isValidEmailAddress($value))
			return $value;
	}

	// then with the author email
	$post = get_post($post_id);
	if ($email = get_the_author_meta('user_email', $post->author))
		return $email;

	return '';
}

function wpbusdirman_the_image($wpbusdirman_pID,$size = 'medium' , $class = '')
{

	//setup the attachment array
	$att_array = array(
	'post_parent' => $wpbusdirman_pID,
	'post_type' => 'attachment',
	'post_mime_type' => 'image',
	'order_by' => 'menu_order'
	);

	//get the post attachments
	$attachments = get_children($att_array);

	//make sure there are attachments
	if (is_array($attachments))
	{
		//loop through them
		foreach($attachments as $att)
		{
			//find the one we want based on its characteristics
			if ( $att->menu_order == 0)
			{
				$image_src_array = wp_get_attachment_image_src($att->ID, $size);

				//get url - 1 and 2 are the x and y dimensions
				$url = $image_src_array[0];
				$caption = $att->post_excerpt;
				$image_html = '%s';

				//combine the data
				$wpbusdirman_img_html = sprintf($image_html,$url,$caption,$class);

				$wpbusdirman_image_url=$url;

			}

			return $wpbusdirman_image_url;
		}
	}
}

function wpbusdirman_isValidEmailAddress($email) {
	if (is_array($email))
		return false;

	return (bool) preg_match('/^(?!(?>\x22?(?>\x22\x40|\x5C?[\x00-\x7F])\x22?){255,})(?!(?>\x22?\x5C?[\x00-\x7F]\x22?){65,}@)(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22))(?>\.(?>[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+|(?>\x22(?>[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|\x5C[\x00-\x7F])*\x22)))*@(?>(?>(?!.*[^.]{64,})(?>(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*\.){0,126}(?>xn--)?[a-z0-9]+(?>-[a-z0-9]+)*)|(?:\[(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){7})|(?>(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?)))|(?>(?>IPv6:(?>(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){5}:)|(?>(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}:)?)))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\]))$/isD', $email);
}

function wpbusdirman_is_ValidDate($date)
{
	list($themonth,$theday,$theyear)=explode("/",$date);
	$theday=(int)$theday;
	$themonth=(int)$themonth;
	$theyear=(int)$theyear;

	if ($theday!="" && $themonth!="" && $theyear!="")
	{
		if (is_numeric($theyear) && is_numeric($themonth) && is_numeric($theday))
   		{
       		 return checkdate($themonth,$theday,$theyear);
    	}
    }

	return false;
}

// TODO - maybe replace this function?
function wpbusdirmancreatethumb($wpbusdirmanuploadedfilename,$wpbusdirmanuploaddir,$wpbusdirmanthumbnailwidth)
{
		$wpbusdirman_show_all=true;
		$wpbusdirman_thumbs_width=$wpbusdirmanthumbnailwidth;
		$mynewimg='';
		if (extension_loaded('gd')) {
			if ($wpbusdirman_imginfo=getimagesize($wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename)) {
				$width=$wpbusdirman_imginfo[0];
				$height=$wpbusdirman_imginfo[1];
				if ($width>$wpbusdirman_thumbs_width) {
					$newwidth=$wpbusdirman_thumbs_width;
					$newheight=$height*($wpbusdirman_thumbs_width/$width);
					if ($wpbusdirman_imginfo[2]==1) {		//gif
					} elseif ($wpbusdirman_imginfo[2]==2) {		//jpg
						if (function_exists('imagecreatefromjpeg')) {
							$myimg=@imagecreatefromjpeg($wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename);
						}
					} elseif ($wpbusdirman_imginfo[2]==3) {	//png
						$myimg=@imagecreatefrompng($wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename);
					}
					if (isset($myimg) && !empty($myimg)) {
						$gdinfo=wpbusdirman_GD();
						if (stristr($gdinfo['GD Version'], '2.')) {	// if we have GD v2 installed
							$mynewimg=@imagecreatetruecolor($newwidth,$newheight);
							if (imagecopyresampled($mynewimg,$myimg,0,0,0,0,$newwidth,$newheight,$width,$height)) {
								$wpbusdirman_show_all=false;
							}
						} else {	// GD 1.x here
							$mynewimg=@imagecreate($newwidth,$newheight);
							if (@imagecopyresized($mynewimg,$myimg,0,0,0,0,$newwidth,$newheight,$width,$height)) {
								$wpbusdirman_show_all=false;
							}
						}
					}
				}
			}
		}
		if (!is_writable($wpbusdirmanuploaddir.'/thumbnails')) {
			@chmod($wpbusdirmanuploaddir.'/thumbnails',0755);
			if (!is_writable($wpbusdirmanuploaddir.'/thumbnails')) {
				@chmod($wpbusdirmanuploaddir.'/thumbnails',0777);
			}
		}
		if ($wpbusdirman_show_all) {
			$myreturn=@copy($wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename,$wpbusdirmanuploaddir.'/thumbnails/'.$wpbusdirmanuploadedfilename);
		} else {
			$myreturn=@imagejpeg($mynewimg,$wpbusdirmanuploaddir.'/thumbnails/'.$wpbusdirmanuploadedfilename,100);
		}
		@chmod($wpbusdirmanuploaddir.'/thumbnails/'.$wpbusdirmanuploadedfilename,0644);
	return $myreturn;
}



		function wpbusdirman_GD()
		{
			$myreturn=array();
			if (function_exists('gd_info'))
			{
				$myreturn=gd_info();
			} else
			{
				$myreturn=array('GD Version'=>'');
				ob_start();
				phpinfo(8);
				$info=ob_get_contents();
				ob_end_clean();
				foreach (explode("\n",$info) as $line)
				{
					if (strpos($line,'GD Version')!==false)
					{
						$myreturn['GD Version']=trim(str_replace('GD Version', '', strip_tags($line)));
					}
				}
			}
			return $myreturn;
		}

function wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$wpbusdirmanfeeoptions)
{

	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	global $wpbusdirmanconfigoptionsprefix;

	$imagesalloweleftobj="";

	if($wpbusdirmanlistingpostid)
	{
		$existingfeeids=get_post_meta($wpbusdirmanlistingpostid,'_wpbdp_listingfeeid',false);

			if($existingfeeids)
			{
				foreach($existingfeeids as $existingfeeid)
				{
					$wpbusdirmannumimgsallowedarr[]=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$existingfeeid);
				}

				$wpbusdirmannumimagesallowed=max($wpbusdirmannumimgsallowedarr);
			}
			else
			{
				if($wpbusdirmanfeeoptions)
				{
					foreach($wpbusdirmanfeeoptions as $wpbusdirmanfeeoption)
					{
						$wpbusdirmannumimgsallowed=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$wpbusdirmanfeeoption);
						if(!isset($wpbusdirmannumimgsallowed) || empty($wpbusdirmannumimgsallowed)){$wpbusdirmannumimgsallowed=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_2'];}
						$wpbusdirmannumimgsallowedarr[]=$wpbusdirmannumimgsallowed;
					}

						$wpbusdirmannumimagesallowed=max($wpbusdirmannumimgsallowedarr);
				}
				else
				{
					$wpbusdirmannumimagesallowed=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_2'];
				}
			}
	}
	else
	{
		if($wpbusdirmanfeeoptions)
		{
			foreach($wpbusdirmanfeeoptions as $wpbusdirmanfeeoption)
			{
				$wpbusdirmannumimgsallowed=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_images_'.$wpbusdirmanfeeoption);
				if(!isset($wpbusdirmannumimgsallowed) || empty($wpbusdirmannumimgsallowed)){$wpbusdirmannumimgsallowed=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_2'];}
				$wpbusdirmannumimgsallowedarr[]=$wpbusdirmannumimgsallowed;
			}

				$wpbusdirmannumimagesallowed=max($wpbusdirmannumimgsallowedarr);
		}
		else
		{
				$wpbusdirmannumimagesallowed=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_2'];
		}
	}

		$existingimages=get_post_meta($wpbusdirmanlistingpostid,'_wpbdp_image',false);
		if($existingimages){ $totalexistingimages=count($existingimages); } else { $totalexistingimages = 0;}

		if($totalexistingimages > 0){$wpbusdirmannumimgsleft=($wpbusdirmannumimagesallowed - $totalexistingimages );}else{$wpbusdirmannumimgsleft = $wpbusdirmannumimagesallowed;}

		$imagesalloweleftobj=array('listingid' => $wpbusdirmanlistingpostid, 'imagesallowed' => $wpbusdirmannumimagesallowed, 'imagesleft' =>  $wpbusdirmannumimgsleft,'totalexisting' => $totalexistingimages );

		return $imagesalloweleftobj;

}

function wpbusdirman_managelistings() {
	return wpbdp()->controller->manage_listings();
}

function wpbusdirman_contactform($wpbusdirmanpermalink,$wpbusdirmanlistingpostid,$commentauthorname,$commentauthoremail,$commentauthorwebsite,$commentauthormessage,$wpbusdirmancontacterrors) {
	if (!wpbdp_get_option('show-contact-form'))
		return '';

	$action = '';
	
	$recaptcha = null;
	if (wpbdp_get_option('recaptcha-on')) {
		if ($public_key = wpbdp_get_option('recaptcha-public-key')) {
			require_once(WPBDP_PATH . 'recaptcha/recaptchalib.php');
			$recaptcha = recaptcha_get_html($public_key);
		}
	}

	return wpbdp_render('listing-contactform', array(
							'action' => $action,
							'validation_errors' => $wpbusdirmancontacterrors,
							'listing_id' => $wpbusdirmanlistingpostid,
							'current_user' => is_user_logged_in() ? wp_get_current_user() : null,
							'recaptcha' => $recaptcha							
						), false);
}

// TODO - implement thankyou message
function wpbusdirman_payment_thankyou()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_payment_thankyou_message=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_37'];
	$html = '';

	$html .= "<h3>" . __("Listing Sumitted","WPBDM") . "</h3>";
	if(isset($wpbusdirman_payment_thankyou_message)
		&& !empty($wpbusdirman_payment_thankyou_message))
	{
		$html .= "<p>$wpbusdirman_payment_thankyou_message</p>";
	}

	return $html;
}

// TODO - implement thankyou sticky message
function wpbudirman_sticky_payment_thankyou()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_payment_thankyou_message=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_37'];
	$html = '';

	$html .= "<h3>" . __("Listing Upgraded to featured","WPBDM") . "</h3>";
	if(isset($wpbusdirman_payment_thankyou_message)
		&& !empty($wpbusdirman_payment_thankyou_message))
	{
		$html .= "<p>$wpbusdirman_payment_thankyou_message</p>";
	}

	return $html;
}

function wpbusdirman_sticky_payment_thankyou()
{
	$html = '';

	$html .= "<h3>" . __("Listing Upgrade Payment Status","WPBDM") . "</h3>";
	$html .= "<p>" . __("Thank you for your payment. Your listing upgrade request and payment notification have been sent. Contact the administrator if your listing is not upgraded within 24 hours.","WPBDM") . "</p>";

	return $html;
}

function wpbusdirman_listings_expirations()
{
	global $wpbusdirman_gpid,$permalinkstructure,$nameofsite,$thisadminemail,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');
	if($wpbusdirman_myterms)
	{
		foreach($wpbusdirman_myterms as $wpbusdirman_myterm)
		{
			$wpbusdirman_postcatitems[]=$wpbusdirman_myterm->term_id;
		}
	}
	if($wpbusdirman_postcatitems)
	{
		foreach($wpbusdirman_postcatitems as $wpbusdirman_postcatitem)
		{
			$args = array(
				'post_status' => 'publish',
				'meta_key' => '_wpbdp_termlength',
				'post_type' => $wpbdmposttype,
				'meta_compare=>meta_value=0'
				);
			$wpbusdirman_catcat = get_posts($args);
			if ($wpbusdirman_catcat)
			{
				foreach ($wpbusdirman_catcat as $wpbusdirman_cat)
				{
					$wpbusdirman_postsposts[]=$wpbusdirman_cat->ID;
				}
			}
		}
	}
	if(!empty($wpbusdirman_postsposts))
	{

		foreach($wpbusdirman_postsposts as $listingwithtermlengthset)
		{
			$wpbusdirmantermlength=get_post_meta($listingwithtermlengthset, "_wpbdp_termlength", true);
			$wpbusdirmanpostdataarr=get_post( $listingwithtermlengthset );
			$wpbusdirmanpoststartdatebase=$wpbusdirmanpostdataarr->post_date;
			$wpbusdirmanpostauthorid=$wpbusdirmanpostdataarr->post_author;
			$wpbusdirmanpostauthoremail=get_the_author_meta( 'user_email', $wpbusdirmanpostauthorid );
			$wpbusdirmanstartdate = strtotime($wpbusdirmanpoststartdatebase);
			$wpbusdirmanexpiredate= date('Y-m-d', strtotime('+'.$wpbusdirmantermlength.' days', $wpbusdirmanstartdate));
			$wpbusdirmanlistingtitle=get_the_title($listingwithtermlengthset);
			$todaysdatestart=date('Y-m-d');
			$wpbusdirmantodaysdate=strtotime($todaysdatestart);
			$wpbusdirmanexpiredatestrt = strtotime($wpbusdirmanexpiredate);
			if ($wpbusdirmanexpiredatestrt < $wpbusdirmantodaysdate)
			{
				$wpbusdirman_my_expired_post = array();
				$wpbusdirman_my_expired_post['ID'] = $listingwithtermlengthset;
				$wpbusdirman_my_expired_post['post_status'] = 'wpbdmexpired';
				wp_update_post( $wpbusdirman_my_expired_post );
				$listingexpirationtext=__("has expired","WPBDM");
				$headers =	"MIME-Version: 1.0\n" .
						"From: $nameofsite <$thisadminemail>\n" .
						"Reply-To: $thisadminemail\n" .
						"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
				$subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( $wpbusdirmanlistingtitle, array() );
				$time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );
				if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_38'] == "yes")
				{
					$wpbusdirmanrenewlistingtext="To renew your listing click the link below";
					$wpbusdirmanrenewlistinglink=get_permalink($wpbusdirman_gpid);
					if(isset($permalinkstructure) && !empty($permalinkstructure))
					{
						$wpbusdirmanrenewlistinglink.="?do=renewlisting&id=$listingwithtermlengthset";
					}
					else
					{
						$wpbusdirmanrenewlistinglink.="&do=renewlisting&id=$listingwithtermlengthset";
					}
				}
				else
				{
					$wpbusdirmanrenewlistingtext="";
					$wpbusdirmanrenewlistinglink="";
				}
				$message = "

				$wpbusdirmanlistingtitle $listingexpirationtext

				$wpbusdirmanrenewlistingtext

				$wpbusdirmanrenewlistinglink

				Time: $time

				";
				@wp_mail( $wpbusdirmanpostauthoremail, $subject, $message, $headers );
			}
		}
	}
}

function wpbusdirman_viewlistings() {
	return wpbdp()->controller->view_listings();
}


//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail() {
	global $post, $wpbdmimagesurl, $wpbusdirman_imagesurl;

	if (!wpbdp_get_option('allow-images') || !wpbdp_get_option('show-thumbnail'))
		return '';

	$html = '';
	$thumbnail = null;
	
	if ($thumbnail_id = get_post_meta($post->ID, '_wpbdp[thumbnail_id]', true)) {
		$thumbnail = wp_get_attachment_thumb_url($thumbnail_id);
		// $thumbnail = $wpbdmimagesurl . '/thumbnails/' . $thumbnail;
	}

	if (!$thumbnail && function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID))
		return sprintf('<div class="listingthumbnail"><a href="%s">%s</a></div>',
					   get_permalink(),
					   get_the_post_thumbnail($post->ID,
										array(wpbdp_get_option('thumbnail-width', '120'), wpbdp_get_option('thumbnail-width', '120')),
										array('class' => 'wpbdmthumbs',
											  'alt' => the_title(null, null, false),
											  'title' => the_title(null, null, false) ))
					  );

	if (!$thumbnail && wpbdp_get_option('use-default-picture'))
		$thumbnail = $wpbusdirman_imagesurl . '/default.png';

	if ($thumbnail) {
		$html .= '<div class="listingthumbnail">';
		$html .= sprintf('<a href="%s"><img class="wpbdmthumbs" src="%s" width="%s" alt="%s" title="%s" border="0" /></a>',
						 get_permalink(),
						 $thumbnail,
						 wpbdp_get_option('thumbnail-width', '120'),
						 the_title(null, null, false),
						 the_title(null, null, false)
						);
		$html .= '</div>';
	}

	return $html;
}

function wpbusdirman_catpage_title()
{
	echo wpbusdirman_post_catpage_title();
}

function wpbusdirman_post_catpage_title()
{
	global $post,$wpbdmposttypecategory;
	$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
	$html = '';

	$html .=  $term->name;

	return $html;
}

function wpbusdirman_menu_buttons()
{
	echo wpbusdirman_post_menu_buttons();
}

function wpbusdirman_post_menu_buttons()
{
	$html = '';
	$html .= '' . wpbusdirman_post_menu_button_submitlisting() . wpbusdirman_menu_button_directory() . '</div><div style="clear:both;">';
	return $html;
}

function wpbusdirman_menu_button_submitlisting()
{
	echo wpbusdirman_post_menu_button_submitlisting();
}

function wpbusdirman_post_menu_button_submitlisting()
{
	return '<form method="post" action="' . wpbdp_get_page_link('add-listing') . '"><input type="hidden" name="action" value="submitlisting" /><input type="submit" class="submitlistingbutton" value="' . __("Submit A Listing","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_viewlistings()
{
	echo wpbusdirman_post_menu_button_viewlistings();
}

function wpbusdirman_post_menu_button_viewlistings()
{
	return '<form method="post" action="' . wpbdp_get_page_link('view-listings') . '"><input type="hidden" name="action" value="viewlistings" /><input type="submit" class="viewlistingsbutton" value="' . __("View Listings","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_directory()
{

	echo wpbusdirman_post_menu_button_directory();
}

function wpbusdirman_post_menu_button_directory()
{
	return '<form method="post" action="' . wpbdp_get_page_link('main') . '"><input type="submit" class="viewlistingsbutton" value="' . __("Directory","WPBDM") . '" /></form>';
}

function wpbusdirman_menu_button_editlisting()
{
	global $post;
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	if(is_user_logged_in())
	{
		global $current_user;
		get_currentuserinfo();
		$wpbusdirmanloggedinuseremail=$current_user->user_email;
		$wpbusdirmanauthoremail=get_the_author_meta('user_email');
		if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail)
		{
			$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . $post->ID . '" /><input type="submit" class="editlistingbutton" value="' . __("Edit Listing","WPBDM") . '" /></form>';
		}
	}

	return $html;
}

function wpbusdirman_menu_button_upgradelisting()
{
	global $post,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	if(is_user_logged_in())
	{
		global $current_user;
		get_currentuserinfo();
		$wpbusdirmanloggedinuseremail=$current_user->user_email;
		$wpbusdirmanauthoremail=get_the_author_meta('user_email');
		$wpbdmpostissticky=get_post_meta($post->ID, "_wpbdp_sticky", $single=true);
		if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail)
		{
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_31'] == "yes")
			{
				if( (!isset($wpbdmpostissticky) || empty($wpbdmpostissticky) || ($wpbdmpostissticky == 'not paid')) && ( $post->post_status == 'publish') )
				{
					$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="upgradetostickylisting" /><input type="hidden" name="listing_id" value="' . $post->ID . '" /><input type="submit" class="updradetostickylistingbutton" value="' . __("Upgrade Listing","WPBDM") . '" /></form>';
				}
			}
		}
	}

	return $html;
}

function wpbusdirman_list_categories()
{
	echo wpbusdirman_post_list_categories();
}

function wpbusdirman_post_list_categories()
{
	global $wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbdm_hide_empty=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_10'];
	$html = '';

	if(isset($wpbdm_hide_empty)
		&& !empty($wpbdm_hide_empty)
		&& ($wpbdm_hide_empty == "yes"))
	{
		$wpbdm_hide_empty=1;
	}
	elseif(isset($wpbdm_hide_empty)
		&& !empty($wpbdm_hide_empty)
		&& ($wpbdm_hide_empty == "no"))
	{
		$wpbdm_hide_empty=0;
	}
	$wpbdm_show_count=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_9'];
	if(isset($wpbdm_show_count)
		&& !empty($wpbdm_show_count)
		&& ($wpbdm_show_count == "yes"))
	{
		$wpbdm_show_count=1;
	}
	elseif(isset($wpbdm_show_count)
		&& !empty($wpbdm_show_count)
		&& ($wpbdm_show_count == "no"))
	{
		$wpbdm_show_count=0;
	}
	$wpbdm_show_parent_categories_only=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix."_settings_config_48"];
	// wpbdp_debug_e($wpbdm_show_parent_categories_only);
	// $wpbdm_show_parent_categories_only=1;
	if(isset($wpbdm_show_parent_categories_only)
		&& !empty($wpbdm_show_parent_categories_only)
		&& ($wpbdm_show_parent_categories_only == "yes"))
	{
		$wpbdm_show_parent_categories_only=1;
	}
	elseif(isset($wpbdm_show_parent_categories_only)
		&& !empty($wpbdm_show_parent_categories_only)
		&& ($wpbdm_show_parent_categories_only == "no"))
	{
		$wpbdm_show_parent_categories_only=0;
	}

	$taxonomy     = $wpbdmposttypecategory;
	$orderby      = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_7'];
	$show_count   = $wpbdm_show_count;      // 1 for yes, 0 for no
	$pad_counts   = 0;      // 1 for yes, 0 for no
	$hierarchical = $wpbdm_show_parent_categories_only;      // 1 for yes, 0 for no
	$title        = '';
	$order=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_8'];
	$hide_empty=$wpbdm_hide_empty;

	$html .= wp_list_categories(array(
		'taxonomy' => $taxonomy,
		'echo' => false,
		'title_li' => '',
		'orderby' => $orderby,
		'order' => $order,
		'show_count' => $show_count,
		'pad_counts' => true,
		'hide_empty' => $hide_empty,
		'hierarchical' => 1,
		'depth' => $wpbdm_show_parent_categories_only ? 1 : 0
	));

	return $html;
}

function wpbusdirman_dropdown_categories()
{
	global $post,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$wpbdm_hide_empty=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_10'];
	$html = '';

	if(isset($wpbdm_hide_empty)
		&& !empty($wpbdm_hide_empty)
		&& ($wpbdm_hide_empty == "yes"))
	{
		$wpbdm_hide_empty=1;
	}
	elseif(isset($wpbdm_hide_empty)
		&& !empty($wpbdm_hide_empty)
		&& ($wpbdm_hide_empty == "no"))
	{
		$wpbdm_hide_empty=0;
	}
	$wpbdm_show_count=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_9'];
	if(isset($wpbdm_show_count)
		&& !empty($wpbdm_show_count)
		&& ($wpbdm_show_count == "yes"))
	{
		$wpbdm_show_count=1;
	}
	elseif(isset($wpbdm_show_count)
		&& !empty($wpbdm_show_count)
		&& ($wpbdm_show_count == "no"))
	{
		$wpbdm_show_count=0;
	}
	$wpbdm_show_parent_categories_only=$wpbusdirmanconfigoptionsprefix."_settings_config_48";
	$wpbdm_show_parent_categories_only=1;
	if(isset($wpbdm_show_parent_categories_only)
		&& !empty($wpbdm_show_parent_categories_only)
		&& ($wpbdm_show_parent_categories_only == "yes"))
	{
		$wpbdm_show_parent_categories_only=0;
	}
	$wpbusdirman_postvalues=get_the_terms(get_the_ID(), $wpbdmposttypecategory);
	if($wpbusdirman_postvalues)
	{
		foreach($wpbusdirman_postvalues as $wpbusdirman_postvalue)
		{
			$wpbusdirman_field_value_selected=$wpbusdirman_postvalue->term_id;
		}
	}
	$html .= '<form action="' . bloginfo('url') . '" method="get">';
	$taxonomies = array($wpbdmposttypecategory);
	$args = array('echo'=>0,'show_option_none'=>$wpbusdirman_selectcattext,'orderby'=>$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_7'],'selected'=>$wpbusdirman_field_value_selected,'order'=>$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_8'],'hide_empty'=>$wpbdm_hide_empty,'hierarchical'=>$wpbdm_show_parent_categories_only);
	$select = get_terms_dropdown($taxonomies, $args);
	$select = preg_replace("#<select([^>]*)>#", "<select$1 onchange='return this.form.submit()'>", $select);
	$html .= $select;
	$html .= '<noscript><div><input type="submit" value="N?yt?" /></div></noscript></form>';

	return $html;
}

function get_terms_dropdown($taxonomies, $args)
{
	global $wpbdmposttypecategory;
	$myterms = get_terms($taxonomies, $args);
	$output ="<select name='".$wpbdmposttypecategory."'>";

	if($myterms)
	{
		foreach($myterms as $term){
			$root_url = get_bloginfo('url');
			$term_taxonomy=$term->taxonomy;
			$term_slug=$term->slug;
			$term_name =$term->name;
			$link = $term_slug;
			$output .="<option value='".$link."'>".$term_name."</option>";
		}
	}
	$output .="</select>";

	return $output;
}


function wpbusdirman_catpage_query()
{
	global $wpbdmposttype,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypetags;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
	//print_r($term);
	if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52']))
	{
		$wpbdm_order_listings_by=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52'];
	}

	if(isset($wpbdm_order_listings_by) && !empty($wpbdm_order_listings_by)){$wpbdmorderlistingsby=$wpbdm_order_listings_by;}
	else { $wpbdmorderlistingsby='date';}

	if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53']))
	{
		$wpbdm_sort_order_listings=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53'];
	}

	if(isset($wpbdm_sort_order_listings) && !empty($wpbdm_sort_order_listings)){$wpbdmsortorderlistings=$wpbdm_sort_order_listings;}
	else { $wpbdmsortorderlistings='ASC';}


	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

	$catortag=$term->taxonomy;
	if($catortag == $wpbdmposttypecategory)
	{
		$args=array(
		  $wpbdmposttypecategory => $term->name,
		  'post_type' => $wpbdmposttype,
		  'post_status' => 'publish',
		  'posts_per_page' => -1,
		'paged'=>$paged,
		'orderby'=>$wpbdmorderlistingsby,
		'order'=> $wpbdmsortorderlistings,
		'post__not_in' => $sticky_ids
		);
	}
	elseif($catortag == $wpbdmposttypetags) {
		$args=array(
		  $wpbdmposttypetags => $term->name,
		  'post_type' => $wpbdmposttype,
		  'post_status' => 'publish',
		  'posts_per_page' => -1,
		'paged'=>$paged,
		'orderby'=>$wpbdmorderlistingsby. ' meta_key=sticky&meta_value=approved',
		'order'=> $wpbdmsortorderlistings,
		);
	}
	//$mycatq = null;
	//$mycatq = new WP_Query($args);

	query_posts($args);
	//$query = new WP_Query( $args );
	//$wpbusdirman_stickyids=array();
}

function wpbusdirman_show_sticky(){}
function wpbusdirman_get_sticky_ids(){}

function wpbusdirman_indexpage_query()
{
	global $wpbdmposttype,$wpbusdirmanconfigoptionsprefix;
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;


	$wpbusdirman_config_options=get_wpbusdirman_config_options();

	if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52']))
	{
		$wpbdm_order_listings_by=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_52'];
	}

	if(isset($wpbdm_order_listings_by) && !empty($wpbdm_order_listings_by)){$wpbdmorderlistingsby=$wpbdm_order_listings_by;}
	else { $wpbdmorderlistingsby='date';}

	if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53']))
	{
		$wpbdm_sort_order_listings=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_53'];
	}

	if(isset($wpbdm_sort_order_listings) && !empty($wpbdm_sort_order_listings)){$wpbdmsortorderlistings=$wpbdm_sort_order_listings;}
	else { $wpbdmsortorderlistings='ASC';}


	$args=array(
	  'post_type' => $wpbdmposttype,
	  'post_status' => 'publish',
	'paged'=>$paged,
	'orderby'=>$wpbdmorderlistingsby. ' meta_key=sticky&meta_value=approved',
	'order'=>$wpbdmsortorderlistings
	);
	query_posts($args);
	$wpbusdirman_stickyids=array();
}

// Display the listing fields in excerpt view
function wpbusdirman_display_the_listing_fields() {
	global $post;

	$html = '';

	foreach (wpbdp_get_formfields() as $field) {
		if (!$field->display_options['show_in_excerpt'])
			continue;

		$html .= wpbdp_format_field_output($field, null, $post);
	}

	return $html;
}

function wpbusdirman_view_edit_delete_listing_button() {
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	$html .= '<div style="clear:both;"></div><div class="vieweditbuttons"><div class="vieweditbutton"><form method="post" action="' . get_permalink() . '"><input type="hidden" name="action" value="viewlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("View","WPBDM") . '" /></form></div>';

	if (wp_get_current_user()->ID == get_the_author_meta('ID')) {
		$html .= '<div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Edit","WPBDM") . '" /></form></div><div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="deletelisting" /><input type="hidden" name="listing_id" value="' . get_the_id() . '" /><input type="submit" value="' . __("Delete","WPBDM") . '" /></form></div>';
	}
	$html .= '</div>';

	return $html;
}

function wpbusdirman_display_excerpt($deprecated=null) {
	echo wpbusdirman_post_excerpt($deprecated);
}

function wpbusdirman_post_excerpt($deprecated=null) {
	static $count = 0;

	$is_sticky = get_post_meta(get_the_ID(), '_wpbdp_sticky', true) == 'approved' ? true : false;

	$html = '';
	$html .= sprintf('<div id="wpbdmlistings" class="wpbdp-listing excerpt %s %s %s">',
					$is_sticky ? 'sticky' : '',
					$is_sticky ? (($count & 1) ? 'wpbdmoddsticky' : 'wpbdmevensticky') : '',
					($count & 1) ? 'wpbdmodd' : 'wpbdmeven');

	$html .= wpbusdirman_display_the_thumbnail();

	$html .= '<div class="listingdetails">';
	$html .= apply_filters('wpbdp_listing_excerpt_view_before', '', get_the_ID());
	$html .= wpbusdirman_display_the_listing_fields();
	$html .= apply_filters('wpbdp_listing_excerpt_view_after', '', get_the_ID());
	$html .= wpbusdirman_view_edit_delete_listing_button();
	$html .= '</div>';
	$html .= '<div style="clear: both;"></div>';
	$html .= '</div>';

	$count++;

	return $html;
}


function wpbusdirman_display_ac()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_34'] == "yes")
	{
		$html .= '<div class="wpbdmac">Directory powered by <a href="http://businessdirectoryplugin.com/">Business Directory Plugin</a></div>';
	}

	return $html;
}

function wpbusdirman_display_main_image()
{
	echo wpbusdirman_post_main_image();
}

function wpbusdirman_post_main_image()
{
	global $post,$wpbdmimagesurl,$wpbusdirman_imagesurl,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_11'] == "yes")
	{
		$usingdefault=0;
		$wpbusdirmanpostimages=get_post_meta($post->ID, "_wpbdp_thumbnail", $single=false);
		$wpbusdirmanpostimagestotal=count($wpbusdirmanpostimages);
		$wpbusdirmanpostimagefeature='';
		if($wpbusdirmanpostimagestotal >=1)
		{
			$wpbusdirmanpostimagefeature=$wpbusdirmanpostimages[0];
		}
		$wpbdmusedef=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_39'];
		if(!isset($wpbdmusedef)
			|| empty($wpbdmusedef)
			|| ($wpbdmusedef == "yes"))
		{
			if(!isset($wpbusdirmanpostimagefeature)
				|| empty($wpbusdirmanpostimagefeature))
			{
				$usingdefault=1;
				$wpbusdirmanpostimagefeature=$wpbusdirman_imagesurl.'/default-image-big.gif';
			}
		}
		if ( function_exists('has_post_thumbnail') && has_post_thumbnail() )
		{

			$html .= '<a href="' . get_permalink() . '">' .the_post_thumbnail('medium') . '</a><br/>';
		}
		elseif(isset($wpbusdirmanpostimagefeature)
			&& !empty($wpbusdirmanpostimagefeature))
		{
			$html .= '<a href="' . get_permalink() . '"><img src="';
			if($usingdefault != 1)
			{
				$html .= $wpbdmimagesurl;
				$html .= '/';
			}
			$html .= $wpbusdirmanpostimagefeature . '" alt="' . the_title(null, null, false) . '" title="' . the_title(null, null, false) . '" border="0"></a><br />';
		}

	}

	return $html;
}

function wpbusdirman_display_extra_thumbnails()
{
	echo wpbusdirman_post_extra_thumbnails();
}

function wpbusdirman_post_extra_thumbnails()
{
	global $post,$wpbdmimagesurl;
	$wpbusdirmanpostimages=get_post_meta($post->ID, "_wpbdp_thumbnail", $single=false);
	$wpbusdirmanpostimagestotal=count($wpbusdirmanpostimages);
	$wpbusdirmanpostimagefeature='';
	$html = '';

	if($wpbusdirmanpostimagestotal >=1)
	{
		$wpbusdirmanpostimagefeature=$wpbusdirmanpostimages[0];
	}
	if($wpbusdirmanpostimagestotal > 1)
	{
		$html .= '<div class="extrathumbnails">';
		foreach($wpbusdirmanpostimages as $wpbusdirmanpostimage)
		{
			if(!($wpbusdirmanpostimage == $wpbusdirmanpostimagefeature))
			{
				$html .= '<a class="thickbox" href="' . $wpbdmimagesurl . '/' . $wpbusdirmanpostimage . '"><img class="wpbdmthumbs" src="' . $wpbdmimagesurl . '/thumbnails/' . $wpbusdirmanpostimage . '" alt="' . the_title(null, null, false) . '" title="' . the_title(null, null, false) . '" border="0"></a>';
			}
		}
		$html .= '</div>';
	}

	return $html;
}

function wpbusdirman_single_listing_details()
{
	echo wpbusdirman_post_single_listing_details();
}

function wpbusdirman_post_single_listing_details()
{
	global $post,$wpbusdirman_gpid,$wpbdmimagesurl,$wpbusdirman_imagesurl,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	if(is_user_logged_in()) {
		global $current_user;
		$html .= get_currentuserinfo();
		$wpbusdirmanloggedinuseremail=$current_user->user_email;
		$wpbusdirmanauthoremail=get_the_author_meta('user_email');
		$wpbdmpostissticky=get_post_meta($post->ID, "_wpbdp[sticky]", $single=true);
		if ($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail) {
			$html .= '<div id="editlistingsingleview">' . wpbusdirman_menu_button_editlisting() . wpbusdirman_menu_button_upgradelisting() . '</div><div style="clear:both;"></div>';
		}
	}

	if(isset($wpbdmpostissticky) && !empty($wpbdmpostissticky) && ($wpbdmpostissticky  == 'sticky') ) {
	 	$html .= '<span class="featuredlisting"><img src="' . $wpbusdirman_imagesurl . '/featuredlisting.png" alt="' . __("Featured Listing","WPBDM") . '" border="0" title="' . the_title(null, null, false) . '"></span>';
	}

	$html .= apply_filters('wpbdp_listing_view_before', '', $post->ID);

	$html .= '<div class="singledetailsview">';

	foreach (wpbdp_get_formfields() as $field) {
		if ($field->association == 'excerpt'):
			$html .= wpbdp_format_field_output($field, $post->post_excerpt);
		else:
			$html .= wpbdp_format_field_output($field, null, $post);
		endif;
	}

	$html .= apply_filters('wpbdp_listing_view_after', '', $post->ID);
	$html .= wpbusdirman_contactform($wpbusdirman_permalink,$post->ID,$commentauthorname='',$commentauthoremail='',$commentauthorwebsite='',$commentauthormessage='',$wpbusdirman_contact_form_errors='');
	$html .= '</div>';

	return $html;
}

function wpbusdirman_the_listing_title() {
	return wpbdp_format_field_output('title', null, get_the_ID());
}

function wpbusdirman_the_listing_excerpt() {
	if (has_excerpt(get_the_ID()))
		return wpbdp_format_field_output('excerpt', null, get_the_ID());
}

function wpbusdirman_the_listing_content() {
	return wpbdp_format_field_output('content', null, get_the_ID());
}

function wpbusdirman_the_listing_category() {
	return wpbdp_format_field_output('category', null, get_the_ID());
}

function wpbusdirman_the_listing_tags() {
	return wpbdp_format_field_output('tags', null, get_the_ID());
}

function wpbusdirman_the_listing_meta($excerptorsingle) {
	global $post;
	$html = '';

	foreach (wpbdp_formfields_api()->getFieldsByAssociation('meta') as $field) {
		if ($excerptorsingle == 'excerpt' && !$field->display_options['show_in_excerpt'])
			continue;

		$html .= wpbdp_format_field_output($field, null, $post);
	}

	return $html;
}

function wpbusdirman_latest_listings($numlistings)
{
	global $wpbdmposttype;
	$wpbdmpostheadline='';
	$args = array(
		'post_status' => 'publish',
		'post_type' => $wpbdmposttype,
		'numberposts' => $numlistings,
		'orderby' => 'date'
	);
	$wpbusdirman_theposts = get_posts($args);

	if($wpbusdirman_theposts)
	{
		foreach($wpbusdirman_theposts as $wpbusdirman_thepost)
		{
			$wpbdmpostheadline.="<li><a href=\"";
			$wpbdmpostheadline.=get_permalink($wpbusdirman_thepost->ID);
			$wpbdmpostheadline.="\">$wpbusdirman_thepost->post_title</a></li>";
		}
	}

	return $wpbdmpostheadline;
}





function remove_no_categories_msg($content) {
  if (!empty($content)) {
  if(function_exists('str_ireplace')){
    $content = str_ireplace('<li>' .__( "No categories" ). '</li>', "", $content);
    }
  }
  return $content;
}
add_filter('wp_list_categories','remove_no_categories_msg');



global $wpbdp;

require_once(WPBDP_PATH . 'utils.php');
require_once(WPBDP_PATH . 'admin/wpbdp-admin.class.php');
require_once(WPBDP_PATH . 'wpbdp-settings.class.php');
require_once(WPBDP_PATH . 'form-fields.php');
require_once(WPBDP_PATH . 'payment.php');
require_once(WPBDP_PATH . 'listings.php');
require_once(WPBDP_PATH . 'views.php');

require_once(WPBDP_PATH . '/deprecated/deprecated.php');

class WPBDP_Plugin {

	const VERSION = '2.0.3';
	const DB_VERSION = '2.5';

	const POST_TYPE = 'wpbdm-directory';
	const POST_TYPE_CATEGORY = 'wpbdm-category';
	const POST_TYPE_TAGS = 'wpbdm-tags';
	

	public function __construct() {
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

		if (is_admin()) {
			$this->admin = new WPBDP_Admin();
		}

		$this->settings = new WPBDP_Settings();
		$this->formfields = new WPBDP_FormFieldsAPI();
		$this->fees = new WPBDP_FeesAPI();
		$this->payments = new WPBDP_PaymentsAPI();
		$this->listings = new WPBDP_ListingsAPI();
		$this->controller = new WPBDP_DirectoryController();

		add_action('init', array($this, 'install_or_update_plugin'), 0);
		add_action('init', array($this, '_register_post_type'));

		add_filter('posts_join', array($this, '_join_with_terms'));
		add_filter('posts_where', array($this, '_include_terms_in_search'));
		
		add_filter('posts_request', array($this, '_posts_request'));
		add_action('pre_get_posts', array($this, '_pre_get_posts'));

		add_filter('comments_template', array($this, '_comments_template'));
		add_filter('taxonomy_template', array($this, '_category_template'));
		add_filter('single_template', array($this, '_single_template'));
	}

	// TODO: handle sticky posts
	public function _pre_get_posts(&$query) {
		global $wpdb;

		// category page query
		if (!$query->is_admin && $query->is_archive && $query->get(self::POST_TYPE_CATEGORY)) {
			$category = get_term_by('slug', $query->get(self::POST_TYPE_CATEGORY), self::POST_TYPE_CATEGORY);
			$category_ids = array_merge(array(intval($category->term_id)), get_term_children($category->term_id, self::POST_TYPE_CATEGORY));

			// select posts expired in this category (and all of its children)
			$sql = "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE 1=1";
			foreach ($category_ids as $cat_id)
				$sql .= sprintf(" AND (meta_key = '%s' AND meta_value = '%s')", '_wpbdp[expired][' . $cat_id . ']', '1');
			$excluded_ids = $wpdb->get_col($sql);

			$query->set('post_status', 'publish');
			$query->set('post__not_in', $excluded_ids);
			$query->set('post_type', self::POST_TYPE);
			$query->set('posts_per_page', 0);
			$query->set('orderby', wpbdp_get_option('listings-order-by', 'date'));
			$query->set('order', wpbdp_get_option('listings-sort', 'ASC'));
		}
	}

	public function _posts_request($sql) {
		wpbdp_debug($sql);
		return $sql;
	}

	public function plugin_activation() {
		add_action('init', array($this, 'flush_rules'), 11);
	}

	public function plugin_deactivation() {	}

	public function flush_rules() {
		if (function_exists('flush_rewrite_rules'))
			flush_rewrite_rules(false);
	}

	public function init() {
		$this->controller->init();

		do_action('wpbdp_modules_init');
		do_action('wpbdp_register_settings', $this->settings);
		do_action('wpbdp_register_fields', $this->formfields);
	}

	public function get_post_type() {
		return self::POST_TYPE;
	}

	public function get_post_type_category() {
		return self::POST_TYPE_CATEGORY;
	}

	public function get_post_type_tags() {
		return self::POST_TYPE_TAGS;
	}	

	public function get_version() {
		return self::VERSION;
	}

	public function get_db_version() {
			return self::DB_VERSION;
	}

	public function install_or_update_plugin() {
		global $wpdb;

		// For testing version-transitions.
		// add_option('wpbusdirman_db_version', '1.0');
		// // delete_option('wpbusdirman_db_version');
		// delete_option('wpbdp-db-version');
		// update_option('wpbdp-db-version', '2.4');
		// exit;

		$installed_version = get_option('wpbdp-db-version', get_option('wpbusdirman_db_version'));

		// create SQL tables
		if ($installed_version != self::DB_VERSION) {
			wpbdp_log('Running dbDelta.');

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			$sql = "CREATE TABLE {$wpdb->prefix}wpbdp_form_fields (
				id MEDIUMINT(9) PRIMARY KEY  AUTO_INCREMENT,
				label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				description VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
				type VARCHAR(100) NOT NULL,
				association VARCHAR(100) NOT NULL,
				validator VARCHAR(255) NULL,
				is_required TINYINT(1) NOT NULL DEFAULT 0,
				weight INT(5) NOT NULL DEFAULT 0,
				display_options BLOB NULL,
				field_data BLOB NULL
			) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

			dbDelta($sql);

			$sql = "CREATE TABLE {$wpdb->prefix}wpbdp_fees (
				id MEDIUMINT(9) PRIMARY KEY  AUTO_INCREMENT,
				label VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				images SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				categories BLOB NOT NULL,
				extra_data BLOB NULL
			) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

			dbDelta($sql);

			$sql = "CREATE TABLE {$wpdb->prefix}wpbdp_payments (
				id MEDIUMINT(9) PRIMARY KEY  AUTO_INCREMENT,
				listing_id MEDIUMINT(9) NOT NULL,
				gateway VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
				amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
				payment_type VARCHAR(255) NOT NULL,
				status VARCHAR(255) NOT NULL,
				created_on TIMESTAMP NOT NULL,
				processed_on TIMESTAMP NULL,
				processed_by VARCHAR(255) NOT NULL DEFAULT 'gateway',				
				payerinfo BLOB NULL,
				extra_data BLOB NULL
			) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

			dbDelta($sql);			
		}

		if ($installed_version) {
			wpbdp_log('WPBDP is already installed.');

			if (version_compare($installed_version, '2.0') < 0) {
				$this->settings->upgrade_options();
				wpbdp_log('WPBDP settings updated to 2.0-style');

				// make directory-related metadata hidden
				$old_meta_keys = array(
					'termlength', 'image', 'listingfeeid', 'sticky', 'thumbnail', 'paymentstatus', 'buyerfirstname', 'buyerlastname',
					'paymentflag', 'payeremail', 'paymentgateway', 'totalallowedimages', 'costoflisting'
				);

				foreach ($old_meta_keys as $meta_key) {
					$query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
											'_wpbdp_' . $meta_key, $meta_key, self::POST_TYPE);
					$wpdb->query($query);
				}

				wpbdp_log('Made WPBDP directory metadata hidden attributes');
			}

			if (version_compare($installed_version, '2.1') < 0) {
				// new form-fields support
				wpbdp_log('Updating old-style form fields.');
				$this->formfields->_update_to_2_1();
			}

			if (version_compare($installed_version, '2.2') < 0) {
				wpbdp_log('Updating table collate information.');
				$wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHARACTER SET utf8 COLLATE utf8_general_ci");
				$wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `label` `label` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
				$wpdb->query("ALTER TABLE {$wpdb->prefix}wpbdp_form_fields CHANGE `description` `description` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");
			}

			if (version_compare($installed_version, '2.3') < 0) {
				wpbdp_log('Updating fees to new format.');
				$this->fees->_update_to_2_3();
			}

			if (version_compare($installed_version, '2.4') < 0) {
				wpbdp_log('Making field values hidden metadata.');
				$this->formfields->_update_to_2_4();
			}
		} else {
			$default_fields = array(
				array(
					'label' => __("Business Name","WPBDM"),
					'type' => 'textfield',
					'association' => 'title',
					'weight' => 9,
					'is_required' => true,
					'display_options' => array('show_in_excerpt' => true)
				),
				array(
					'label' => __("Business Genre","WPBDM"),
					'type' => 'select',
					'association' => 'category',
					'weight' => 8,
					'is_required' => true,
					'display_options' => array('show_in_excerpt' => true)
				),
				array(
					'label' => __("Short Business Description","WPBDM"),
					'type' => 'textarea',
					'association' => 'excerpt',
					'weight' => 7
				),
				array(
					'label' => __("Long Business Description","WPBDM"),
					'type' => 'textarea',
					'association' => 'content',
					'weight' => 6,
					'is_required' => true
				),
				array(
					'label' => __("Business Website Address","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 5,
					'validator' => 'URLValidator',
					'display_options' => array('show_in_excerpt' => true)
				),
				array(
					'label' => __("Business Phone Number","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 4,
					'display_options' => array('show_in_excerpt' => true)
				),
				array(
					'label' => __("Business Fax","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 3
				),
				array(
					'label' => __("Business Contact Email","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 2,
					'validator' => 'EmailValidator',
					'is_required' => true
				),
				array(
					'label' => __("Business Tags","WPBDM"),
					'type' => 'textfield',
					'association' => 'tags',
					'weight' => 1
				)
			);

			foreach ($default_fields as $field) {
				$newfield = $field;
				if (isset($newfield['display_options']))
					$newfield['display_options'] = serialize($newfield['display_options']);

				$wpdb->insert($wpdb->prefix . 'wpbdp_form_fields', $newfield);
			}
		}

		delete_option('wpbusdirman_db_version');
		update_option('wpbdp-db-version', self::DB_VERSION);

	    $plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'WPBDM', null, $plugin_dir.'/languages' );		
	}

	function _register_post_type() {
		$post_type_slug = $this->settings->get('permalinks-directory-slug', self::POST_TYPE);
		$category_slug = $this->settings->get('permalinks-category-slug', self::POST_TYPE_CATEGORY);
		$tags_slug = $this->settings->get('permalinks-tags-slug', self::POST_TYPE_TAGS);

		$labels = array(
			'name' => _x('Directory', 'post type general name', 'WPBDM'),
			'singular_name' => _x('Directory', 'post type singular name', 'WPBDM'),
			'add_new' => _x('Add New Listing', 'listing', 'WPBDM'),
			'add_new_item' => _x('Add New Listing', 'post type', 'WPBDM'),
			'edit_item' => __('Edit Listing', 'WPBDM'),
			'new_item' => __('New Listing', 'WPBDM'),
			'view_item' => __('View Listing', 'WPBDM'),
			'search_items' => __('Search Listings', 'WPBDM'),
			'not_found' =>  __('No listings found', 'WPBDM'),
			'not_found_in_trash' => __('No listings found in trash', 'WPBDM'),
			'parent_item_colon' => ''
			);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array('slug'=> $post_type_slug, 'with_front' => false),
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => WPBDP_URL . 'resources/images/menuico.png',
			'supports' => array('title','editor','author','categories','tags','thumbnail','excerpt','comments','custom-fields','trackbacks')
		);

		register_post_type(self::POST_TYPE, $args);

		register_taxonomy(self::POST_TYPE_CATEGORY, self::POST_TYPE, array( 'hierarchical' => true, 'label' => 'Directory Categories', 'singular_name' => 'Directory Category', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count','query_var' => true, 'rewrite' => array('slug' => $category_slug) ) );
		register_taxonomy(self::POST_TYPE_TAGS, self::POST_TYPE, array( 'hierarchical' => false, 'label' => 'Directory Tags', 'singular_name' => 'Directory Tag', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count', 'query_var' => true, 'rewrite' => array('slug' => $tags_slug) ) );
	}

	public function debug_on() {
		WPBDP_Debugging::debug_on();
	}

	public function debug_off() {
		WPBDP_Debugging::debug_off();
	}

	public function has_module($name) {
		global $wpbusdirman_haspaypalmodule, $wpbusdirman_hastwocheckoutmodule, $wpbusdirman_hasgooglecheckoutmodule;

		switch (strtolower($name)) {
			default:
				break;
			case 'paypal':
				return $wpbusdirman_haspaypalmodule == 1;
				break;
			case '2checkout':
			case 'twocheckout':
				return $wpbusdirman_hastwocheckoutmodule == 1;
				break;
			case 'googlecheckout':
				return $wpbusdirman_hasgooglecheckoutmodule == 1;
				break;
		}

		return false;
	}

	/* search filters */
	public function _join_with_terms($join) {
		global $wp_query, $wpdb;

		if ($wp_query->is_search && !empty($wp_query->query_vars['s']) && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == self::POST_TYPE) {
			$on = array();
			$on[] = "ttax.taxonomy = '" . self::POST_TYPE_CATEGORY . "'";
			$on[] = "ttax.taxonomy = '" . self::POST_TYPE_TAGS . "'";

			$on = ' ( ' . implode( ' OR ', $on ) . ' ) ';
			$join .= " LEFT JOIN {$wpdb->term_relationships} AS trel ON ({$wpdb->posts}.ID = trel.object_id) LEFT JOIN {$wpdb->term_taxonomy} AS ttax ON ( " . $on . " AND trel.term_taxonomy_id = ttax.term_taxonomy_id) LEFT JOIN {$wpdb->terms} AS tter ON (ttax.term_id = tter.term_id) ";
		}

		return $join;
	}

	public function _include_terms_in_search($query) {
		global $wp_query, $wpdb;

		if ($wp_query->is_search && !empty($wp_query->query_vars['s']) && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == self::POST_TYPE) {
			$query .= $wpdb->prepare(' OR (tter.name LIKE \'%%%s%%\')', $wp_query->query_vars['s']);
			$query .= $wpdb->prepare(' OR (tter.slug LIKE \'%%%s%%\')', $wp_query->query_vars['s']);
			$query .= $wpdb->prepare(' OR (ttax.description LIKE \'%%%s%%\')', $wp_query->query_vars['s']);
		}

		return $query;
	}

	/* theme filters */
	public function _comments_template($template) {
		if (is_single() && get_post_type() == self::POST_TYPE && !$this->settings->get('show-comment-form')) {
			return WPBDP_TEMPLATES_PATH . '/empty-template.php';
		}

		return $template;
	}

	public function _category_template($template) {
		if (get_query_var(self::POST_TYPE_CATEGORY) && taxonomy_exists(self::POST_TYPE_CATEGORY)) {
			return wpbdp_locate_template(array('businessdirectory-category', 'wpbusdirman-category'));
		}

		return $template;
	}

	public function _single_template($template) {
		if (is_single() && get_post_type() == self::POST_TYPE) {
			return wpbdp_locate_template(array('businessdirectory-single', 'wpbusdirman-single'));
		}

		return $template;
	}


}

$wpbdp = new WPBDP_Plugin();
$wpbdp->init();
$wpbdp->debug_on();