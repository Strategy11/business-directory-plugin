<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
Plugin Name: Business Directory Plugin
Plugin URI: http://www.businessdirectoryplugin.com
Description: Provides the ability to maintain a free or paid business directory on your WordPress powered site.
Version: 2.0-BETA
Author: D. Rodenbaugh
Author URI: http://businessdirectoryplugin.com
Contributors: Mike Bronner - Rocking Double-M Services (http://rocking-mm.com), A. Lewis
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Business Directory Plugin (Formerly WP Business Directory Manager) provides the ability for you to add a business directory to your wordpress blog and charge a fee for users
// to submit their listing
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*  Copyright 2009-2012, Skyline Consulting and D. Rodenbaugh

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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

$wpbusdirman_db_version = "1.0";

	if( file_exists("$wpbusdirman_plugin_path/gateways/paypal.php") )
	{
		require("$wpbusdirman_plugin_path/gateways/paypal.php");
		$wpbusdirman_haspaypalmodule=1;
	}
	if( file_exists("$wpbusdirman_plugin_path/gateways/twocheckout.php") )
	{
		require("$wpbusdirman_plugin_path/gateways/twocheckout.php");
		$wpbusdirman_hastwocheckoutmodule=1;
	}
	if( file_exists("$wpbusdirman_plugin_path/gateways/googlecheckout.php") )
	{
		require("$wpbusdirman_plugin_path/gateways/googlecheckout.php");
		$wpbusdirman_hasgooglecheckoutmodule=1;
	}

	if($wpbusdirman_haspaypalmodule	== 1)
	{
		add_shortcode('WPBUSDIRMANPAYPAL', 'wpbusdirman_do_paypal');
	}
	if($wpbusdirman_hastwocheckoutmodule == 1)
	{
		add_shortcode('WPBUSDIRMANTWOCHECKOUT', 'wpbusdirman_do_twocheckout');
	}
	if($wpbusdirman_hasgooglecheckoutmodule == 1)
	{
		add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout');
	}

	if( file_exists("$wpbusdirman_plugin_path/wpbusdirman-maintenance-functions.php") )
	{
		require("$wpbusdirman_plugin_path/wpbusdirman-maintenance-functions.php");
	}

	if( file_exists("$wpbusdirman_plugin_path/wpbusdirman-install.php") )
	{
		require("$wpbusdirman_plugin_path/wpbusdirman-install.php");
	}
	
	if( file_exists("$wpbusdirman_plugin_path/admin/wpbusdirman-fields-manager.php") )
	{
		require("$wpbusdirman_plugin_path/admin/wpbusdirman-fields-manager.php");
	}
	if( file_exists("$wpbusdirman_plugin_path/admin/wpbusdirman-fees-manager.php") )
	{
		require("$wpbusdirman_plugin_path/admin/wpbusdirman-fees-manager.php");
	}
	if( file_exists("$wpbusdirman_plugin_path/admin/manage-options.php") )
	{
		require("$wpbusdirman_plugin_path/admin/manage-options.php");
	}


$wpbusdirman_labeltext=__("Label","WPBDM");
$wpbusdirman_typetext=__("Type","WPBDM");
$wpbusdirman_associationtext=__("Association","WPBDM");
$wpbusdirman_optionstext=__("Options","WPBDM");
$wpbusdirman_ordertext=__("Order","WPBDM");
$wpbusdirman_actiontext=__("Action","WPBDM");
$wpbusdirman_valuetext=__("Value","WPBDM");
$wpbusdirman_amounttext=__("Amount","WPBDM");
$wpbusdirman_appliedtotext=__("Applied To","WPBDM");
$wpbusdirman_allcatstext=__("All categories","WPBDM");
$wpbusdirman_daytext=__("Day","WPBDM");
$wpbusdirman_daystext=__("Days","WPBDM");
$wpbusdirman_imagestext=__("Images","WPBDM");
$wpbusdirman_durationtext=__("Duration","WPBDM");
$wpbusdirman_validationtext=__("Validation","WPBDM");
$wpbusdirman_requiredtext=__("Required","WPBDM");
$wpbusdirman_showinexcerpttext=__("Excerpt","WPBDM");


define('WPBUSDIRMANURL', $wpbusdirman_plugin_url );
define('WPBUSDIRMANPATH', $wpbusdirman_plugin_path );
define('WPBUSDIRPLUGINDIR', 'wp-business-directory-manager');
define('WPBUSDIRMANMENUICO', $wpbusdirman_imagesurl .'/menuico.png');
define('WPBUSDIRMAN_TEMPLATES_PATH', $wpbusdirman_plugin_path . '/posttemplate');

define('WPBDP_PATH', plugin_dir_path(__FILE__));

$wpbusdirman_gpid=wpbusdirman_gpid();
$permalinkstructure=get_option('permalink_structure');
$wpbusdirmanconfigoptionsprefix="wpbusdirman";

$wpbusdirman_field_vals_pfl=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add actions and filters etc
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	add_action('init', 'wpbusdirman_install');
	add_action( 'wpbusdirman_listingexpirations_hook', 'wpbusdirman_listings_expirations' );
	add_action('wp_print_styles', 'wpbusdirman_addcss');
	add_shortcode('WPBUSDIRMANUI', 'wpbusdirmanui_homescreen');
	add_shortcode('WPBUSDIRMANADDLISTING', 'wpBusDirManUi_addListingForm');
	add_shortcode('WPBUSDIRMANMANAGELISTING', 'wpbusdirman_managelistings');
	add_shortcode('WPBUSDIRMANMVIEWLISTINGS', 'wpbusdirman_viewlistings');
	add_filter('single_template', 'wpbusdirman_single_template');
	add_filter('taxonomy_template', 'wpbusdirman_category_template');
	add_filter('search_template', 'wpbusdirman_search_template');
	//add_filter('the_content', 'wpbusdirman_template_the_content');
	add_filter('comments_template', 'wpbusdirman_template_comment');
	//add_filter('the_title', 'wpbusdirman_template_the_title');
	//add_action('loop_start', 'wpbusdirman_remove_post_dates_author_etc');

	//add_filter('the_post', 'wpbusdirman_template_the_post');
	add_filter("wp_footer", "wpbusdirman_display_ac");
	add_filter('wp_list_pages_excludes', 'wpbusdirman_exclude_payment_pages');



/*******************************************************************************
*	SETTING UP PLUGIN HOOKS TO ALLOW CUSTOM OVERRIDES
*******************************************************************************/
	//display add listing form
	add_filter('wpbdm_show-add-listing-form', 'wpbusdirman_displaypostform', 10, 4);
	//display directory
	add_filter('wpbdm_show-directory', 'wpbusdirmanui_directory_screen', 10, 0);
	//display image upload form
	add_filter('wpbdm_show-image-upload-form', 'wpbusdirman_image_upload_form', 10, 8);
	//form post handler
	add_filter('wpbdm_process-form-post', 'wpbusdirman_do_post', 10, 0);


function wpbdm_get_post_data($data,$wpbdmlistingid)
{
		global $table_prefix;
		// Set field label values
		$query="SELECT $data FROM {$table_prefix}posts WHERE ID = '$wpbdmlistingid'";
		if (!($res=mysql_query($query))) {die(__(' Failure retrieving table data ['.$query.'].'));}
		while ($rsrow=mysql_fetch_row($res))
		{
			list($wpbusdirman_post_data)=$rsrow;
		}

	return $wpbusdirman_post_data;
}



function wpBusDirManUi_addListingForm()
{
	$wpbusdirmanaction = '';
	$html = '';

	if(isset($_REQUEST['action'])
		&& !empty($_REQUEST['action']))
	{
		$wpbusdirmanaction=$_REQUEST['action'];
	}
	elseif(isset($_REQUEST['do'])
		&& !empty ($_REQUEST['do']))
	{
		$wpbusdirmanaction=$_REQUEST['do'];
	}
	if ("post" == $wpbusdirmanaction)
	{
		$html .= apply_filters('wpbdm_process-form-post', null);
	}
	else
	{
		$html .= apply_filters('wpbdm_show-add-listing-form', 1, '', 'new', '');
	}

	return $html;
}

function wpbusdirman_displaypostform($makeactive = 1, $wpbusdirmanerrors = '', $neworedit = 'new', $wpbdmlistingid = '')
{
 	global $wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory,$wpbdmposttypetags,$wpbdmposttype;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirmanselectedword="selected";
 	$wpbusdirmancheckedword="checked";
	$wpbusdirman_field_value='';
 	$args=array('hide_empty' => 0);
 	$wpbusdirman_postcats=get_terms( $wpbdmposttypecategory, $args);
 	$html = '';
 	$html .= "<div id=\"wpbdmentry\">";
 	$html .= "<div id=\"lco\">";
	$html .= "<div class=\"title\">";
	if($neworedit == 'new'){
	$html .= "Submit A Listing";
	}
	elseif($neworedit == 'edit') {
	$html .= "Edit Your Listing";}
	else {
	$html .= "Submit A Listing";
	}
	$html .= "</div>";
	$html .= "<div class=\"button\">";
	$html .= "<form method=\"post\" action=\"\"><input type=\"hidden\" name=\"action\" value=\"viewlistings\"><input type=\"submit\" class=\"viewlistingsbutton\" value=\"" . __("View Listings","WPBDM") . "\"></form>";
	$html .= "<form method=\"post\" action=\"\"><input type=\"submit\" class=\"viewlistingsbutton\" value=\"" . __("Directory","WPBDM") . "\"></form>";
	$html .= "</div>";
	$html .= "<div style=\"clear:both;\"></div></div>";

 	if(!isset($wpbusdirman_postcats) || empty($wpbusdirman_postcats))
 	{
 		if(is_user_logged_in() && current_user_can('install_plugins'))
 		{
 			$html .= "<p>" . __("There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they cannot add their listing at this time. Listings cannot be added until you assign categories to the business directory.","WPBDM") . "</p>";
 		}
 		else
 		{
 			$html .= "<p>" . __("Your listing cannot be added at this time. Please try again later.","WPBDM") . "</p>";
 		}
 	}
 	else
	{
		if(($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_3'] == "yes") && !is_user_logged_in())
		{
			$wpbusdirman_loginurl=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_4'];
			if(!isset($wpbusdirman_loginurl) || empty($wpbusdirman_loginurl))
			{
				$wpbusdirman_loginurl=get_option('siteurl').'/wp-login.php';
			}
			$html .= "<p>" . __("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email with 60 mintues.","WPBDM") . "</p>";
			$html .= "<form method=\"post\" action=\"$wpbusdirman_loginurl\"><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Login Now","WPBDM") . "\"></form>";
		}
		else
		{
			$wpbusdirman_selectcattext=__("Choose One","WPBDM");
			$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
			global $wpbusdirman_gpid,$permalinkstructure;
			if(!isset($permalinkstructure)
				|| empty($permalinkstructure))
			{
				$querysymbol="&amp";
			}
			else
			{
				$querysymbol="?";
			}
			$html .= "<div class=\"clear\"></div><form method=\"post\" action=\"\" enctype=\"application/x-www-form-urlencoded\">";
			$html .= "<input type=\"hidden\" name=\"formmode\" value=\"$makeactive\" />";
			$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\" />";
			$html .= "<input type=\"hidden\" name=\"wpbdmlistingid\" value=\"$wpbdmlistingid\" />";
			$html .= "<input type=\"hidden\" name=\"action\" value=\"post\" />";
			if (isset($wpbusdirmanerrors)
				&& (!empty($wpbusdirmanerrors)))
			{
				$html .= "<ul id=\"wpbusdirmanerrors\">" . $wpbusdirmanerrors . "</ul>";
			}
			if($wpbusdirman_field_vals)
			{
				foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
				{
					$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
					$wpbusdirman_field_label_name=$wpbusdirman_field_label;
					$wpbusdirman_field_type=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);
					$wpbusdirman_field_options=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_field_val);
					$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
					$class_required = '';
					if ("yes" == get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val))
					{
						$class_required = ' required';
					}
					switch ($wpbusdirman_field_association)
					{
						case 'title':
						case 'category':
						case 'excerpt':
						case 'description':
						case 'tags':
							$wpbusdirman_field_label_association = "_" . $wpbusdirman_field_association;
							break;
						default:
							$wpbusdirman_field_label_association = "_meta$wpbusdirman_field_val";
							break;
					}
					if(isset($wpbusdirmanerrors)
						&& !empty($wpbusdirmanerrors))
					{
						if($wpbusdirman_field_label_association == "_category")
						{
							if($wpbusdirman_field_type == 2){$wpbusdirman_field_value=$_REQUEST['cat'];}
							elseif($wpbusdirman_field_type == 6){$wpbusdirman_field_value=$_REQUEST['wpbusdirman_field_label_category'];}
						}
						else
						{
							$wpbusdirman_field_value=$_REQUEST['wpbusdirman_field_label'.$wpbusdirman_field_label_association];
						}
					}
					else
					{
						if(isset($wpbdmlistingid)
							&& !empty($wpbdmlistingid))
						{
							switch ($wpbusdirman_field_association)
							{
								case 'category':
									$wpbusdirman_field_value=array();
									$wpbusdirman_postvalues=get_the_terms($wpbdmlistingid, $wpbdmposttypecategory);


									if($wpbusdirman_postvalues)
									{
										foreach($wpbusdirman_postvalues as $wpbusdirman_postvalue)
										{
											$wpbusdirman_field_value[]=$wpbusdirman_postvalue->term_id;
										}

									}
									break;
								case 'title':
									$wpbusdirman_field_value=get_the_title($wpbdmlistingid);
									break;
								case 'description':
									$wpbusdirman_field_value=wpbdm_get_post_data($data='post_content',$wpbdmlistingid);
									break;
								case 'excerpt':
									$wpbusdirman_field_value=wpbdm_get_post_data($data='post_excerpt',$wpbdmlistingid);
									break;
								case 'tags':
									$wpbusdirman_field_value='';
									$wpbusdirmanfieldtagsarr=array();
									$wpbusdirmanfieldtagsobject=get_the_terms($wpbdmlistingid, $wpbdmposttypetags);
									if($wpbusdirmanfieldtagsobject)
									{
										foreach($wpbusdirmanfieldtagsobject as $wpbusdirmanfieldtags)
										{
										   $wpbusdirmantag=$wpbusdirmanfieldtags->slug;
										   $wpbusdirmanfieldtagsarr[]=$wpbusdirmantag;
										}
									}
									if($wpbusdirmanfieldtagsarr)
									{
										$wpbusdirman_last_field_tag=end($wpbusdirmanfieldtagsarr);
										foreach($wpbusdirmanfieldtagsarr as $wpbusdirman_field_tag)
										{
											$wpbusdirman_field_value.="$wpbusdirman_field_tag";
											if($wpbusdirman_last_field_tag != $wpbusdirman_field_tag )
											{
												$wpbusdirman_field_value.=",";
											}
										}
									}
									break;
								default:
									$wpbusdirman_field_value=get_post_meta($wpbdmlistingid, $wpbusdirman_field_label, $single = true);
									break;
							}
						}
					}
					switch ($wpbusdirman_field_type)
					{
						case 1:
							$html .= "<p class=\"wpbdmp\"><label for=\"wpbusdirman_field_label$wpbusdirman_field_label_association\">$wpbusdirman_field_label_name</label>";
							$wpbusdirman_field_validation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_val);
							if($wpbusdirman_field_validation == 'date')
							{
								$html .= __("Format 01/31/1969","WPBDM");
							}
							$html .= "</p><input type=\"text\" id=\"wpbusdirman_field_label$wpbusdirman_field_label_association\" name=\"wpbusdirman_field_label$wpbusdirman_field_label_association\" class=\"intextbox" . $class_required . "\" value=\"$wpbusdirman_field_value\">";
							break;
						case 2:
							if($wpbusdirman_field_association == 'category')
							{
								if(is_array($wpbusdirman_field_value))
								{
									$wpbusdirman_field_value_selected=$wpbusdirman_field_value[0];
								}
								else
								{
									$wpbusdirman_field_value_selected=$wpbusdirman_field_value;
								}
								$html .= "<p class=\"wpbdmp\"><label for=\"cat\">$wpbusdirman_field_label_name</label></p>";
								$html .= wp_dropdown_categories(array('taxonomy' => $wpbdmposttypecategory, 'show_option_none' => $wpbusdirman_selectcattext, 'orderby' => 'name', 'selected' => $wpbusdirman_field_value_selected, 'order' => 'ASC', 'hide_empty' => 0, 'hierarchical' => 1, 'echo' => 0, 'class' => $class_required));
							}
							else
							{
								$html .= "<p class=\"wpbdmp\"><label for=\"wpbusdirman_field_label$wpbusdirman_field_label_association\">$wpbusdirman_field_label_name</label></p><select class=\"inselect" . $class_required . "\" id=\"wpbusdirman_field_label$wpbusdirman_field_label_association\" name=\"wpbusdirman_field_label$wpbusdirman_field_label_association\">";
								$wpbusdirman_formselops=explode(",",$wpbusdirman_field_options);
								$wpbusdirman_formselop=array();
								for ($i=0;isset($wpbusdirman_formselops[$i]);++$i)
								{
									$wpbusdirman_formselop[]=$wpbusdirman_formselops[$i];
								}
								if($wpbusdirman_formselop)
								{
									foreach($wpbusdirman_formselop as $wpbusdirman_formseloption)
									{
										$wpbusdirman_formseloption=trim($wpbusdirman_formseloption);
										if($wpbusdirman_field_value == $wpbusdirman_formseloption)
										{
											$wpbusdirmanselected="selected";
										}
										else
										{
											$wpbusdirmanselected='';
										}
										$html .= "<option $wpbusdirmanselected value=\"$wpbusdirman_formseloption\" $wpbusdirmanselected>$wpbusdirman_formseloption</option>";
									}
								}
								$html .= "</select>";
							}
							break;
						case 3:
							$wpbusdirman_field_value=stripslashes($wpbusdirman_field_value);
							$html .= "<p class=\"wpbdmp\"><label for=\"wpbusdirman_field_label$wpbusdirman_field_label_association\">$wpbusdirman_field_label_name</label></p><textarea id=\"\" name=\"wpbusdirman_field_label$wpbusdirman_field_label_association\" class=\"intextarea" . $class_required . "\">$wpbusdirman_field_value</textarea>";
							break;
						case 4:
							$html .= "<p class=\"wpbdmp\"><label>$wpbusdirman_field_label_name</label></p>";
							$wpbusdirman_formselops=explode(",",$wpbusdirman_field_options);
							$wpbusdirman_formselop=array();
							for ($i=0;isset($wpbusdirman_formselops[$i]);++$i)
							{
								$wpbusdirman_formselop[]=$wpbusdirman_formselops[$i];
							}
							if($wpbusdirman_formselop)
							{
								foreach($wpbusdirman_formselop as $wpbusdirman_formseloption)
								{
									$wpbusdirman_formseloption=trim($wpbusdirman_formseloption);
									if($wpbusdirman_formseloption == $wpbusdirman_field_value)
									{
										$wpbusdirmanchecked="checked";
									}
									else
									{
										$wpbusdirmanchecked='';
									}
									$html .= "<span style=\"padding-right:10px;\"><input type=\"radio\" class=\"" . $class_required . "\" name=\"wpbusdirman_field_label$wpbusdirman_field_label_association\" value=\"$wpbusdirman_formseloption\" $wpbusdirmanchecked />$wpbusdirman_formseloption</span>";
								}
							}
							break;
						case 5:
							$html .= "<p class=\"wpbdmp\"><label for=\"\">$wpbusdirman_field_label_name</label></p><select class=\"inselectmultiple" . $class_required . "\" id=\"wpbusdirman_field_label" . $wpbusdirman_field_label_association . "\" name=\"wpbusdirman_field_label".$wpbusdirman_field_label_association."[]\" multiple=\"multiple\">";
							$wpbusdirman_formselops=explode(",",$wpbusdirman_field_options);
							$wpbusdirman_formselop=array();
							for ($i=0;isset($wpbusdirman_formselops[$i]);++$i)
							{
								$wpbusdirman_formselop[]=$wpbusdirman_formselops[$i];
							}
							$wpbusdirmanmultivals=explode("\t",$wpbusdirman_field_value);
							$wpbusdirmanmultivalsarr=array();
							for ($a=0;isset($wpbusdirmanmultivals[$a]);++$a)
							{
								$wpbusdirmanmultivalsarr[]=trim($wpbusdirmanmultivals[$a]);
							}
							if($wpbusdirman_formselop)
							{
								foreach($wpbusdirman_formselop as $wpbusdirman_formseloption)
								{
									$wpbusdirman_formseloption=trim($wpbusdirman_formseloption);
									$html .= "<option ";
									if(in_array($wpbusdirman_formseloption,$wpbusdirmanmultivalsarr))
									{
										$html .= $wpbusdirmanselectedword;
									}
									$html .= "  value=\"$wpbusdirman_formseloption\">$wpbusdirman_formseloption</option>";
								}
							}
							$html .= "</select>";
							break;
						case 6:
							$html .= "<p class=\"wpbdmp\"><label for=\"wpbusdirman_field_label" . $wpbusdirman_field_label_association . "\">$wpbusdirman_field_label_name</label></p>";
							if($wpbusdirman_field_association == 'category')
							{
								$mywpbdmcatlist = get_terms($wpbdmposttypecategory, 'hide_empty=0');
								if($mywpbdmcatlist)
								{
									foreach($mywpbdmcatlist as $wpbusdirman_formseloption)
									{
										$mywpbdmcattermid=$wpbusdirman_formseloption->term_id;
										$mywpbdmcattermname=$wpbusdirman_formseloption->name;
										$html .= "<div id=\"wpbdmcheckboxclass\"><input type=\"checkbox\" class=\"" . $class_required . "\" id=\"wpbusdirman_field_label" . $wpbusdirman_field_label_association . "\" name=\"wpbusdirman_field_label".$wpbusdirman_field_label_association."[]\" value=\"$mywpbdmcattermid\"";

										if ( (is_array($wpbusdirman_field_value))
										&& (in_array($mywpbdmcattermid,$wpbusdirman_field_value)) )
										{
										$html .= $wpbusdirmancheckedword;
										}

										$html .= "/>" . $mywpbdmcattermname . "</div>";
									}
								}
							}
							else
							{
								$wpbusdirman_formselops=explode(",",$wpbusdirman_field_options);
								$wpbusdirman_formselop=array();
								for ($i=0;isset($wpbusdirman_formselops[$i]);++$i)
								{
									$wpbusdirman_formselop[]=$wpbusdirman_formselops[$i];
								}
								$wpbusdirmancboxvals=explode("\t",$wpbusdirman_field_value);
								$wpbusdirmanxboxvalsarr=array();
								for ($a=0;isset($wpbusdirmancboxvals[$a]);++$a)
								{
									$wpbusdirmanxboxvalsarr[]=trim($wpbusdirmancboxvals[$a]);
								}
								if($wpbusdirman_formselop)
								{
									foreach($wpbusdirman_formselop as $wpbusdirman_formseloption)
									{
										$wpbusdirman_formseloption=trim($wpbusdirman_formseloption);
										$html .= "<div id=\"wpbdmcheckboxclass\"><input type=\"checkbox\" class=\"" . $class_required . "\" name=\"wpbusdirman_field_label".$wpbusdirman_field_label_association."[]\" value=\"$wpbusdirman_formseloption\"";
										if(in_array($wpbusdirman_formseloption,$wpbusdirmanxboxvalsarr))
										{
											$html .= $wpbusdirmancheckedword;
										}
										$html .= "/>$wpbusdirman_formseloption</div>";
									}
								}

							}
							$html .= "<div style=\"clear:both;\"></div>";
							break;
					}
				}
			}
			$html .= "<p><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Submit","WPBDM") . "\" /></p></form>";
			$html .= "</div>";

		}
	}

	return $html;
}



function wpbusdirman_opsconfig_categories()
{
}

function wpbusdirmanui_homescreen ()
{
	$html = '';

	$html .= apply_filters('wpbdm_show-directory', null);

	return $html;
}

function wpbusdirmanui_directory_screen()
{
	global $wpbdmimagesurl,$wpbusdirman_imagesurl,$wpbusdirman_plugin_path,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbdmposttype;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_contact_errors=false;
 	$args=array('hide_empty' => 0);
 	$wpbusdirman_postcats=get_terms( $wpbdmposttypecategory, $args);
	$html = '';

	if(!isset($wpbusdirman_postcats) || empty($wpbusdirman_postcats))
	{
 		if(is_user_logged_in() && current_user_can('install_plugins'))
 		{
			$html .= "<p>" . __("There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory. ","WPBDM") . "</p>";
 		}
 		else
 		{
			$html .= "<p>" . __("There are currently no listings in the directory","WPBDM") . "</p>";
		}
	}
	else
	{
		$wpbusdirmanaction='';
		if(isset($_REQUEST['action'])
			&& !empty($_REQUEST['action']))
		{
			$wpbusdirmanaction=$_REQUEST['action'];
		}
		elseif(isset($_REQUEST['do'])
			&& !empty ($_REQUEST['do']))
		{
			$wpbusdirmanaction=$_REQUEST['do'];
		}

		if($wpbusdirmanaction == 'submitlisting')
		{
			$html .= apply_filters('wpbdm_show-add-listing-form', '1', '', 'new', '');
		}
		elseif($wpbusdirmanaction == 'viewlistings')
		{
			$html .= wpbusdirman_viewlistings();
		}
		elseif($wpbusdirmanaction == 'renewlisting')
		{
			$wpbdmgpid=wpbusdirman_gpid();
			$wpbusdirman_permalink=get_permalink($wpbdmgpid);
			$neworedit="renew";
			if(isset($_REQUEST['id'])
				&& !empty($_REQUEST['id']))
			{
				$wpbdmidtorenew=$_REQUEST['id'];
				$html .= wpbusdirman_renew_listing($wpbdmidtorenew,$wpbusdirman_permalink,$neworedit);
			}
		}
		elseif($wpbusdirmanaction == 'renewlisting_step_2')
		{
			$wpbusdirmanlistingtermlength=array();
			$wpbusdirmanfeeoption=array();

			if(isset($_REQUEST['wpbusdirmanlistingpostid'])
				&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
			{
				$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
			}
			if(isset($_REQUEST['whichfeeoption'])
				&& !empty($_REQUEST['whichfeeoption']))
			{
				$wpbusdirmanfeeoption=$_REQUEST['whichfeeoption'];
			}
			if(isset($_REQUEST['wpbusdirmanlistingtermlength'])
				&& !empty($_REQUEST['wpbusdirmanlistingtermlength']))
			{
				$wpbusdirmanlistingtermlength=$_REQUEST['wpbusdirmanlistingtermlength'];
			}
			if(isset($_REQUEST['wpbusdirmanpermalink'])
				&& !empty($_REQUEST['wpbusdirmanpermalink']))
			{
				$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
			}
			if(isset($_REQUEST['neworedit'])
				&& !empty($_REQUEST['neworedit']))
			{
				$neworedit=$_REQUEST['neworedit'];
			}


			/*$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption);

			$wpbusdirmannumimagesallowed=$myimagesallowedleft['imagesallowed'];
			$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];
			$totalexistingimages=$myimagesallowedleft['totalexisting'];*/

			$wpbusdirmanthisfeetopay=wpbusdirman_calculate_fee_to_pay($wpbusdirmanfeeoption);

			$wpbusdirman_my_renew_post = array();
			$wpbusdirman_my_renew_post['ID'] = $wpbusdirmanlistingpostid;
			$wpbusdirman_my_renew_post['post_status'] = 'pending';
			$html .= wp_update_post( $wpbusdirman_my_renew_post );

				if($wpbusdirmanthisfeetopay > 0)
				{
					$html .= wpbusdirman_load_payment_page($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirmanlistingtermlength,$wpbusdirmanthisfeetopay);
				}
				else
				{
					// There is no fee to pay so skip to end of process. Nothing left to do
					if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
					{
						$html .= "<p>" . __("Your submission has been received and is currently pending review","WPBDM") . "</p>";
					}
					elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'publish')
					{
						$html .= "<p>" . __("Your submission has been received and is currently published. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
					}
					else
					{
						$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p>";
						$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></form>";
					}
				}

		}
		elseif($wpbusdirmanaction == 'post')
		{
			$html .= apply_filters('wpbdm_process-form-post', null);
		}
		elseif($wpbusdirmanaction == 'editlisting')
		{
			if(isset($_REQUEST['wpbusdirmanlistingid'])
				&& !empty($_REQUEST['wpbusdirmanlistingid']))
			{
				$wpbdmlistingid=$_REQUEST['wpbusdirmanlistingid'];
			}
			$html .= apply_filters('wpbdm_show-add-listing-form', '', '', 'edit', $wpbdmlistingid);
		}
		elseif($wpbusdirmanaction == 'deletelisting')
		{
			$wpbusdirman_config_options=get_wpbusdirman_config_options();
			$wpbdmdraftortrash=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_47'];
			if(isset($_REQUEST['wpbusdirmanlistingid'])
				&& !empty($_REQUEST['wpbusdirmanlistingid']))
			{
				$wpbdmlistingid=$_REQUEST['wpbusdirmanlistingid'];
			}
			if(isset($wpbdmlistingid) && !empty($wpbdmlistingid))
			{
				$wpbusdirman_del_postarr = array();
				$wpbusdirman_del_postarr['ID'] = $wpbdmlistingid;
				$wpbusdirman_del_postarr['post_type'] = $wpbdmposttype;
				$wpbusdirman_del_postarr['post_status'] = $wpbdmdraftortrash;
				$html .= wp_update_post( $wpbusdirman_del_postarr );
				$html .= "<p>" . __("The listing has been deleted.","WPBDM") . "</p>";
				$html .= wpbusdirman_managelistings();
			}
			else
			{
				$html .= "<p>" . __("The system could not determine which listing you want to delete so nothing has been deleted.","WPBDM") . "</p>";
				$html .= wpbusdirman_managelistings();
			}
		}
		elseif($wpbusdirmanaction == 'upgradetostickylisting')
		{
			if(isset($_REQUEST['wpbusdirmanlistingid'])
				&& !empty($_REQUEST['wpbusdirmanlistingid']))
			{
				$wpbdmlistingid=$_REQUEST['wpbusdirmanlistingid'];
			}
			$html .= wpbusdirman_upgradetosticky($wpbdmlistingid);
		}
		elseif($wpbusdirmanaction == 'sendcontactmessage')
		{
			$commentauthormessage='';
			$commentauthorname='';
			$commentauthoremail='';
			$commentauthorwebsite='';
			if(isset($_REQUEST['wpbusdirmanlistingpostid'])
				&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
			{
				$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
			}
			if(isset($_REQUEST['wpbusdirmanpermalink'])
				&& !empty($_REQUEST['wpbusdirmanpermalink']))
			{
				$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
			}
			if(isset($_REQUEST['commentauthormessage'])
				&& !empty($_REQUEST['commentauthormessage']))
			{
				$commentauthormessage=$_REQUEST['commentauthormessage'];
			}
			global $post, $current_user, $user_identity;
			global $wpbusdirman_contact_form_values, $wpbusdirman_contact_form_errors;
			$wpbusdirman_contact_form_errors = '';
			if(is_user_logged_in())
			{
				$commentauthorname=$user_identity;
				$commentauthoremail=$current_user->data->user_email;
				$commentauthorwebsite=$current_user->data->user_url;
			}
			else
			{
				if(isset($_REQUEST['commentauthorname'])
					&& !empty($_REQUEST['commentauthorname']))
				{
					$commentauthorname=htmlspecialchars( $_REQUEST['commentauthorname'] );
				}
				if(isset($_REQUEST['commentauthoremail'])
					&& !empty($_REQUEST['commentauthoremail']))
				{
					$commentauthoremail=$_REQUEST['commentauthoremail'];
				}
				if(isset($_REQUEST['commentauthorwebsite'])
					&& !empty($_REQUEST['commentauthorwebsite']))
				{
					$commentauthorwebsite=$_REQUEST['commentauthorwebsite'];
				}

			}
			if ( !isset($commentauthorname)
				|| empty($commentauthorname) )
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("Please enter your name.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			if(strlen($commentauthorname) < 3)
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("Name needs to be at least 3 characters in length to be considered valid.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			if ( !isset($commentauthoremail)
				|| empty($commentauthoremail) )
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("Please enter your email.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			if ( !wpbusdirman_isValidEmailAddress($commentauthoremail) )
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("Please enter a valid email.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			if( isset($commentauthorwebsite)
				&& !empty($commentauthorwebsite)
				&& !(wpbusdirman_isValidURL($commentauthorwebsite)) )
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("Please enter a valid URL.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			$commentauthormessage = stripslashes($commentauthormessage);
			$commentauthormessage = trim(wp_kses( $commentauthormessage, array() ));
			if ( !isset($commentauthormessage )
				|| empty($commentauthormessage))
			{
				$wpbusdirman_contact_errors=true;
				$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_contact_form_errors.=__("You did not enter a message.","WPBDM");
				$wpbusdirman_contact_form_errors.="</li>";
			}
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_30'] == "yes")
			{
				$privatekey = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_29'];
				if(isset($privatekey) && !empty($privatekey))
				{
					require_once('recaptcha/recaptchalib.php');
					$resp = recaptcha_check_answer ($privatekey,
					$_SERVER["REMOTE_ADDR"],
					$_POST["recaptcha_challenge_field"],
					$_POST["recaptcha_response_field"]);
					if (!$resp->is_valid)
					{
						$wpbusdirman_contact_errors=true;
						$wpbusdirman_contact_form_errors.="<li class=\"wpbusdirmanerroralert\">";
						$wpbusdirman_contact_form_errors.=__("The reCAPTCHA wasn't entered correctly: ","WPBDM");
						$wpbusdirman_contact_form_errors.=" . $resp->error . ";
						$wpbusdirman_contact_form_errors.="</li>";
					}
				}
			}
			if($wpbusdirman_contact_errors)
			{
				$html .= wpbusdirman_contactform($wpbusdirmanpermalink,$wpbusdirmanlistingpostid,$commentauthorname,$commentauthoremail,$commentauthorwebsite,$commentauthormessage,$wpbusdirman_contact_form_errors);
			}
			else
			{
				$post_author = get_userdata( $post->post_author );
				$headers =	"MIME-Version: 1.0\n" .
						"From: $commentauthorname <$commentauthoremail>\n" .
						"Reply-To: $commentauthoremail\n" .
						"Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
				$subject = "[" . get_option( 'blogname' ) . "] " . wp_kses( get_the_title($wpbusdirmanlistingpostid), array() );
				$wpbdmsendtoemail=wpbusdirman_get_the_business_email($wpbusdirmanlistingpostid);
				if(!isset($wpbdmsendtoemail) || empty($wpbdmsendtoemail))
				{
					$wpbdmsendtoemail=$post_author->user_email;
				}
				$time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );
				$message = "Name: $commentauthorname
				Email: $commentauthoremail
				Website: $commentauthorwebsite

				$commentauthormessage

				Time: $time

				";
				if(wp_mail( $wpbdmsendtoemail, $subject, $message, $headers ))
				{
					$html .= "<p>" . __("Your message has been sent","WPBDM") . "</p>";
				}
				else
				{
					$html .= "<p>" . __("There was a problem encountered. Your message has not been sent","WPBDM") . "</p>";
				}
			}
		}
		elseif($wpbusdirmanaction == 'deleteimage')
		{
			$wpbdmlistingid='';
			$wpbdmimagetodelete='';
			$wpbusdirmannumimgsallowed='';
			$wpbusdirmannumimgsleft='';
			$wpbusdirmanlistingtermlength=array();
			$wpbusdirmanpermalink='';
			$neworedit='';
			if(isset($_REQUEST['wpbusdirmanlistingpostid'])
				&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
			{
				$wpbdmlistingid=$_REQUEST['wpbusdirmanlistingpostid'];
			}
			if(isset($_REQUEST['wpbusdirmanimagetodelete']) && !empty($_REQUEST['wpbusdirmanimagetodelete']))
			{
				$wpbdmimagetodelete=$_REQUEST['wpbusdirmanimagetodelete'];
			}
			if(isset($_REQUEST['wpbusdirmannumimgsallowed']) && !empty($_REQUEST['wpbusdirmannumimgsallowed']))
			{
				$wpbusdirmannumimgsallowed=$_REQUEST['wpbusdirmannumimgsallowed'];
			}
			if(isset($_REQUEST['wpbusdirmannumimgsleft']) && !empty($_REQUEST['wpbusdirmannumimgsleft']))
			{
				$wpbusdirmannumimgsleft=$_REQUEST['wpbusdirmannumimgsleft'];
			}
			if(isset($_REQUEST['wpbusdirmanlistingtermlength']) && !empty($_REQUEST['wpbusdirmanlistingtermlength']))
			{
				$wpbusdirmanlistingtermlength=$_REQUEST['wpbusdirmanlistingtermlength'];
			}
			if(isset($_REQUEST['wpbusdirmanpermalink']) && !empty($_REQUEST['wpbusdirmanpermalink']))
			{
				$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
			}
			if(isset($_REQUEST['neworedit']) && !empty($_REQUEST['neworedit']))
			{
				$neworedit=$_REQUEST['neworedit'];
			}
			$html .= wpbusdirman_deleteimage($imagetodelete=$wpbdmimagetodelete,$wpbdmlistingid,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$wpbusdirmanlistingtermlength,$wpbusdirmanpermalink,$neworedit);
		}
		elseif($wpbusdirmanaction == 'payment_step_1')
		{
			$wpbusdirmanfeeoptions=array();

			if(isset($_REQUEST['wpbusdirmanlistingpostid'])
				&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
			{
				$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
			}
			if(isset($_REQUEST['inpost_category'])
				&& !empty($_REQUEST['inpost_category']))
			{
				$uscats=$_REQUEST['inpost_category'];
			}

			foreach($uscats as $uscat)
			{
					if(isset($_REQUEST['whichfeeoption_'.$uscat])
						&& !empty($_REQUEST['whichfeeoption_'.$uscat]))
					{
						$wpbusdirmanfeeoption=$_REQUEST['whichfeeoption_'.$uscat];
						$wpbusdirmanfeeoptions[]=$wpbusdirmanfeeoption;
						$myfeecatobj[]=array('catid' => $uscat, 'feeopid' => $wpbusdirmanfeeoption);
					}
			} // End foreach uscats


			foreach($myfeecatobj as $fcobj)
			{
				$cat=$fcobj['catid'];
				$feeid=$fcobj['feeopid'];

				$listingincr=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_increment_'.$feeid);

				$catdur=$cat;
				$catdur.="_";
				$catdur.=$listingincr;
				$wpbusdirmanlistingtermlength[]=$catdur;

				$mycatobj[]=array('listingcat' => $uscat,'listingduration' => $listingincr);
			}


			if(isset($_REQUEST['wpbusdirmanpermalink'])
				&& !empty($_REQUEST['wpbusdirmanpermalink']))
			{
				$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
			}
			if(isset($_REQUEST['neworedit'])
				&& !empty($_REQUEST['neworedit']))
			{
				$neworedit=$_REQUEST['neworedit'];
			}

			$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$wpbusdirmanfeeoptions);

			$wpbusdirmannumimagesallowed=$myimagesallowedleft['imagesallowed'];
			$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];
			$totalexistingimages=$myimagesallowedleft['totalexisting'];


			if($wpbusdirmanlistingtermlength)
			{
				foreach($wpbusdirmanlistingtermlength as $catdur)
				{
					$existingtermlengths=get_post_meta($wpbusdirmanlistingpostid, "termlength", false);

						if(!in_array($catdur,$existingtermlengths))
						{
								add_post_meta($wpbusdirmanlistingpostid, "termlength", $catdur, false);
						}
				}
			}

			if($wpbusdirmanfeeoptions)
			{
				foreach($wpbusdirmanfeeoptions as $feeopid)
				{
					$wpbusdirmanlistingcost=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$feeopid);
					add_post_meta($wpbusdirmanlistingpostid, "costoflisting", $wpbusdirmanlistingcost, false) or update_post_meta($wpbusdirmanlistingpostid, "costoflisting", $wpbusdirmanlistingcost);
					add_post_meta($wpbusdirmanlistingpostid, "listingfeeid", $feeopid, false) or update_post_meta($wpbusdirmanlistingpostid, "costoflisting", $feeopid);
				}
			}

			$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirmanlistingpostid,$wpbusdirmanpermalink,$wpbusdirmannumimagesallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror='',$neworedit,$wpbusdirmanfeeoptions);

		}
		elseif($wpbusdirmanaction == 'payment_step_2')
		{
			$wpbusdirmanfeeoptions=array();
			$wpbusdirmanlistingtermlength=array();

			if(isset($_REQUEST['wpbusdirmanlistingpostid'])
				&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
			{
				$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
			}
			if(isset($_REQUEST['wpbusdirmanfeeoption'])
				&& !empty($_REQUEST['wpbusdirmanfeeoption']))
			{
				$wpbusdirmanfeeoption=$_REQUEST['wpbusdirmanfeeoption'];
			}elseif(isset($_REQUEST['whichfeeoption'])
				&& !empty($_REQUEST['whichfeeoption'])){$wpbusdirmanfeeoption=$_REQUEST['whichfeeoption'];}

			if(isset($_REQUEST['wpbusdirmanlistingtermlength'])
				&& !empty($_REQUEST['wpbusdirmanlistingtermlength']))
			{
				$wpbusdirmanlistingtermlength=$_REQUEST['wpbusdirmanlistingtermlength'];
			}
			if(isset($_REQUEST['wpbusdirmanpermalink'])
				&& !empty($_REQUEST['wpbusdirmanpermalink']))
			{
				$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
			}
			if(isset($_REQUEST['neworedit'])
				&& !empty($_REQUEST['neworedit']))
			{
				$neworedit=$_REQUEST['neworedit'];
			}

			$wpbusdirmancostoflisting=wpbusdirman_calculate_fee_to_pay($wpbusdirmanfeeoption);

				if($wpbusdirmancostoflisting > 0)
				{
					$html .= wpbusdirman_load_payment_page($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirmanlistingtermlength,$wpbusdirmancostoflisting);
				}
				else
				{
					// There is no fee to pay so skip to end of process. Nothing left to do
					if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
					{
						$html .= "<p>" . __("Your submission has been received and is currently pending review","WPBDM") . "</p>";
					}
					elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'publish')
					{
						$html .= "<p>" . __("Your submission has been received and is currently published. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
					}
					else
					{
						$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p>";
						$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></form>";
					}
				}
		}
		elseif($wpbusdirmanaction == 'wpbusdirmanuploadfile')
		{
			$html .= wpbusdirman_doupload();
		}
		else
		{
			global $wpbusdirman_gpid,$permalinkstructure;
			$excludebuttons=1;
			$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
			$querysymbol="?";
			if(!isset($permalinkstructure)
				|| empty($permalinkstructure))
			{
				$querysymbol="&amp";
			}
			if(file_exists(get_template_directory() . '/single/wpbusdirman-index-categories.php'))
			{
				include get_template_directory() . '/single/wpbusdirman-index-categories.php';
			}
			elseif(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-index-categories.php'))
			{
				include get_stylesheet_directory() . '/single/wpbusdirman-index-categories.php';
			}
			elseif(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-categories.php'))
			{
				include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-categories.php';
			}
			else
			{
				include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-categories.php';
			}
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_44'] == "yes")
			{

				if(file_exists(get_template_directory() . '/single/wpbusdirman-index-listings.php'))
				{
					include get_template_directory() . '/single/wpbusdirman-index-listings.php';
				}
				elseif(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php'))
				{
					include get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php';
				}
				elseif(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php'))
				{
					include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
				}
				else
				{
					include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
				}
			}
		}
	}

	return $html;
}

function wpbusdirman_get_the_business_email($wpbusdirmanlistingpostid)
{

	global $wpbusdirmanconfigoptionsprefix;

	$wpbdm_the_email='';
	wp_reset_query();
	$mypost=get_post($wpbusdirmanlistingpostid);
	$thepostid=$mypost->ID;
	$wpbdm_the_emailsarr=array();

	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');

	if($wpbusdirman_field_vals)
	{
		foreach($wpbusdirman_field_vals as $wpbusdirman_field_val):


			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);


			if($wpbusdirman_field_association == 'meta')
			{
				$wpbdm_meta_fields[]=$wpbusdirman_field_label;
			}

		endforeach;


		foreach($wpbdm_meta_fields as $wpbdm_meta_field)
		{

			$wpbdm_field_value=get_post_meta($thepostid, $wpbdm_meta_field, true);

				if(isset($wpbdm_field_value) && !empty($wpbdm_field_value) && (wpbusdirman_isValidEmailAddress($wpbdm_field_value)))
				{
					$wpbdm_the_emailsarr[]=$wpbdm_field_value;
				}

		}

	}

	$wpbdm_the_email=$wpbdm_the_emailsarr[0];
	return $wpbdm_the_email;
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

function wpbusdirman_do_post()
{
	global $wpbusdirman_gpid,$wpbdmposttype,$wpbdmposttypecategory,$wpbdmposttypetags,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
	$html = '';
	$makeactive='';
	$neworedit='';
	$wpbdmlistingid='';
	$mycatobj=array();

	if(isset($_REQUEST['formmode'])
		&& ($_REQUEST['formmode'] == -1))
	{
		$makeactive=$_REQUEST['formmode'];
	}
	if(isset($_REQUEST['neworedit'])
		&& !empty($_REQUEST['neworedit']))
	{
		$neworedit=$_REQUEST['neworedit'];
	}
	if(isset($_REQUEST['wpbdmlistingid'])
		&& !empty($_REQUEST['wpbdmlistingid']))
	{
		$wpbdmlistingid=$_REQUEST['wpbdmlistingid'];
	}
	if($makeactive == -1)
	{
		$html .= "<h3 style=\"padding:10px;\">" . __("Information Not Saved","WPBDM") . "</h3><p>" . __("You are trying to submit the form in preview mode. You cannot save while in preview mode","WPBDM") . " <a href=\"javascript:history.go(-1)\">" . __("Go Back","WPBDM") . "</a></p>";
	}
	else
	{
		if (!(is_user_logged_in()) )
		{
			if($wpbusdirman_field_vals)
			{
				foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
				{
					$wpbusdirman_validation_op=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_val);
					if($wpbusdirman_validation_op == 'email')
					{
						$wpbusdirman_email_numval=$wpbusdirman_field_val;
					}
					$wpbusdirman_association_op=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
					if($wpbusdirman_association_op == 'title')
					{
						$wpbusdirman_title_numval=$wpbusdirman_field_val;
					}
				}
			}
			$wpbusdirman_email_field=$_REQUEST['wpbusdirman_field_label_meta'.$wpbusdirman_email_numval];
			$guestrand=$wpbusdirman_user_pass=wpbusdirman_generatePassword(5,2);
			$wpbusdirman_display_name='Guest';
			$wpbusdirman_display_name.=" $guestrand";
			$wpbusdirman_user_login='guest_';
			$wpbusdirman_user_login.=" $guestrand";
			if(email_exists($wpbusdirman_email_field))
			{
				$wpbusdirman_UID_get=get_user_by_email($wpbusdirman_email_field);
				$wpbusdirman_UID=$wpbusdirman_UID_get->ID;
			}
			else
			{
				$wpbusdirman_user_pass=wpbusdirman_generatePassword(7,2);
				$wpbusdirman_UID=wp_insert_user(array('display_name'=>$wpbusdirman_display_name,'user_login'=>$wpbusdirman_user_login,'user_email'=>$wpbusdirman_email_field,'user_pass'=>$wpbusdirman_user_pass));
			}
		}
		elseif(is_user_logged_in())
		{
			global $current_user;
			get_currentuserinfo();
			$wpbusdirman_UID=$current_user->ID;
		}

		if(!isset($wpbusdirman_UID) || empty($wpbusdirman_UID))
		{
			$wpbusdirman_UID=1;
		}
		$wpbusdirmanposterrors = wpbusdirman_validate_data();
		if($wpbusdirmanposterrors)
		{
			$html .= apply_filters('wpbdm_show-add-listing-form', $makeactive,$wpbusdirmanposterrors,$neworedit,$wpbdmlistingid);
		}
		else
		{
			$post_title=wpbusdirman_filterinput($_REQUEST['wpbusdirman_field_label_title']);
			$post_excerpt=wpbusdirman_filterinput($_REQUEST['wpbusdirman_field_label_excerpt']);
			$post_content=wpbusdirman_filterinput($_REQUEST['wpbusdirman_field_label_description']);
			$post_tags=wpbusdirman_filterinput($_REQUEST['wpbusdirman_field_label_tags']);
			global $wpbusdirman_gpid,$permalinkstructure;
			$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
			$querysymbol="?";
			if(!isset($permalinkstructure)
				|| empty($permalinkstructure))
			{
				$querysymbol="&amp";
			}
			if(isset($_REQUEST['cat'])
				&& !empty($_REQUEST['cat']))
			{
				$post_category_item= $_REQUEST['cat'];
				$inpost_category=array("$post_category_item");
			}
			elseif(isset($_REQUEST['wpbusdirman_field_label_category'])
				&& !empty($_REQUEST['wpbusdirman_field_label_category']))
			{
				$inpost_category=$_REQUEST['wpbusdirman_field_label_category'];
			} else {
                $inpost_category=array();
            }
			if(isset($neworedit)
				&& ($neworedit == 'edit'))
			{
				$post_status=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_19'];
				if($post_status == 'pending2')
				{
					$post_status="pending";
				}
				elseif($post_status == 'publish2')
				{
					$post_status="publish";
				}
			}
			else
			{
				$post_status=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'];
			}
			if(!isset($post_status)
				|| empty($post_status))
			{
				$post_status='pending';
			}
			if ( empty($inpost_category)
				|| 0 == count($inpost_category)
				|| !is_array($inpost_category) )
			{
				$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');
				if($wpbusdirman_myterms)
				{
					foreach($wpbusdirman_myterms as $wpbusdirman_myterm)
					{
						$wpbusdirman_postcatitems[]=$wpbusdirman_myterm->term_id;
					}
				}
				$post_category=$wpbusdirman_postcatitems[0];
			}
			else
			{
				$post_category = $inpost_category;
			}
			$post_tag=explode(",",$post_tags);
			$tags_input=array();
			for ($i=0;isset($post_tag[$i]);++$i)
			{
				$tags_input[]=$post_tag[$i];
			}
			$wpbusdirman_postID = wp_insert_post( array(
				'post_author'	=> $wpbusdirman_UID,
				'post_title'	=> $post_title,
				'post_content'	=> $post_content,
				'post_excerpt'	=> $post_excerpt,
				'post_status' 	=> $post_status,
				'post_type' 	=> $wpbdmposttype,
				'ID'	=> $wpbdmlistingid
			));
			wp_set_post_terms( $wpbusdirman_postID , $tags_input, $wpbdmposttypetags, false );
			wp_set_post_terms( $wpbusdirman_postID , $post_category, $wpbdmposttypecategory, false );
			$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
			if($wpbusdirman_field_vals)
			{
				foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
				{
					$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
					$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
					$wpbusdirman_field_type=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_val);
					if($wpbusdirman_field_association == 'meta')
					{
						$wpbusdirman_fieldmeta_set="wpbusdirman_field_label_meta$wpbusdirman_field_val";
						if($wpbusdirman_field_type == 6)
						{
							$wpbusdirman_the_fieldmeta=$_REQUEST[$wpbusdirman_fieldmeta_set];
							$wpbusdirmanfieldmeta='';
							if($wpbusdirman_the_fieldmeta)
							{
								foreach($wpbusdirman_the_fieldmeta as $wpbusdirman_thefieldmeta)
								{
									$wpbusdirmanfieldmeta.="$wpbusdirman_thefieldmeta\t";
								}
							}
						}
						elseif($wpbusdirman_field_type == 5)
						{
							$wpbusdirman_the_fieldmeta=$_REQUEST[$wpbusdirman_fieldmeta_set];
							$wpbusdirmanfieldmeta='';
							if (count($wpbusdirman_the_fieldmeta) > 0)
							{
								for ($i=0;$i<count($wpbusdirman_the_fieldmeta);$i++)
								{
									$wpbusdirmanfieldmeta.="$wpbusdirman_the_fieldmeta[$i]\t";
								}
							}
						}
						else
						{
							$wpbusdirmanfieldmeta=$_REQUEST[$wpbusdirman_fieldmeta_set];
						}
						add_post_meta($wpbusdirman_postID, $wpbusdirman_field_label, $wpbusdirmanfieldmeta, true) or update_post_meta($wpbusdirman_postID, $wpbusdirman_field_label, $wpbusdirmanfieldmeta);

					}
				}

						if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "no")
						{
							if(isset($neworedit)
								&& (!($neworedit == 'edit')) )
							{
								$wpbusdirmantermduration=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];
								foreach($inpost_category as $mypostcategory)
								{
									$wpbusdirmanlengthofterm=$mypostcategory;
									$wpbusdirmanlengthofterm.="_";
									$wpbusdirmanlengthofterm.=$wpbusdirmantermduration;

									add_post_meta($wpbusdirman_postID, "termlength", $wpbusdirmanlengthofterm, false) or update_post_meta($wpbusdirman_postID, "termlength", $wpbusdirmanlengthofterm);
								}
							}
						}

			}

			global $wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule;

			if(!($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "no"))
			{
				/* Payments are activated */

				if(( $wpbusdirman_haspaypalmodule == 1) || ($wpbusdirman_hastwocheckoutmodule == 1) || ($wpbusdirman_hasgooglecheckoutmodule == 1))
				{
					if(!($neworedit == 'edit'))
					{
						/* This is not an edit so payment options need to be setup */


						$html .= "<h2>" . __("Step 2","WPBDM") . "</h2>";
						$wpbusdirman_fee_to_pay_li=wpbusdirman_feepay_configure($inpost_category);

						if(isset($wpbusdirman_fee_to_pay_li) && !empty($wpbusdirman_fee_to_pay_li))
						{
							/* There is a fee to be paid so proceed with setting up the fee selection page to display to the user */

							global $wpbusdirman_gpid,$permalinkstructure;
							$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
							$wpbusdirman_fee_to_pay="<div id=\"wpbusdirmanpaymentoptionslist\">";
							$wpbusdirman_fee_to_pay.=$wpbusdirman_fee_to_pay_li;
							$wpbusdirman_fee_to_pay.="</div>";
							$neworedit='new';
							$html .= "<label>" . __("Select Listing Payment Option","WPBDM") . "</label><br /><p>";
							$usercatstotal=count($inpost_category);
							if($usercatstotal > 1){
							$html .="<p>";
							$html .= __("You have selected more than one category. Each category you to which you elect to submit your listing incurs a separate fee.", "WPBDM");
							if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
							{
								$html .=__(" The number of images attached to your listing will be set according to option you choose that has the most images. So if for one category you chose an option with 2 images but for another category you chose an option with 4 images your listing will be allotted 4 image slots", "WPBDM");
							}
							$html .="</p>";
							}
							$html .= "<form method=\"post\" action=\"$wpbusdirman_permalink\">";
							$html .= "<input type=\"hidden\" name=\"action\" value=\"payment_step_1\" />";
							foreach ($inpost_category as $key => $value)
							{
							 $html.='<input type=hidden name="inpost_category[]" value="'.htmlspecialchars($value).'"/>';
							}
							$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirman_postID\" />";
							$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirman_permalink\" />";
							$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\" />";
							$html .= $wpbusdirman_fee_to_pay;
							$html .= "<br /><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Next","WPBDM") . "\" /></form></p>";
						}
						else
						{

							/* wpbusdirman_fee_to_pay_li value is missing so move on and setup the image upload form to display to the user */

							if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
							{
								$wpbusdirmanlistingtermlength=array();
								if(!isset($wpbusdirmanlistingtermlength) || empty($wpbusdirmanlistingtermlength))
								{
									$wpbusdirmanlistingtermlength=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];
								}

								$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirman_postID,$wpbusdirmanfeeoption='');

								$wpbusdirmannumimgsallowed=$myimagesallowedleft['imagesallowed'];
								$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];


									foreach($inpost_category as $mycatid)
									{
											$listingincr=$mycatid;
											$listingincr="_";
											$listingincr=$wpbusdirmanlistingtermlength;

											$mycatobj[]=array('listingcat' => $mycatid,'listingduration' => $listingincr);

									} // End foreach wpbusdirmanlistingtermlength

								$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirman_postID,$wpbusdirman_permalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror='',$neworedit,$whichfeeoption='');

							}
							else
							{
								$html .= "<h3 style=\"padding:10px;\">" . __("Submission received","WPBDM") . "</h3><p>" . __("Your submission has been received.","WPBDM") .  "</p>";
							}
						}
					}
					else
					{
						if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
						{
							$html .= "<h3>" . __("Step 2","WPBDM") . "</h3>";

							$wpbusdirmanlistingtermlength=get_post_meta($wpbusdirman_postID, "termlength", $single=false);

							if($wpbusdirmanlistingtermlength)
							{

								foreach($wpbusdirmanlistingtermlength as $catdur)
								{
									// potential issue for users with listings submitted via pre 1.9.3 versions because termlength is saved as single digit value whereas in 1.9.3+ term length saves as XXX_xx where XXX is the category ID and xx is the term duration with _ acting as a delimiter

									$mycatdurvals=explode("_",$catdur);
									$mycatid=$mycatdurvals[0];
									$listingincr=$mycatdurvals[1];

										$mycatobj[]=array('listingcat' => $mycatid,'listingduration' => $listingincr);

								} // End foreach wpbusdirmanlistingtermlength
							}
							else
							{
								$wpbusdirmanlistingtermlength=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];

								foreach($inpost_category as $uscat)
								{
									$mycatobj[]=array('listingcat' => $uscat,'listingduration' => $wpbusdirmanlistingtermlength);
								}
							}

								$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirman_postID,$wpbusdirmanfeeoption='');

								$wpbusdirmannumimgsallowed=$myimagesallowedleft['imagesallowed'];
								$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

							$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirman_postID,$wpbusdirman_permalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror='',$neworedit,$whichfeeoption='');

						}
						else
						{
							$html .= "<h3 style=\"padding:10px;\">" . __("Submission received","WPBDM") . "</h3><p>" . __("Your submission has been received.","WPBDM") . "</p>";
						}
					}
				}
				else
				{
					if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
					{
						$html .= "<h3>" . __("Step 2","WPBDM") . "</h3>";
							$wpbusdirmanlistingtermlength=get_post_meta($wpbusdirman_postID, "termlength", $single=false);

							if($wpbusdirmanlistingtermlength)
							{

								foreach($wpbusdirmanlistingtermlength as $catdur)
								{
									// potential issue for users with listings submitted via pre 1.9.3 versions because termlength is saved as single digit value whereas in 1.9.3+ term length saves as XXX_xx where XXX is the category ID and xx is the term duration with _ acting as a delimiter

									$mycatdurvals=explode("_",$catdur);
									$mycatid=$mycatdurvals[0];
									$listingincr=$mycatdurvals[1];

										$mycatobj[]=array('listingcat' => $mycatid,'listingduration' => $listingincr);

								} // End foreach wpbusdirmanlistingtermlength
							}
							else
							{
								$wpbusdirmanlistingtermlength=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];

								foreach($inpost_category as $uscat)
								{
									$mycatobj[]=array('listingcat' => $uscat,'listingduration' => $wpbusdirmanlistingtermlength);
								}
							}

								$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirman_postID,$wpbusdirmanfeeoption='');

								$wpbusdirmannumimgsallowed=$myimagesallowedleft['imagesallowed'];
								$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

								$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirman_postID,$wpbusdirman_permalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror,$neworedit,$whichfeeoption);

					}
					else
					{
						$html .= "<h3 style=\"padding:10px;\">" . __("Submission received","WPBDM") . "</h3><p>" . __("Your submission has been received.","WPBDM") . "</p>";
					}
				}
			}
			else
			{
				/* Payments are not activated */

				if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
				{
					$html .= "<h3>" . __("Step 2","WPBDM") . "</h3>";
					if(isset($neworedit)
						&& !empty($neworedit)
						&& ($neworedit == 'edit'))
					{
						$wpbusdirmanlistingtermlength=get_post_meta($wpbusdirman_postID, "termlength", $single=false);

							if($wpbusdirmanlistingtermlength)
							{

								foreach($wpbusdirmanlistingtermlength as $catdur)
								{
									// potential issue for users with listings submitted via pre 1.9.3 versions because termlength is saved as single digit value whereas in 1.9.3+ term length saves as XXX_xx where XXX is the category ID and xx is the term duration with _ acting as a delimiter

									$mycatdurvals=explode("_",$catdur);
									$mycatid=$mycatdurvals[0];
									$listingincr=$mycatdurvals[1];

										$mycatobj[]=array('listingcat' => $mycatid,'listingduration' => $listingincr);

								} // End foreach wpbusdirmanlistingtermlength
							}
							else
							{
								$wpbusdirmanlistingtermlength=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];

								foreach($inpost_category as $uscat)
								{
									$mycatobj[]=array('listingcat' => $uscat,'listingduration' => $wpbusdirmanlistingtermlength);
								}
							}


							$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirman_postID,$wpbusdirmanfeeoption='');

							$wpbusdirmannumimgsallowed=$myimagesallowedleft['imagesallowed'];
							$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

							$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirman_postID,$wpbusdirman_permalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror='',$neworedit,$whichfeeoption='');

					}
					else
					{
						$wpbusdirmanlistingtermlength=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_18'];

						foreach($inpost_category as $uscat)
						{
								$mycatobj[]=array('listingcat' => $uscat,'listingduration' => $wpbusdirmanlistingtermlength);
						}

							$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirman_postID,$wpbusdirmanfeeoption='');

							$wpbusdirmannumimgsallowed=$myimagesallowedleft['imagesallowed'];
							$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

							$html .= apply_filters('wpbdm_show-image-upload-form', $wpbusdirman_postID,$wpbusdirman_permalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror='',$neworedit,$whichfeeoption='');

					}
				}
				else
				{
					$html .= "<h3 style=\"padding:10px;\">" . __("Submission received","WPBDM") . "</h3><p>" . __("Your submission has been received.","WPBDM") . "</p>";
				}
			}
		}
	}

	return $html;
}

function wpbusdirman_image_upload_form($wpbusdirmanlistingpostid, $wpbusdirmanpermalink, $wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft, $mycatobj, $wpbusdirmanuerror, $neworedit, $whichfeeoption)
{
	global $wpbdmimagesurl,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

		$mycatduration=array();
		$feeoptionsarr=array();

		if($mycatobj && is_array($mycatobj)){

			foreach($mycatobj as $mycatobject)
			{
				$catduration=$mycatobject['listingcat'];
				$catduration.="_";
				$catduration.=$mycatobject['listingduration'];
				$mycatduration[]=$catduration;

			}
		}

		if($whichfeeoption)
		{
			foreach($whichfeeoption as $feeoption)
			{
				$feeoptionsarr[]=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$feeoption);
			}
		}

		$feepayval=array_sum($feeoptionsarr);

	if(isset($wpbusdirmanuerror) && !empty($wpbusdirmanuerror))
	{
		$html .= "<p>";
		foreach($wpbusdirmanuerror as $wpbusdirmanuerror)
		{
			$html .= $wpbusdirmanuerror;
		}
		$html .= "</p>";
	}
	if(isset($wpbusdirmanuerror)
		&& !empty($wpbusdirmanuerror))
	{
		$html .= "<p class=\"wpbusdirmaerroralert\">$wpbusdirmanuerror</p>";
	}


	$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$whichfeeoption);
	//print_r($myimagesallowedleft);die;

	$wpbusdirmannumimagesallowed=$myimagesallowedleft['imagesallowed'];
	$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];
	$totalexistingimages=$myimagesallowedleft['totalexisting'];

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
	{

				if( ($totalexistingimages > 0) && ( $wpbusdirmannumimgsleft <= 0) )
				{
					$wpbusdirmanimagesinpost=get_post_meta($wpbusdirmanlistingpostid, "image", $single = false);

					$html .= "<p>" . __("It appears you do not have the ability to upload additional images at this time.","WPBDM") . "</p>";
					if(get_post_meta($wpbusdirmanlistingpostid, "image", $single = true))
					{
						$html .= "<p>" . __("You can manage your current images below","WPBDM") . "</p>";
						if($wpbusdirmanimagesinpost)
						{
							foreach($wpbusdirmanimagesinpost as $wpbusdirmanimage)
							{
								$html .= "<div style=\"float:left;margin-right:10px;margin-bottom:10px;\"><img src=\"$wpbdmimagesurl/thumbnails/$wpbusdirmanimage\" border=\"0\" height=\"100\" alt=\"$wpbusdirmanimage\"><br/>";
								$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
								$html .= "<input type=\"hidden\" name=\"action\" value=\"deleteimage\" />";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\" />";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanimagetodelete\" value=\"$wpbusdirmanimage\" />";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsallowed\" value=\"$wpbusdirmannumimgsallowed\" />";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsleft\" value=\"$wpbusdirmannumimgsleft\" />";
								//$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength\" value=\"$wpbusdirmanlistingtermlength\" />";
								foreach ($mycatduration as $key => $value)
								{
									$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength[]\" value=\"$value\" />";
								}

								$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\" />";
								$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\" />";
								//$html .= "<input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\" />";
								if($whichfeeoption)
								{
									foreach ($whichfeeoption as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
									}
								}
								$html .= "<input type=\"submit\" class=\"deletelistingbutton\" value=\"" . __("Delete Image","WPBDM") . "\" /></form></div>";
							}
						}
						$html .= "<div style=\"clear:both;\"></div>";
						if(isset($neworedit)
							&& !empty($neworedit)
							&& ($neworedit == 'edit'))
						{
							$html .= "<p>" . __("If you are not updating your images you can click the exit now button.","WPBDM") . "</p>";
							$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
							$html .= "<p>";
							$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\"></p></form>";
						}
					}
					else
					{
						if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
						{
							$html .= "<p>" . __("Your submission has been received and is currently pending review","WPBDM") . "</p>";
						}
						elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1']=='publish')
						{
							$html .= "<p>" . __("Your submission has been received and is currently published. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
						}
						$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p>";
						$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\"></form>";
					}
				}
				else
				{
					$html .= "<p>If you would like to include an image with your listing please upload the image of your choice. You are allowed [$wpbusdirmannumimgsallowed] images and have [$wpbusdirmannumimgsleft] image slots still available.</p>";
					$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\" ENCTYPE=\"Multipart/form-data\">";
					$html .= "<input type=\"hidden\" name=\"action\" value=\"wpbusdirmanuploadfile\" />";
					$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\" />";
					$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsallowed\" value=\"$wpbusdirmannumimgsallowed\" />";
					$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsleft\" value=\"$wpbusdirmannumimgsleft\" />";

						foreach ($mycatduration as $key => $value)
						{
							$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength[]\" value=\"$value\" />";
						}
					$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\" />";
					if($whichfeeoption)
					{
						foreach ($whichfeeoption as $key => $value)
						{
							$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
						}
					}
					$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\" />";
					for ($i=0;$i<$wpbusdirmannumimgsleft;$i++)
					{
						$html .= "<p><input name=\"wpbusdirmanuploadpic$i\"type=\"file\"></p>";
					}
					$html .= "<p><input class=\"insubmitbutton\" value=\"" . __("Upload File","WPBDM") . "\" type=\"submit\"></p></form><div style=\"clear:both;\"></div>";
					if($totalexistingimages >= 1)
					{
						if(get_post_meta($wpbusdirmanlistingpostid, "image", $single = true))
						{
							$wpbusdirmanimagesinpost=get_post_meta($wpbusdirmanlistingpostid, "image", $single = false);
							$html .= "<p>" . __("You can manage your current images below","WPBDM") . "</p>";
							if($wpbusdirmanimagesinpost)
							{
								foreach($wpbusdirmanimagesinpost as $wpbusdirmanimage)
								{
									$html .= "<div style=\"float:left;margin-right:10px;margin-bottom:15px;\"><img src=\"$wpbdmimagesurl/thumbnails/$wpbusdirmanimage\" border=\"0\" height=\"100\" alt=\"$wpbusdirmanimage\" style=\"margin-bottom:10px;\"><br/>";
									$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
									$html .= "<input type=\"hidden\" name=\"action\" value=\"deleteimage\"/>";
									$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/>";
									$html .= "<input type=\"hidden\" name=\"wpbusdirmanimagetodelete\" value=\"$wpbusdirmanimage\"/>";
									$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsallowed\" value=\"$wpbusdirmannumimgsallowed\"/>";
									$html .= "<input type=\"hidden\" name=\"wpbusdirmannumimgsleft\" value=\"$wpbusdirmannumimgsleft\"/>";
									//$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength\" value=\"$wpbusdirmanlistingtermlength\"/>";
									foreach ($mycatduration as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength[]\" value=\"$value\" />";
									}
									$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\"/>";
									$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\"/>";
									//$html .= "<input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\"/>";
									if($whichfeeoption)
									{
										foreach ($whichfeeoption as $key => $value)
										{
											$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
										}
									}
									$html .= "<input type=\"submit\" class=\"deletelistingbutton\" value=\"" . __("Delete Image","WPBDM") . "\" /></form></div>";
								}
							}
							$html .= "<div style=\"clear:both;\"></div>";
						}
					}
					if(isset($neworedit) && !empty($neworedit) && ($neworedit == 'edit'))
					{
						$html .= "<p>" . __("If you prefer not to add an image or you are otherwise finished managing your images you can click the exit now button.","WPBDM") . "</p>";
						if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_19'] == 'pending2')
						{
							$html .= "<p>" . __("Your updated listing will be submitted for review.","WPBDM") . "</p>";
						}
						elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_19']=='publish2')
						{
							$html .= "<p>" . __("Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
						}
						$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
						$html .= "<p>";
						$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/>";
									if($whichfeeoption)
									{
										foreach ($whichfeeoption as $key => $value)
										{
											$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
										}
									}
						$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></p></form>";
					}
					else
					{
						if(!($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "no"))
						{

							if($feepayval > 0)
							{
								$html .= "<p>" . __("If you prefer not to add any images please click Next to proceed to the next step.","WPBDM") . "</p>";
								$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
								$html .= "<p><input type=\"hidden\" name=\"action\" value=\"payment_step_2\"/>";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/>";
									foreach ($mycatduration as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength[]\" value=\"$value\" />";
									}
									foreach ($whichfeeoption as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
									}
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\"/>";
								$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\"/>";
								$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Next","WPBDM") . "\" /></p></form>";
							}
							else
							{
								$html .= "<p>" . __("If you prefer not to add an image click exit now. Your listing will be submitted for review.","WPBDM") . "</p>";
								$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><p>";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/><input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\" />";
								$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></p></form>";
							}
						}
						else
						{
							$submitactionword =__("submit your listing.","WPBDM");
							if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
							{
								$submitactionword =__("submit your listing for review","WPBDM");
							}
							elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1']=='publish')
							{
								$submitactionword =__("publish your listing","WPBDM");
							}
							$html .= "<p>" . __("If you prefer not to upload an image at this time you can click the Exit now Button. Clicking the button will $submitactionword.","WPBDM") . "</p>";
							$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><p>";
							$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/><input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\" /><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></p></form>";
						}
					}
				}


		}
		else
		{

						if(!($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "no"))
						{

							if($feepayval > 0)
							{
								$html .= "<p>" . __("Click Next to pay your listing fee. Your listing will not be published until your listing fee payment has been received and processed.","WPBDM") . "</p>";
								$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
								$html .= "<p><input type=\"hidden\" name=\"action\" value=\"payment_step_2\"/>";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/>";
									foreach ($mycatduration as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingtermlength[]\" value=\"$value\" />";
									}
									foreach ($whichfeeoption as $key => $value)
									{
										$html .= "<input type=\"hidden\" name=\"whichfeeoption[]\" value=\"$value\" />";
									}
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\"/>";
								$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\"/>";
								$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Next","WPBDM") . "\" /></p></form>";
							}
							else
							{
								$html .= "<p>" . __("If you prefer not to add an image click exit now. Your listing will be submitted for review.","WPBDM") . "</p>";
								$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><p>";
								$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/><input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\" />";
								$html .= "<input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></p></form>";
							}
						}
						else
						{
							$submitactionword =__("submit your listing.","WPBDM");
							if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
							{
								$submitactionword =__("submit your listing for review","WPBDM");
							}
							elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1']=='publish')
							{
								$submitactionword =__("publish your listing","WPBDM");
							}
							$html .= "<p>" . __("If you prefer not to upload an image at this time you can click the Exit now Button. Clicking the button will $submitactionword.","WPBDM") . "</p>";
							$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><p>";
							$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\"/><input type=\"hidden\" name=\"wpbusdirmanfeeoption\" value=\"$whichfeeoption\" /><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></p></form>";
						}

		}

	return $html;

}

function wpbusdirman_doupload()
{
	global $wpbusdirmanimagesdirectory,$wpbusdirmanthumbsdirectory,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirmanpermalink='';
	$wpbusdirmannumimgsallowed='';
	$wpbusdirmannumimgsleft='';
	$wpbusdirmanlistingpostid='';
	$neworedit='';
	$html = '';
	$mycatobj=array();
	$wpbusdirmanfeeoption=array();
	$wpbusdirmanlistingtermlength=array();

	if(isset($_REQUEST['wpbusdirmanlistingpostid'])
		&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
	{
		$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
	}


	if(isset($_REQUEST['wpbusdirmanlistingpostid'])
		&& !empty($_REQUEST['wpbusdirmanlistingpostid']))
	{
		$wpbusdirmanlistingpostid=$_REQUEST['wpbusdirmanlistingpostid'];
	}

	if(isset($_REQUEST['wpbusdirmannumimgsallowed'])
		&& !empty($_REQUEST['wpbusdirmannumimgsallowed']))
	{
		$wpbusdirmannumimgsallowed=$_REQUEST['wpbusdirmannumimgsallowed'];
	}

	if(isset($_REQUEST['wpbusdirmanlistingtermlength'])
		&& !empty($_REQUEST['wpbusdirmanlistingtermlength']))
	{
		$wpbusdirmanlistingtermlength=$_REQUEST['wpbusdirmanlistingtermlength'];
	}

	if(isset($_REQUEST['wpbusdirmannumimgsleft'])
		&& !empty($_REQUEST['wpbusdirmannumimgsleft']))
	{
		$wpbusdirmannumimgsleft=$_REQUEST['wpbusdirmannumimgsleft'];
	}

	if(isset($_REQUEST['wpbusdirmanpermalink'])
		&& !empty($_REQUEST['wpbusdirmanpermalink']))
	{
		$wpbusdirmanpermalink=$_REQUEST['wpbusdirmanpermalink'];
	}
	if(isset($_REQUEST['neworedit'])
		&& !empty($_REQUEST['neworedit']))
	{
		$neworedit=$_REQUEST['neworedit'];
	}
	if(isset($_REQUEST['wpbusdirmanfeeoption'])
		&& !empty($_REQUEST['wpbusdirmanfeeoption']))
	{
		$wpbusdirmanfeeoption=$_REQUEST['wpbusdirmanfeeoption'];
	}elseif(isset($_REQUEST['whichfeeoption'])
		&& !empty($_REQUEST['whichfeeoption']))
	{
		$wpbusdirmanfeeoption=$_REQUEST['whichfeeoption'];
	}

/*	print_r($wpbusdirmanfeeoption);
	echo "<br/>";

	print_r($wpbusdirmanlistingtermlength);

	echo "<p>Images allowed: $wpbusdirmannumimgsallowed</p>";
	echo "Images left: $wpbusdirmannumimgsleft";
	echo "Listing ID: $wpbusdirmanlistingpostid";
	die;*/

		//Rebuild mycatobj

		foreach($wpbusdirmanlistingtermlength as $catdur)
		{

			$mycatdurvals=explode("_",$catdur);
			$mycatid=$mycatdurvals[0];
			$listingincr=$mycatdurvals[1];

				$mycatobj[]=array('listingcat' => $mycatid,'listingduration' => $listingincr);

		} // End foreach wpbusdirmanlistingtermlength


	if ( !is_dir($wpbusdirmanimagesdirectory) )
	{
		@umask(0);
		@mkdir($wpbusdirmanimagesdirectory, 0777);
	}
	if ( !is_dir($wpbusdirmanthumbsdirectory) )
	{
		@umask(0);
		@mkdir($wpbusdirmanthumbsdirectory, 0777);
	}
	$wpbusdirmanimgmaxsize = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_13'];
	$wpbusdirmanimgminsize = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_14'];
	$wpbusdirmanimgmaxwidth = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_15'];
	$wpbusdirmanimgmaxheight = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_16'];
	$wpbusdirmanthumbnailwidth = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_17'];
	$wpbusdirmanallowedextensions = array(".jpg", ".gif", ".png");
	$wpbusdirmanerrornofiles=true;
	$wpbusdirmanuerror=array();
	for ($i=0;$i<$wpbusdirmannumimgsleft;$i++)
	{
		$wpbusdirmantheuploadedfilename = $_FILES['wpbusdirmanuploadpic'. $i]['name'];
		if(!empty($wpbusdirmantheuploadedfilename))
		{
			$wpbusdirmanerrornofiles=false;
		}
	}
	if ($wpbusdirmanerrornofiles)
	{
		$wpbusdirmanuerror[]="<p class=\"wpbusdirmanerroralert\">";
		$wpbusdirmanuerror[].=__("No file was selected","wpbusdirman");
		$wpbusdirmanuerror[].="</p>";

		$wpbusdirmanuploadformshow=apply_filters('wpbdm_show-image-upload-form', $wpbusdirmanlistingpostid,$wpbusdirmanpermalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror,$neworedit,$wpbusdirmanfeeoption);

		$html .= $wpbusdirmanuploadformshow;
	}
	else
	{
		$html .= wpbusdirmanuploadimages($wpbusdirmanlistingpostid,$wpbusdirmanpermalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanimgmaxsize,$wpbusdirmanimgminsize,$wpbusdirmanthumbnailwidth,$wpbusdirmanuploaded_actual_field_name='wpbusdirmanuploadpic',$required=false,$neworedit,$wpbusdirmanfeeoption);
	}

	return $html;
}

function wpbusdirman_calculate_fee_to_pay($wpbusdirmanfeeoption)
{

	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirmanthisfeetopay='';
	$wpbusdirmanthisfeetopayarr=array();
		if($wpbusdirmanfeeoption)
		{
			foreach($wpbusdirmanfeeoption as $feeopid)
			{
				$wpbusdirmanlistingcost=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$feeopid);
				$wpbusdirmanthisfeetopayarr[]=$wpbusdirmanlistingcost;
			}
		}

		if($wpbusdirmanthisfeetopayarr)
		{
			$wpbusdirmanthisfeetopay=array_sum($wpbusdirmanthisfeetopayarr);
		}

	return $wpbusdirmanthisfeetopay;

}

function wpbusdirman_validate_data()
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_field_item_array=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
	$wpbusdirman_field_errors='';

	if($wpbusdirman_field_item_array)
	{
		foreach($wpbusdirman_field_item_array as $wpbusdirman_field_x_field)
		{
			$wpbusdirman_field_name=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_x_field);
			$wpbusdirman_field_validation=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_validation_'.$wpbusdirman_field_x_field);
			$wpbusdirman_field_type=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_type_'.$wpbusdirman_field_x_field);
			$wpbusdirman_field_options=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_options_'.$wpbusdirman_field_x_field);
			$wpbusdirman_field_required=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_required_'.$wpbusdirman_field_x_field);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_x_field);
			if($wpbusdirman_field_association == 'title')
			{
				$wpbusdirman_field_label_association="_title";
			}
			elseif($wpbusdirman_field_association == 'category')
			{
				$wpbusdirman_field_label_association="_category";
			}
			elseif($wpbusdirman_field_association == 'excerpt')
			{
				$wpbusdirman_field_label_association="_excerpt";
			}
			elseif($wpbusdirman_field_association == 'description')
			{
				$wpbusdirman_field_label_association="_description";
			}
			elseif($wpbusdirman_field_association == 'tags')
			{
				$wpbusdirman_field_label_association="_tags";
			}
			else
			{
				$wpbusdirman_field_label_association="_meta$wpbusdirman_field_x_field";
			}

			if($wpbusdirman_field_association == 'category')
			{
				if($wpbusdirman_field_type == 2){$wpbusdirman_field_inputname="cat";}
				elseif($wpbusdirman_field_type == 6){$wpbusdirman_field_inputname="wpbusdirman_field_label_category";}
			}
			else
			{
				$wpbusdirman_field_inputname="wpbusdirman_field_label";
				$wpbusdirman_field_inputname.=$wpbusdirman_field_label_association;
			}
			if (($wpbusdirman_field_required == 'yes')
				&& ((!isset($_REQUEST[$wpbusdirman_field_inputname])
					|| empty($_REQUEST[$wpbusdirman_field_inputname]))))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name is required","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			if ((($wpbusdirman_field_validation == 'missing')
				&& ($wpbusdirman_field_required == 'yes'))
				&& (!isset($_REQUEST[$wpbusdirman_field_inputname])
					|| empty($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name is required","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			elseif (($wpbusdirman_field_validation == 'url')
				&& (isset($_REQUEST[$wpbusdirman_field_inputname]))
				&& (!empty($_REQUEST[$wpbusdirman_field_inputname]))
				&& (!wpbusdirman_isValidURL($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name is badly formatted. Valid URL format required. Include http://","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			elseif (($wpbusdirman_field_validation == 'email')
				&& ($wpbusdirman_field_required == 'yes')
				&& (!wpbusdirman_isValidEmailAddress($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name is badly formatted. Valid Email format required.","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			elseif (($wpbusdirman_field_validation == 'numericdeci')
				&& ($wpbusdirman_field_required == 'yes')
				&& (!is_numeric($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name must be a number.","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			elseif (($wpbusdirman_field_validation == 'numericwhole')
				&& ($wpbusdirman_field_required == 'yes')
				&& (!ctype_digit($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name must be a number. Decimal values not allowed.","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
			elseif (($wpbusdirman_field_validation == 'date')
				&& ($wpbusdirman_field_required == 'yes')
				&& (!wpbusdirman_is_ValidDate($_REQUEST[$wpbusdirman_field_inputname])))
			{
				$error=true;
				$wpbusdirman_field_errors.="<li class=\"wpbusdirmanerroralert\">";
				$wpbusdirman_field_errors.=__("$wpbusdirman_field_name must be in the format 00/00/0000.","awpdb");
				$wpbusdirman_field_errors.="</li>";
			}
		}
	}

	return $wpbusdirman_field_errors;
}

function wpbusdirman_isValidURL($url)
{
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function wpbusdirman_isValidEmailAddress($email)
{
	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email))
	{
		return false;
	}
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++)
	{
    	if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&?'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i]))
    	{
			return false;
		}
	}
	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1]))
	{
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2)
		{
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++)
		{
			if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])| ?([A-Za-z0-9]+))$", $domain_array[$i]))
			{
				return false;
			}
		}
	}

	return true;
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

function wpbusdirmanuploadimages($wpbusdirmanlistingpostid,$wpbusdirmanpermalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanimgmaxsize,$wpbusdirmanimgminsize,$wpbusdirmanthumbnailwidth,$wpbusdirmanuploaded_actual_field_name,$required,$neworedit,$wpbusdirmanfeeoption)
{

	//echo $wpbusdirmannumimgsleft;die;

	global $wpdb,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirmanwpbusdirmanerroralert=false;
	$wpbusdirmanfilesuploaded=true;
	$wpbusdirmanuerror=array();
	$uploaddir=get_option('upload_path');
	if(!isset($uploaddir) || empty($uploaddir))
	{
		$uploaddir=ABSPATH;
		$uploaddir.="wp-content/uploads";
		//$uploaddir = trim($uploaddir,'/');
	}
	$wpbusdirmanuploaddir=$uploaddir;
	$wpbusdirmanuploaddir.="/wpbdm";
	$wpbusdirmanuploadthumbsdir=$wpbusdirmanuploaddir;
	$wpbusdirmanuploadthumbsdir.="/thumbnails";
	$html = '';

	/* listing term length with cat id and term lenght for multi cat situations */
	$mycatduration=array();

	if($mycatobj && is_array($mycatobj))
	{

		foreach($mycatobj as $mycatobject)
		{
				$catduration=$mycatobject['listingcat'];
				$catduration.="_";
				$catduration.=$mycatobject['listingduration'];
				$mycatduration[]=$catduration;
		}
	}


	if ( !is_dir($wpbusdirmanuploaddir) )
	{
		umask(0);
		mkdir($wpbusdirmanuploaddir, 0777);
	}
	if ( !is_dir($wpbusdirmanuploadthumbsdir) )
	{
		umask(0);
		mkdir($wpbusdirmanuploadthumbsdir, 0777);
	}
	for($i=0;$i<$wpbusdirmannumimgsleft;$i++)
	{
		$wpbusdirmanuploadedfilename=addslashes($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['name']);
		$wpbusdirmanuploaded_ext=strtolower(substr(strrchr($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['name'],"."),1));
		$wpbusdirmanuploaded_ext_array=array('gif','jpg','jpeg','png');
		if (isset($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name'])
			&& is_uploaded_file($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name']))
		{
			$wpbusdirman_imginfo = getimagesize($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name']);
			$wpbusdirman_imgfilesizeval=filesize($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name']);
			$wpbusdirmandesired_filename=mktime();
			$wpbusdirmandesired_filename.="_$i";
			if(isset($wpbusdirmanuploadedfilename)
				&& !empty($wpbusdirmanuploadedfilename))
			{
				if (!(in_array($wpbusdirmanuploaded_ext, $wpbusdirmanuploaded_ext_array)))
				{
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbusdirmanuploadedfilename]";
					$wpbusdirmanuerror[].=__("had an invalid file extension and was not uploaded","wpbusdirman");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif(filesize($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name']) <= $wpbusdirmanimgminsize)
				{
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">";
					$wpbusdirmanuerror[].=__("The size of $wpbusdirmanuploadedfilename was too small. The file was not uploaded. File size must be greater than $wpbusdirmanimgminsize bytes","wpbusdirman");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif($wpbusdirman_imginfo[0]< $wpbusdirmanthumbnailwidth)
				{
					// width is too short
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbusdirmanuploadedfilename]";
					$wpbusdirmanuerror[].=__("did not meet the minimum width of [$wpbusdirmanthumbnailwidth] pixels. The file was not uploaded","wpbusdirman");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif ($wpbusdirman_imginfo[1]< $wpbusdirmanthumbnailwidth)
				{
					// height is too short
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbusdirmanuploadedfilename]";
					$wpbusdirmanuerror[].=__("did not meet the minimum height of [$wpbusdirmanthumbnailwidth] pixels. The file was not uploaded","wpbusdirman");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif(!isset($wpbusdirman_imginfo[0])
					&& !isset($wpbusdirman_imginfo[1]))
				{
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbusdirmanuploadedfilename]";
					$wpbusdirmanuerror[].=__("does not appear to be a valid image file","wpbusdirman");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif( $wpbusdirman_imgfilesizeval > $wpbusdirmanimgmaxsize )
				{
					$wpbusdirmanwpbusdirmanerroralert=true;
					$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbusdirmanuploadedfilename]";
					$wpbusdirmanuerror[].=__("was larger than the maximum allowed file size of [$wpbusdirmanimgmaxsize] bytes. The file was not uploaded");
					$wpbusdirmanuerror[].="</p>";
				}
				elseif(!empty($wpbusdirmandesired_filename))
				{
					$wpbusdirmanuploadedfilename="$wpbusdirmandesired_filename.$wpbusdirmanuploaded_ext";
					if (!move_uploaded_file($_FILES[$wpbusdirmanuploaded_actual_field_name.$i]['tmp_name'],$wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename))
					{
						$wpbdmor=$wpbusdirmanuploadedfilename;
						$wpbusdirmanuploadedfilename='';
						$wpbusdirmanwpbusdirmanerroralert=true;
						$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">[$wpbdmor]";
						$wpbusdirmanuerror[].=__("could not be moved to the destination directory $wpbusdirmanuploaddir","wpbusdirman");
						$wpbusdirmanuerror[].="</p>";
					}
					else
					{
						if(!wpbusdirmancreatethumb($wpbusdirmanuploadedfilename,$wpbusdirmanuploaddir,$wpbusdirmanthumbnailwidth))
						{
							$wpbusdirmanwpbusdirmanerroralert=true;
							$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">";
							$wpbusdirmanuerror[].=__("Could not create thumbnail image of [ $wpbusdirmanuploadedfilename ]","wpbusdirman");
							$wpbusdirmanuerror[].="</p>";
						}
						@chmod($wpbusdirmanuploaddir.'/'.$wpbusdirmanuploadedfilename,0644);

						add_post_meta($wpbusdirmanlistingpostid, $wpbusdirman_field_label='image', $wpbusdirmanfieldmeta=$wpbusdirmanuploadedfilename, false) or update_post_meta($wpbusdirmanlistingpostid, $wpbusdirman_field_label='image', $wpbusdirmanfieldmeta=$wpbusdirmanuploadedfilename);
						add_post_meta($wpbusdirmanlistingpostid, $wpbusdirman_field_label='thumbnail', $wpbusdirmanfieldmeta=$wpbusdirmanuploadedfilename, false) or update_post_meta($wpbusdirmanlistingpostid, $wpbusdirman_field_label='thumbnail', $wpbusdirmanfieldmeta=$wpbusdirmanuploadedfilename);
						add_post_meta($wpbusdirmanlistingpostid, "totalallowedimages", $wpbusdirmannumimgsallowed, true) or update_post_meta($wpbusdirmanlistingpostid, "totalallowedimages", $wpbusdirmannumimgsallowed);

					/*	if($mycatduration)
						{
							foreach($mycatduration as $catdur)
							{
								$existingtermlengths=get_post_meta($wpbusdirmanlistingpostid, "termlength", false);

								if(!in_array($catdur,$existingtermlengths))
								{
									add_post_meta($wpbusdirmanlistingpostid, "termlength", $catdur, false);
								}
							}
						}

						if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes")
						{
							if($wpbusdirmanfeeoption)
							{
								foreach($wpbusdirmanfeeoption as $feeopid)
								{
									$wpbusdirmanlistingcost=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$feeopid);
									add_post_meta($wpbusdirmanlistingpostid, "costoflisting", $wpbusdirmanlistingcost, false) or update_post_meta($wpbusdirmanlistingpostid, "costoflisting", $wpbusdirmanlistingcost);
									add_post_meta($wpbusdirmanlistingpostid, "listingfeeid", $feeopid, false) or update_post_meta($wpbusdirmanlistingpostid, "costoflisting", $feeopid);
								}
							}
						} */
					}
				}
			}
			else
			{
				$wpbusdirmanwpbusdirmanerroralert=true;
				$wpbusdirmanuerror[].="<p class=\"wpbusdirmanerroralert\">";
				$wpbusdirmanuerror[].=__("Unknown error encountered uploading image","wpbusdirman");
				$wpbusdirmanuerror[].="</p>";
			}
		}
	} // Close for $i...
	if ($wpbusdirmanwpbusdirmanerroralert)
	{
		$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption);

		$new_wpbusdirmannumimagesallowed=$myimagesallowedleft['imagesallowed'];
		$new_wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

		$wpbusdirmanuploadformshow=apply_filters('wpbdm_show-image-upload-form', $wpbusdirmanlistingpostid,$wpbusdirmanpermalink,$new_wpbusdirmannumimagesallowed,$new_wpbusdirmannumimgsleft,$mycatobj,$wpbusdirmanuerror,$neworedit,$wpbusdirmanfeeoption);
		$html .= $wpbusdirmanuploadformshow;
	}
	else
	{
		if(isset($neworedit)
			&& !empty($neworedit)
			&& ($neworedit == 'edit'))
		{
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_19'] == 'pending2')
			{
				$html .= "<p>" . __("Your listing has been updated. Your listing is currently pending re-review and will become accessible again once the administrator has reviewed it.","WPBDM") . "</p>";
			}
			elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_19'] == 'publish2')
			{
				$html .= "<p>" . __("Your listing has been updated. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
			}
			else
			{
				$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p><form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></form>";
			}
		}
		else
		{
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes")
			{

				$wpbusdirmanthisfeetopay=wpbusdirman_calculate_fee_to_pay($wpbusdirmanfeeoption);

				if($wpbusdirmanthisfeetopay > 0)
				{
					$html .= wpbusdirman_load_payment_page($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$mycatduration,$wpbusdirmanthisfeetopay);
				}
				else
				{
					// There is no fee to pay so skip to end of process. Nothing left to do
					if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
					{
						$html .= "<p>" . __("Your submission has been received and is currently pending review","WPBDM") . "</p>";
					}
					elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'publish')
					{
						$html .= "<p>" . __("Your submission has been received and is currently published. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
					}
					else
					{
						$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p>";
						$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></form>";
					}
				}
			}
			else
			{
				if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'pending')
				{
					$html .= "<p>" . __("Your submission has been received and is currently pending review","WPBDM") . "</p>";
				}
				elseif($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_1'] == 'publish')
				{
					$html .= "<p>" . __("Your submission has been received and is currently published. Note that the administrator reserves the right to terminate without warning any listings that violate the site's terms of use.","WPBDM") . "</p>";
				}
				else
				{
					$html .= "<p>" . __("You are finished with your listing.","WPBDM") . "</p><form method=\"post\" action=\"$wpbusdirmanpermalink\"><input type=\"submit\" class=\"exitnowbutton\" value=\"" . __("Exit Now","WPBDM") . "\" /></form>";
				}
			}
		}
	}

	return $html;
}

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
		$existingfeeids=get_post_meta($wpbusdirmanlistingpostid,'listingfeeid',false);

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

		$existingimages=get_post_meta($wpbusdirmanlistingpostid,'image',false);
		if($existingimages){ $totalexistingimages=count($existingimages); } else { $totalexistingimages = 0;}

		if($totalexistingimages > 0){$wpbusdirmannumimgsleft=($wpbusdirmannumimagesallowed - $totalexistingimages );}else{$wpbusdirmannumimgsleft = $wpbusdirmannumimagesallowed;}

		$imagesalloweleftobj=array('listingid' => $wpbusdirmanlistingpostid, 'imagesallowed' => $wpbusdirmannumimagesallowed, 'imagesleft' =>  $wpbusdirmannumimgsleft,'totalexisting' => $totalexistingimages );

		return $imagesalloweleftobj;

}

function wpbusdirman_managelistings()
{
	global $siteurl,$wpbdmimagesurl,$wpbusdirman_gpid,$permalinkstructure,$wpbdmposttype,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypecategory;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	if(!(is_user_logged_in()))
	{
		$wpbusdirmanloginurl=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_4'];
		if(!isset($wpbusdirmanloginurl) || empty($wpbusdirmanloginurl))
		{
			$wpbusdirmanloginurl=$siteurl.'/wp-login.php';
		}
		$html .= "<p>" . __("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email with 60 mintues.","WPBDM") . "</p>";
		$html .= "<form method=\"post\" action=\"$wpbusdirmanloginurl\"><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Login Now","WPBDM") . "\" /></form>";
	}
	else
	{
		$args=array('hide_empty' => 0);
		$wpbusdirman_postcats=get_terms( $wpbdmposttypecategory, $args);
		if(!isset($wpbusdirman_postcats) || empty($wpbusdirman_postcats))
		{
			if(is_user_logged_in() && current_user_can('install_plugins'))
			{
				$html .= "<p>" . __("There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that they do not currently have any listings to manage. Listings cannot be added until you assign categories to the business directory. ","WPBDM") . "</p>";
			}
			else
			{
				$html .= "<p>" . __("You do not currently have any listings to manage","WPBDM") . "</p>";
			}
		}
		else
		{
			global $current_user;
			$html .= get_currentuserinfo();
			$wpbusdirman_CUID=$current_user->ID;
			wp_reset_query();
			$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
			query_posts('author='.$wpbusdirman_CUID.'&post_type='.$wpbdmposttype);
			if ( have_posts() )
			{
				$count=0;
				$html .= '<p>' . __("Your current listings are shown below. To edit a listing click the edit button. To delete a listing click the delete button.","WPBDM") . "</p>";
				while (have_posts())
				{
					$html .= the_post();
					$count++;
					$html .= wpbusdirman_post_excerpt($count);
				}
				$html .= '<div class="navigation">';
				if(function_exists('wp_pagenavi'))
				{
					$html .= wp_pagenavi();
				}
				else
				{
					$html .= '<div class="alignleft">' . next_posts_link('&laquo; Older Entries') . '</div><div class="alignright">' . previous_posts_link('Newer Entries &raquo;') . '</div>';
				}
				$html .= '</div>';
			}
			else
			{
				 $html .= "<p>" . __("You do not currently have any listings in the directory","WPBDM") . "</p>";
			}
			wp_reset_query();
		}
	}

	return $html;
}

function wpbusdirman_deleteimage($imagetodelete,$wpbdmlistingid,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$wpbusdirmanlistingtermlength,$wpbusdirmanpermalink,$neworedit)
{
	global $wpbusdirmanimagesdirectory,$wpbusdirmanthumbsdirectory;
	$html = '';

	if(isset($imagetodelete)
		&& !empty($imagetodelete))
	{
		if(isset($wpbdmlistingid)
			&& !empty($wpbdmlistingid))
		{
			delete_post_meta($wpbdmlistingid, "image", $imagetodelete);
			delete_post_meta($wpbdmlistingid, "thumbnail", $imagetodelete);
			if (file_exists($wpbusdirmanimagesdirectory.'/'.$imagetodelete))
			{
				@unlink($wpbusdirmanimagesdirectory.'/'.$imagetodelete);
			}
			if (file_exists($wpbusdirmanthumbsdirectory.'/'.$imagetodelete))
			{
				@unlink($wpbusdirmanthumbsdirectory.'/'.$imagetodelete);
			}
			$wpbusdirmannumimgsleft=($wpbusdirmannumimgsleft + 1);
		}
	}
	$html .= apply_filters('wpbdm_show-image-upload-form', $wpbdmlistingid,$wpbusdirmanpermalink,$wpbusdirmannumimgsallowed,$wpbusdirmannumimgsleft,$wpbusdirmanlistingtermlength,$wpbusdirmanuerror='',$neworedit,$whichfeeoption='');

	return $html;
}

function wpbusdirman_load_payment_page($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirmanlengthofterm,$wpbusdirmanlistingcost)
{
	global $wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule,$wpbusdirman_gpid,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();

	$wpbusdirman_get_currency_symbol=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_12'];

	if(!isset($wpbusdirman_get_currency_symbol)
		|| empty($wpbusdirman_get_currency_symbol))
	{
		$wpbusdirman_get_currency_symbol="$";
	}
	$wpbusdirman_googlecheckout_button='';
	$wpbusdirman_paypal_button='';
	$wpbusdirman_twocheckout_button='';
	$html = '';


	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_21'] == "yes")
	{

		if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_6'] == "yes")
		{
			$myimagesallowedleft=wpbusdirman_imagesallowed_left($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption);

			$wpbusdirmannumimagesallowed=$myimagesallowedleft['imagesallowed'];
			$wpbusdirmannumimgsleft=$myimagesallowedleft['imagesleft'];

			add_post_meta($wpbusdirmanlistingpostid, "totalallowedimages", $wpbusdirmannumimagesallowed, true) or update_post_meta($wpbusdirmanlistingpostid, "totalallowedimages", $wpbusdirmannumimagesallowed);
		}
	}
	$html .= "<h3>" . __("Step 3","WPBDM") . "</h3><br />";
	global $wpbusdirman_imagesurl;
	if(($wpbusdirman_hasgooglecheckoutmodule == 1)
		&& ($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_40'] != "yes"))
	{ 	$html .= "<h4 class=\"paymentheader\">" . __("Pay ", "WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirmanlistingcost;
		$html .= __(" listing fee via Google Checkout","WPBDM") . "</h4>";
		$wpbusdirman_googlecheckout_button=wpbusdirman_googlecheckout_button($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirmanlistingcost);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_googlecheckout_button . "</div>";
	}
	if(($wpbusdirman_haspaypalmodule == 1)
		&& ($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_41'] != "yes"))
	{
		$html .= "<h4 class=\"paymentheader\">" . __("Pay ","WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirmanlistingcost;
		$html .= __(" listing Fee via PayPal","WPBDM") . "</h4>";
		$wpbusdirman_paypal_button=wpbusdirman_paypal_button($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirman_imagesurl,$wpbusdirmanlistingcost);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_paypal_button . "</div>";
	}

	if(($wpbusdirman_hastwocheckoutmodule == 1)
		&& ($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_43'] != "yes"))
	{
		$html .= "<h4 class=\"paymentheader\">" . __("Pay ", "WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirmanlistingcost;
		$html .= __(" listing fee via 2Checkout","WPBDM") . "</h4>";
		$wpbusdirman_twocheckout_button=wpbusdirman_twocheckout_button($wpbusdirmanlistingpostid,$wpbusdirmanfeeoption,$wpbusdirman_gpid,$wpbusdirmanlistingcost);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_twocheckout_button . "</div>";
	}

	return $html;
}



function wpbusdirman_msort($array, $id="id", $sort_ascending=true) {
        $temp_array = array();
        while(count($array)>0) {
            $lowest_id = 0;
            $index=0;
            foreach ($array as $item) {
                if (isset($item[$id])) {
                                    if ($array[$lowest_id][$id]) {
                    if ($item[$id]<$array[$lowest_id][$id]) {
                        $lowest_id = $index;
                    }
                    }
                                }
                $index++;
            }
            $temp_array[] = $array[$lowest_id];
            $array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
        }
                if ($sort_ascending) {
            return $temp_array;
                } else {
                    return array_reverse($temp_array);
                }
    }

function wpbusdirman_contactform($wpbusdirmanpermalink,$wpbusdirmanlistingpostid,$commentauthorname,$commentauthoremail,$commentauthorwebsite,$commentauthormessage,$wpbusdirmancontacterrors)
{
	global $wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	if(!isset($wpbusdirmanpermalink) || empty($wpbusdirmanpermalink))
	{
		global $wpbusdirman_gpid,$wpbdmimagesurl;
		$wpbusdirmanpermalink=get_permalink($wpbusdirman_gpid);
	}
	$html = '';

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_27'] == "yes")
	{
		if(isset($wpbusdirmancontacterrors)
			&& !empty($wpbusdirmancontacterrors))
		{
			$html .= "<ul id=\"wpbusdirmanerrors\">$wpbusdirmancontacterrors</ul>";
		}
		$html .= "<h4>" . __("Send Message to listing owner","WPBDM") . "</h4><p><label>" . __("Listing Title: ","WPBDM") . "</label>" . get_the_title($wpbusdirmanlistingpostid) . "</p>";
		$html .= "<form method=\"post\" action=\"$wpbusdirmanpermalink\">";
		if(!is_user_logged_in())
		{
			$html .= "<p><label style=\"width:4em;\">" . __("Your Name ","WPBDM") . "</label><input type=\"text\" class=\"intextbox\" name=\"commentauthorname\" value=\"$commentauthorname\" /></p><p><label style=\"width:4em;\">" . __("Your Email ","WPBDM") . "</label><input type=\"text\" class=\"intextbox\" name=\"commentauthoremail\" value=\"$commentauthoremail\" /></p>";
			$html .= "<p><label style=\"width:4em;\">" . __("Website url ","WPBDM") . "</label><input type=\"text\" class=\"intextbox\" name=\"commentauthorwebsite\" value=\"$commentauthorwebsite\" /></p>";
		}
		elseif(is_user_logged_in())
		{
			if(!isset($commentauthorname) || empty($commentauthorname))
			{
				global $post, $current_user;
				get_currentuserinfo();
				$commentauthorname = $current_user->user_login;
			}
			$html .= "<p>" . __("You are currently logged in as ","WPBDM") . $commentauthorname . "." . __(" Your message will be sent using your logged in contact email.","WPBDM") . "</p>";
		}
		$html .= "<p><label style=\"width:4em;\">" . __("Message","WPBDM") . "</label><br/><textarea name=\"commentauthormessage\" class=\"intextarea\">$commentauthormessage</textarea></p>";
		if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_30'] == "yes")
		{
			$publickey = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_28'];
			if(isset($publickey)
				&& !empty($publickey))
			{
				require_once('recaptcha/recaptchalib.php');
				$wpbdmrecaptcha=recaptcha_get_html($publickey);
				$html .= recaptcha_get_html($publickey);
			}
		}
		$html .= "<p><input type=\"hidden\" name=\"action\" value=\"sendcontactmessage\" />";
		$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbusdirmanlistingpostid\" />";
		$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirmanpermalink\" />";
		$html .= "<input type=\"submit\" class=\"insubmitbutton\" value=\"Send\" /></p></form>";
	}

	return $html;
}


function wpbusdirman_upgradetosticky($wpbdmlistingid)
{
 	global $wpbusdirman_imagesurl,$wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule,$wpbusdirman_gpid,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_get_currency_symbol=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_12'];

	if(!isset($wpbusdirman_get_currency_symbol)
		|| empty($wpbusdirman_get_currency_symbol))
	{
		$wpbusdirman_get_currency_symbol="$";
	}

	$html = '';
 	$html .= "<h4>" . __("Upgrade listing","WPBDM") . "</h4>";
 	$wpbdmstickydetailtext=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_33'];
 	if(isset($wpbdmstickydetailtext)
 		&& !empty($wpbdmstickydetailtext))
 	{
 		$html .= "<p>$wpbdmstickydetailtext</p>";
 	}
 	$wpbusdirman_stickylistingprice=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_32'];
 	add_post_meta($wpbdmlistingid, "sticky", "not paid", true) or update_post_meta($wpbdmlistingid, "sticky", "not paid");
	if($wpbusdirman_hasgooglecheckoutmodule == 1)
	{
		$html .= "<h4 class=\"paymentheader\">" . __("Pay ", "WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirman_stickylistingprice;
		$html .= __(" upgrade fee via Google Checkout","WPBDM") . "</h4>";
		$wpbusdirman_googlecheckout_button=wpbusdirman_googlecheckout_button($wpbdmlistingid,$wpbusdirmanfeeoption='32',$wpbusdirman_stickylistingprice);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_googlecheckout_button . "</div>";
	}

	if($wpbusdirman_haspaypalmodule == 1)
	{
		$html .= "<h4 class=\"paymentheader\">" . __("Pay ", "WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirman_stickylistingprice;
		$html .= __(" upgrade fee via PayPal","WPBDM") . "</h4>";
		$wpbusdirman_paypal_button=wpbusdirman_paypal_button($wpbdmlistingid,$wpbusdirmanfeeoption='32',$wpbusdirman_imagesurl,$wpbusdirman_stickylistingprice);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_paypal_button . "</div>";
	}

	if($wpbusdirman_hastwocheckoutmodule == 1)
	{
		$html .= "<h4 class=\"paymentheader\">" . __("Pay ", "WPBDM");
		$html .= $wpbusdirman_get_currency_symbol;
		$html .= $wpbusdirman_stickylistingprice;
		$html .= __(" upgrade fee via 2Checkout","WPBDM") . "</h4>";
		$wpbusdirman_twocheckout_button=wpbusdirman_twocheckout_button($wpbdmlistingid,$wpbusdirmanfeeoption='32',$wpbusdirman_gpid,$wpbusdirman_stickylistingprice);
		$html .= "<div class=\"paymentbuttondiv\">" . $wpbusdirman_twocheckout_button . "</div>";
	}

	return $html;
}

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

function wpbusdirmanfindpage($shortcode)
{
	global $wpdb,$table_prefix;
	$myreturn=false;
	$query="SELECT count(post_name) FROM {$table_prefix}posts WHERE post_content='$shortcode' AND post_type='page'";

	if (!($res=@mysql_query($query))) {trigger_error(mysql_error(),E_USER_ERROR);}
	if (mysql_num_rows($res) && mysql_result($res,0,0)) {
		$myreturn=true;
	}
	return $myreturn;
}

function wpbusdirmanmakepagemain($wpbdmpagename,$wpbdmpagecontent)
{

	$wpbusdirman_gpid='';
	if(!(wpbusdirmanfindpage($wpbdmpagecontent)))
	{

		// Create the main business directory page
		  $wpbusdirman_gpid=wp_insert_post(  $wpbusdirman_my_page );

			$wpbusdirman_gpid = wp_insert_post( array(
			'post_author'	=> 1,
			'post_title'	=> $wpbdmpagename,
			'post_content'	=> $wpbdmpagecontent,
			'post_status' 	=> 'publish',
			'post_type' 	=> 'page',
		));
  	}

  	return $wpbusdirman_gpid;

}

function wpbusdirmanmakepage($wpbusdirman_gpid,$wpbdmpagename,$wpbdmpagecontent)
{
	if(!(wpbusdirmanfindpage($wpbdmpagecontent)))
	{

		// Create the child pages
		if(!isset($wpbusdirman_gpid) || empty($wpbusdirman_gpid)){$wpbusdirman_gpid=wpbusdirman_gpid();}

			$wpbusdirman_gpid = wp_insert_post( array(
			'post_author'	=> 1,
			'post_title'	=> $wpbdmpagename,
			'post_content'	=> $wpbdmpagecontent,
			'post_status' 	=> 'publish',
			'post_type' 	=> 'page',
			'post_parent' => $wpbusdirman_gpid,
		));
	}

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
				'meta_key' => 'termlength',
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
			$wpbusdirmantermlength=get_post_meta($listingwithtermlengthset, "termlength", true);
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

function wpbusdirman_renew_listing($wpbdmidtorenew,$wpbusdirman_permalink,$neworedit)
{
	global $wpbusdirman_haspaypalmodule,$wpbusdirman_hastwocheckoutmodule,$wpbusdirman_hasgooglecheckoutmodule;
	$html = '';

	if(isset($wpbdmidtorenew)
		&& !empty($wpbdmidtorenew))
	{
		$wpbdmrenewingtitle=get_the_title($wpbdmidtorenew);
		$wpbdmrenewingcat=get_the_category($wpbdmidtorenew);
		if($wpbdmrenewingcat)
		{
			foreach($wpbdmrenewingcat as $wpbdmrenewingcategory)
			{
				$wpbdmrenewingcatID=$wpbdmrenewingcategory->cat_ID;
			}
		}
		if(( $wpbusdirman_haspaypalmodule == 1)
			|| ($wpbusdirman_hastwocheckoutmodule == 1)
			|| ($wpbusdirman_hasgooglecheckoutmodule == 1))
		{
			$html .= "<h3>" . __("Renew Listing","WPBDM") . "</h3>";
			$wpbusdirman_fee_to_pay_li=wpbusdirman_feepay_configure($wpbdmrenewingcatID);
			$html .= "<p>" . __("You are about to renew","WPBDM") . ": $wpbdmrenewingtitle" . "</p>";
			if(isset($wpbusdirman_fee_to_pay_li) && !empty($wpbusdirman_fee_to_pay_li))
			{
				global $wpbusdirman_gpid,$permalinkstructure;
				$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
				$wpbusdirman_fee_to_pay="<ul id=\"wpbusdirmanpaymentoptionslist\">";
				$wpbusdirman_fee_to_pay.=$wpbusdirman_fee_to_pay_li;
				$wpbusdirman_fee_to_pay.="</ul>";
				$neworedit='new';
				$html .= "<label>" . __("Select Listing Payment Option","WPBDM") . "</label><br /><p>";
				$html .= "<form method=\"post\" action=\"$wpbusdirman_permalink\">";
				$html .= "<input type=\"hidden\" name=\"action\" value=\"renewlisting_step_2\" />";
				$html .= "<input type=\"hidden\" name=\"wpbusdirmanlistingpostid\" value=\"$wpbdmidtorenew\" />";
				$html .= "<input type=\"hidden\" name=\"wpbusdirmanpermalink\" value=\"$wpbusdirman_permalink\" />";
				$html .= "<input type=\"hidden\" name=\"neworedit\" value=\"$neworedit\" />" . $wpbusdirman_fee_to_pay . "<br/><input type=\"submit\" class=\"insubmitbutton\" value=\"" . __("Next","WPBDM") . "\" /></form></p>";
			}
		}
	}
	else
	{
		$html .= "<p>" . __("There was no ID supplied. Cannot complete renewal. Please contact administrator","WPBDM") . "</p>";
	}

	return $html;
}

function wpbusdirman_viewlistings()
{
	global $wpbusdirman_plugin_path;

	if(file_exists(get_template_directory() . '/single/wpbusdirman-index-listings.php'))
	{
		include get_template_directory() . '/single/wpbusdirman-index-listings.php';
	}
	elseif(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php'))
	{
		include get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php';
	}
	elseif(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php'))
	{
		include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
	}
	else
	{
		include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
	}
}


//Display the listing thumbnail
function wpbusdirman_display_the_thumbnail()
{
	global $wpbdmimagesurl,$post,$wpbusdirmanconfigoptionsprefix,$wpbusdirman_imagesurl;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$html = '';

	if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_11'] == "yes")
	{
		$tpostimg2=get_post_meta($post->ID, "image", true);
		if(isset($tpostimg2)
			&& !empty($tpostimg2))
		{
			$wpbusdirman_theimg2=$tpostimg2;
		}
		else
		{
			$wpbusdirman_theimg2='';
		}
		$wpbdmusedef=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_39'];
		$wpbdmimgwidth=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_17'];
		if(!isset($wpbdmimgwidth)
			|| empty($wpbdmimgwidth))
		{
			$wpbdmimgwidth="120";
		}
		if(isset($wpbusdirman_theimg2)
			&& !empty($wpbusdirman_theimg2))
		{
			$html .= '<a href="' . get_permalink() . '"><img class="wpbdmthumbs" src="' . $wpbdmimagesurl . '/thumbnails/' . $wpbusdirman_theimg2 . '" width="' . $wpbdmimgwidth . '" alt="' . the_title(null, null, false) . '" title="' . the_title(null, null, false) . '" border="0"></a>';
		}
		else
		{
			if(!isset($wpbdmusedef)
				|| empty($wpbdmusedef)
				|| ($wpbdmusedef == "yes"))
			{
				$html .= '<a href="' . get_permalink() . '"><img class="wpbdmthumbs" src="' . $wpbusdirman_imagesurl . '/default.png" width="' . $wpbdmimgwidth . '" alt="' .  the_title(null, null, false) . '" title="' . the_title(null, null, false) . '" border="0"></a>';
			}
		}
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
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
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
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="submitlisting" /><input type="submit" class="submitlistingbutton" value="' . __("Submit A Listing","WPBDM") . '" /></form>';

	return $html;
}

function wpbusdirman_menu_button_viewlistings()
{
	echo wpbusdirman_post_menu_button_viewlistings();
}

function wpbusdirman_post_menu_button_viewlistings()
{
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="viewlistings" /><input type="submit" class="viewlistingsbutton" value="' . __("View Listings","WPBDM") . '" /></form>';

	return $html;
}

function wpbusdirman_menu_button_directory()
{

	echo wpbusdirman_post_menu_button_directory();
}

function wpbusdirman_post_menu_button_directory()
{
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="submit" class="viewlistingsbutton" value="' . __("Directory","WPBDM") . '" /></form>';

	return $html;
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
			$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . $post->ID . '" /><input type="submit" class="editlistingbutton" value="' . __("Edit Listing","WPBDM") . '" /></form>';
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
		$wpbdmpostissticky=get_post_meta($post->ID, "sticky", $single=true);
		if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail)
		{
			if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_31'] == "yes")
			{
				if( (!isset($wpbdmpostissticky) || empty($wpbdmpostissticky) || ($wpbdmpostissticky == 'not paid')) && ( $post->post_status == 'publish') )
				{
					$html .= '<form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="upgradetostickylisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . $post->ID . '" /><input type="submit" class="updradetostickylistingbutton" value="' . __("Upgrade Listing","WPBDM") . '" /></form>';
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
	$wpbdm_show_parent_categories_only=$wpbusdirmanconfigoptionsprefix."_settings_config_48";
	$wpbdm_show_parent_categories_only=1;
	if(isset($wpbdm_show_parent_categories_only)
		&& !empty($wpbdm_show_parent_categories_only)
		&& ($wpbdm_show_parent_categories_only == "yes"))
	{
		$wpbdm_show_parent_categories_only=0;
	}
	elseif(isset($wpbdm_show_parent_categories_only)
		&& !empty($wpbdm_show_parent_categories_only)
		&& ($wpbdm_show_parent_categories_only == "no"))
	{
		$wpbdm_show_parent_categories_only=1;
	}

	$taxonomy     = $wpbdmposttypecategory;
	$orderby      = $wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_7'];
	$show_count   = $wpbdm_show_count;      // 1 for yes, 0 for no
	$pad_counts   = 0;      // 1 for yes, 0 for no
	$hierarchical = $wpbdm_show_parent_categories_only;      // 1 for yes, 0 for no
	$title        = '';
	$order=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_8'];
	$hide_empty=$wpbdm_hide_empty;
	$args = array(
		'taxonomy'     => $taxonomy,
		'orderby'      => $orderby,
		'show_count'   => $show_count,
		'pad_counts'   => $pad_counts,
		'hierarchical' => $hierarchical,
		'title_li'     => $title,
		'order' =>$order,
		'hide_empty' => $hide_empty
	);
	$html .= wp_list_categories($args);

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
function wpbusdirman_display_the_listing_fields()
{
	global $post,$wpbdmposttypecategory,$wpbdmposttypetags,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_field_vals=wpbusdirman_retrieveoptions($whichoptions='wpbusdirman_postform_field_label_');
	$html = '';

	if($wpbusdirman_field_vals)
	{
		foreach($wpbusdirman_field_vals as $wpbusdirman_field_val)
		{
			if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val) == 'yes')
			{
				$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
				$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
				if($wpbusdirman_field_association == 'title')
				{
					$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: <a href="' .  get_permalink() . '">' .  the_title(null, null, false) . '</a></p>';
				}
				elseif($wpbusdirman_field_association == 'category')
				{
					$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_term_list( $post->ID, $wpbdmposttypecategory, '', ', ', '' ) . '</p>';
				}
				elseif($wpbusdirman_field_association == 'meta')
				{
					$wpbusdirman_field_value=get_post_meta($post->ID, $wpbusdirman_field_label, $single = true);
					$wpbusdirman_field_value=preg_replace("/(http:\/\/[^\s]+)/","<a rel=\"no follow\" href=\"\$1\">\$1</a>",$wpbusdirman_field_value);
					$wpbusdirman_field_value=str_replace("\t",", ",$wpbusdirman_field_value);
					if(isset($wpbusdirman_field_value)
						&& !empty($wpbusdirman_field_value)
						&& (!wpbusdirman_isValidEmailAddress($wpbusdirman_field_value)))
					{
						$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . $wpbusdirman_field_value . '</p>';
					}
				}
				elseif (($wpbusdirman_field_association == 'excerpt')
					&& (has_excerpt($post->ID)))
				{
					$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: <a href="' . get_permalink() . '">' . the_excerpt() . '</a></p>';
				}
				elseif($wpbusdirman_field_association == 'description')
				{
					$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: <a href="' . get_permalink() . '">' . the_content(' ') . '</a></p>';
				}
				elseif (($wpbusdirman_field_association == 'tags')
					&& (get_the_term_list( $post->ID, $wpbdmposttypetags, '', ', ', '' )))
				{
					$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_term_list( $post->ID, $wpbdmposttypetags, '', ', ', '' ) . '</p>';
				}
			}
		}
	}

	return $html;
}

function wpbusdirman_view_edit_delete_listing_button()
{
	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);
	$html = '';

	$html .= '<div style="clear:both;"></div><div class="vieweditbuttons"><div class="vieweditbutton"><form method="post" action="' . get_permalink() . '"><input type="hidden" name="action" value="viewlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("View","WPBDM") . '" /></form></div>';
	if(is_user_logged_in())
	{
		global $current_user;
		get_currentuserinfo();
		$wpbusdirmanloggedinuseremail=$current_user->user_email;
		$wpbusdirmanauthoremail=get_the_author_meta('user_email');
		if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail)
		{
			$html .= '<div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="editlisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("Edit","WPBDM") . '" /></form></div><div class="vieweditbutton"><form method="post" action="' . $wpbusdirman_permalink . '"><input type="hidden" name="action" value="deletelisting" /><input type="hidden" name="wpbusdirmanlistingid" value="' . get_the_id() . '" /><input type="submit" value="' . __("Delete","WPBDM") . '" /></form></div>';
		}
	}
	$html .= '</div>';

	return $html;
}

function wpbusdirman_display_excerpt($count)
{
	echo wpbusdirman_post_excerpt($count);
}

function wpbusdirman_post_excerpt($count)
{ 	$wpbusdirman_gpid=wpbusdirman_gpid();
	$wpbusdirman_permalink=get_permalink($wpbusdirman_gpid);

	$html = '';

	$html .= '<div id="wpbdmlistings"';
	$isasticky=get_post_meta(get_the_ID(),'sticky');
	if(isset($isasticky) && !empty($isasticky)){
	$isasticky=$isasticky[0];}
	if(isset($isasticky) && ($isasticky == 'approved')){
	if($count&1){$html .= ' class="wpbdmoddsticky"';}else {$html .= ' class="wpbdmevensticky"';}}else {if($count&1){$html .= ' class="wpbdmodd"';}else {$html .= ' class="wpbdmeven"';}}
	$html .='><div class="listingthumbnail">' . wpbusdirman_display_the_thumbnail() . '</div><div class="listingdetails">';
	$html .= wpbusdirman_display_the_listing_fields();
	$html .= wpbusdirman_view_edit_delete_listing_button();
	$html .= '</div><div style="clear:both;"></div></div>';

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
		$wpbusdirmanpostimages=get_post_meta($post->ID, "thumbnail", $single=false);
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
		if ( has_post_thumbnail() )
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
	$wpbusdirmanpostimages=get_post_meta($post->ID, "thumbnail", $single=false);
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

	if(is_user_logged_in())
	{
		global $current_user;
		$html .= get_currentuserinfo();
		$wpbusdirmanloggedinuseremail=$current_user->user_email;
		$wpbusdirmanauthoremail=get_the_author_meta('user_email');
		$wpbdmpostissticky=get_post_meta($post->ID, "sticky", $single=true);
		if($wpbusdirmanloggedinuseremail == $wpbusdirmanauthoremail)
		{
			$html .= '<div id="editlistingsingleview">' . wpbusdirman_menu_button_editlisting() . wpbusdirman_menu_button_upgradelisting() . '</div><div style="clear:both;"></div>';
		}
	}
	 if(isset($wpbdmpostissticky)
	 	&& !empty($wpbdmpostissticky)
	 	&& ($wpbdmpostissticky  == 'approved') )
	 {
	 	$html .= '<span class="featuredlisting"><img src="' . $wpbusdirman_imagesurl . '/featuredlisting.png" alt="' . __("Featured Listing","WPBDM") . '" border="0" title="' . the_title(null, null, false) . '"></span>';
	}
	$html .= '<div class="singledetailsview">';
	$html .= wpbusdirman_the_listing_title();
	$html .= wpbusdirman_the_listing_category();
	$html .= wpbusdirman_the_listing_meta('single');
	$html .= wpbusdirman_the_listing_excerpt();
	$html .= wpbusdirman_the_listing_content();
	$html .= wpbusdirman_the_listing_tags();
	$html .= wpbusdirman_contactform($wpbusdirman_permalink,$post->ID,$commentauthorname='',$commentauthoremail='',$commentauthorwebsite='',$commentauthormessage='',$wpbusdirman_contact_form_errors='');
	$html .= '</div>';

	return $html;
}

function wpbusdirman_the_listing_title()
{
	global $wpbusdirman_field_vals_pfl,$wpbusdirmanconfigoptionsprefix;
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if($wpbusdirman_field_association == 'title')
			{
				$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: <a href="' . get_permalink() . '">' . the_title(null, null, false) . '</a></p>';
			}
		}
	}

	return $html;
}

function wpbusdirman_the_listing_tags()
{
	global $wpbdmposttypetags,$wpbusdirman_field_vals_pfl,$post,$wpbusdirmanconfigoptionsprefix;
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if (($wpbusdirman_field_association == 'tags')
				&& (get_the_term_list( $post->ID, $wpbdmposttypetags, '', ', ', '' )))
			{
				$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_term_list( $post->ID, $wpbdmposttypetags, '', ', ', '' ) . '</p>';
			}
		}
	}

	return $html;
}

function wpbusdirman_the_listing_excerpt()
{
	global $wpbusdirman_field_vals_pfl,$post,$wpbusdirmanconfigoptionsprefix;
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if (($wpbusdirman_field_association == 'excerpt')
				&& (has_excerpt($post->ID)))
			{
				$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_excerpt() . '</p>';
			}
		}
	}

	return $html;
}

function wpbusdirman_the_listing_content()
{
	global $wpbusdirman_field_vals_pfl,$wpbusdirmanconfigoptionsprefix;
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if($wpbusdirman_field_association == 'description')
			{
				$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_content() . '</p>';
			}
		}
	}

	return $html;
}

function wpbusdirman_the_listing_category()
{
	global $wpbdmposttypecategory,$wpbusdirman_field_vals_pfl,$post,$wpbusdirmanconfigoptionsprefix;
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if($wpbusdirman_field_association == 'category')
			{
				$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . get_the_term_list( $post->ID, $wpbdmposttypecategory, '', ', ', '' ) . '</p>';
			}
		}
	}

	return $html;
}

function wpbusdirman_the_listing_meta($excerptorsingle)
{
	global $post,$wpbusdirmanconfigoptionsprefix,$wpbusdirman_field_vals_pfl;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$overrideemailblocking=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_45'];
	$html = '';

	if($wpbusdirman_field_vals_pfl)
	{
		foreach($wpbusdirman_field_vals_pfl as $wpbusdirman_field_val)
		{
			$wpbusdirman_field_label=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_label_'.$wpbusdirman_field_val);
			$wpbusdirman_field_association=get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_association_'.$wpbusdirman_field_val);
			if($wpbusdirman_field_association == 'meta')
			{
				$wpbusdirman_field_value=get_post_meta(get_the_ID(), $wpbusdirman_field_label, $single = true);
				$wpbusdirman_field_value=preg_replace("/(http:\/\/[^\s]+)/","<a rel=\"no follow\" href=\"\$1\">\$1</a>",$wpbusdirman_field_value);
				$wpbusdirman_field_value=str_replace("\t",", ",$wpbusdirman_field_value);
				$wpbusdireman_field_hide = get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_hide_' . $wpbusdirman_field_val);
				if ("yes" == $wpbusdireman_field_hide)
				{
					continue;
				}
				if( isset($overrideemailblocking)
					&& !empty($overrideemailblocking)
					&& ($overrideemailblocking == "yes") )
				{
					if( isset($wpbusdirman_field_value)
						&& !empty($wpbusdirman_field_value) )
					{
						if(isset($excerptorsingle)
							&& !empty($excerptorsingle)
							&& ($excerptorsingle == 'excerpt'))
						{
							if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val) == 'yes')
							{
								$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . $wpbusdirman_field_value . '</p>';
							}
						}
						else
						{
							$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . $wpbusdirman_field_value . '</p>';
						}
					}
				}
				elseif($overrideemailblocking == "no")
				{
					if( isset($wpbusdirman_field_value)
						&& !empty($wpbusdirman_field_value)
						&&  !wpbusdirman_isValidEmailAddress($wpbusdirman_field_value) )
					{
						if(isset($excerptorsingle)
							&& !empty($excerptorsingle)
							&& ($excerptorsingle == 'excerpt'))
						{
							if(get_option($wpbusdirmanconfigoptionsprefix.'_postform_field_showinexcerpt_'.$wpbusdirman_field_val) == 'yes')
							{
								$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . $wpbusdirman_field_value . '</p>';
							}
						}
						else
						{
							$html .= '<p><label>' . $wpbusdirman_field_label . '</label>: ' . $wpbusdirman_field_value . '</p>';
						}
					}
				}
			}
		}
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

class WPBDP_Plugin {

	const VERSION = '2.0-BETA';
	const DB_VERSION = '1.1b';

	const POST_TYPE = 'wpbdm-directory';
	const POST_TYPE_CATEGORY = 'wpbdm-category';
	const POST_TYPE_TAGS = 'wpbdm-tags';


	public function __construct() {
		if (is_admin()) {
			$this->admin = new WPBDP_Admin();
		}

		$this->settings = new WPBDP_Settings();

		add_action('init', array($this, 'install_or_update_plugin'), 0); // while testing
		add_action('init', array($this, '_register_post_type'));
	}

	public function install_or_update_plugin() {
		global $wpdb;

		if ($oldversion = get_option('wpbdp-db-version', '1.0')) {
			if (version_compare($oldversion, '2.0') < 0) {
				$this->settings->upgrade_options();
				// delete_option('wpbusdirman_db_version');
			}
		}

		// update_option('wpbdp-db-version', self::DB_VERSION);
		
	}

	function _register_post_type() {
		$post_type_slug = $this->settings->get('permalinks-directory-slug', self::POST_TYPE);
		$category_slug = $this->settings->get('permalinks-category-slug', self::POST_TYPE_CATEGORY);
		$tags_slug = $this->settings->get('permalinks-tags-slug', self::POST_TYPE_TAGS);

		$labels = array(
			'name' => _x('Directory', 'post type general name'),
			'singular_name' => _x('Directory', 'post type singular name'),
			'add_new' => _x('Add New Listing', 'listing'),
			'add_new_item' => __('Add New Listing'),
			'edit_item' => __('Edit Listing'),
			'new_item' => __('New Listing'),
			'view_item' => __('View Listing'),
			'search_items' => __('Search Listings'),
			'not_found' =>  __('No listings found'),
			'not_found_in_trash' => __('No listings found in trash'),
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
			'supports' => array('title','editor','author','categories','tags','thumbnail','excerpt','comments','custom-fields','trackbacks')
		);

		register_post_type(self::POST_TYPE, $args);

		register_taxonomy(self::POST_TYPE_CATEGORY, self::POST_TYPE, array( 'hierarchical' => true, 'label' => 'Directory Categories', 'singular_name' => 'Directory Category', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count','query_var' => true, 'rewrite' => array('slug' => $category_slug) ) );
		register_taxonomy(self::POST_TYPE_TAGS, self::POST_TYPE, array( 'hierarchical' => false, 'label' => 'Directory Tags', 'singular_name' => 'Directory Tag', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count', 'query_var' => true, 'rewrite' => array('slug' => $tags_slug) ) );

		if (function_exists('flush_rewrite_rules'))
			flush_rewrite_rules(false);
	}


}

$wpbdp = new WPBDP_Plugin();


