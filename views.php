<?php
/*
 * General directory views
 */

if (!class_exists('WPBDP_DirectoryController')) {

/*<oldstuff>*/
function wpbusdirmanui_directory_screen() {
	global $wpbdmimagesurl,$wpbusdirman_imagesurl,$wpbusdirman_plugin_path,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbdmposttype;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbusdirman_contact_errors=false;
	$html = '';

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
				if(isset($_REQUEST['commentauthorname']) && !empty($_REQUEST['commentauthorname'])) {
					$commentauthorname=htmlspecialchars( $_REQUEST['commentauthorname'] );
				}
				
				if(isset($_REQUEST['commentauthoremail']) && !empty($_REQUEST['commentauthoremail'])) {
					$commentauthoremail=$_REQUEST['commentauthoremail'];
				}

				if(isset($_REQUEST['commentauthorwebsite']) && !empty($_REQUEST['commentauthorwebsite'])) {
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
				&& !(wpbdp_validate_value('URLValidator', $commentauthorwebsite)) )
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
				$time = date_i18n( __('l F j, Y \a\t g:i a'), current_time( 'timestamp' ) );
				$message = "Name: $commentauthorname
				Email: $commentauthoremail
				Website: $commentauthorwebsite

				$commentauthormessage

				Time: $time

				";

				if(wp_mail( $wpbdmsendtoemail, $subject, $message, $headers )) {
					$html .= "<p>" . __("Your message has been sent","WPBDM") . "</p>";
				} else {
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
					$existingtermlengths=get_post_meta($wpbusdirmanlistingpostid, "_wpbdp_termlength", false);

						if(!in_array($catdur,$existingtermlengths))
						{
								add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_termlength", $catdur, false);
						}
				}
			}

			if($wpbusdirmanfeeoptions)
			{
				foreach($wpbusdirmanfeeoptions as $feeopid)
				{
					$wpbusdirmanlistingcost=get_option($wpbusdirmanconfigoptionsprefix.'_settings_fees_amount_'.$feeopid);
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_costoflisting", $wpbusdirmanlistingcost, false) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_costoflisting", $wpbusdirmanlistingcost);
					add_post_meta($wpbusdirmanlistingpostid, "_wpbdp_listingfeeid", $feeopid, false) or update_post_meta($wpbusdirmanlistingpostid, "_wpbdp_costoflisting", $feeopid);
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

	return $html;
}
/*</oldstuff>*/


class WPBDP_DirectoryController {

	public function __construct() {	}

	public function init() {
		/* shortcodes */
		add_shortcode('WPBUSDIRMANUI', array($this, 'dispatch'));
		add_shortcode('business-directory', array($this, 'dispatch'));

		/* filters */
		// add_filter('wpbdm_show-directory', array($this, '_main_page'), 10, 0);
	}

	public function dispatch() {
    	$action = wpbdp_getv($_REQUEST, 'action');

    	switch ($action) {
    		default:
    			return $this->main_page();
    			break;
    	}

		// $html .= apply_filters('wpbdm_show-directory', null);
		// return 'x';
	}

	public function locate_template($template, $allow_override=true) {
		$template_file = '';

		if (!is_array($template))
			$template = array($template);

		if ($allow_override) {
			$search_for = array();

			foreach ($template as $t) {
				$search_for[] = $t . '.tpl.php';
				$search_for[] = $t . '.php';
				$search_for[] = 'single/' . $t . '.tpl.php';
				$search_for[] = 'single/' . $t . '.php';
			}

			$template_file = locate_template($search_for);
		}

		if (!$template_file) {
			foreach ($template as $t) {
				$template_path = WPBDP_TEMPLATES_PATH . '/' . $t . '.tpl.php'; 
				
				if (file_exists($template_path)) {
					$template_file = $template_path;
					break;
				}
			}
		}

		return $template_file;
	}

	public function render($template, $vars=array(), $allow_override=true) {
		return wpbdp_render_page($this->locate_template($template, $allow_override), $vars, false);
	}

	/*
	 * Directory views/actions
	 */
	private function main_page() {
		$html = '';

		if ( count(get_terms(wpbdp_categories_taxonomy(), array('hide_empty' => 0))) == 0 ) {
			if (is_user_logged_in() && current_user_can('install_plugins')) {
				$html .= "<p>" . _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM') . "</p>";
			} else {
				$html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
			}
		}

		$html .= $this->render(array('businessdirectory-main-page-categories', 'wpbusdirman-index-categories'),
							   array(
							   	'submit_listing_button' => wpbusdirman_post_menu_button_submitlisting(),
							   	'view_listings_button' => wpbusdirman_post_menu_button_viewlistings()
							   ));

		if (wpbdp_get_option('show-listings-under-categories')) {
			$html .= $this->render(array('businessdirectory-listings', 'wpbusdirman-index-listings'),
								   array(
									'exclude_buttons' => 1,								   	
								   	'wpbdmposttype' => wpbdp_post_type(), /* deprecated */
									'excludebuttons' => 1, /* deprecated */
								   ));
		}

		return $html;
	}

			// if($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_44'] == "yes")
			// {

			// 	if(file_exists(get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php'))
			// 	{
			// 		include get_stylesheet_directory() . '/single/wpbusdirman-index-listings.php';
			// 	}
			// 	elseif(file_exists(get_template_directory() . '/single/wpbusdirman-index-listings.php'))
			// 	{
			// 		include get_template_directory() . '/single/wpbusdirman-index-listings.php';
			// 	}
			// 	elseif(file_exists(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php'))
			// 	{
			// 		include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
			// 	}
			// 	else
			// 	{
			// 		include WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-index-listings.php';
			// 	}	


}

}