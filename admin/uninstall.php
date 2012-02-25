<?php
function wpbusdirman_uninstall()
{
	global $message;
	$dirname="wpbdm";
	$html = '';

	if( isset($_REQUEST['action'])
		&& !empty($_REQUEST['action']) )
	{
		if($_REQUEST['action'] == 'wpbusdirman_d_install')
		{
			$html .= wpbusdirman_d_install();
		}
	}
	if( !isset($_REQUEST['action'])
		|| empty($_REQUEST['action']) )
	{
		$html .= wpbdp_admin_header();
		$html .= "<h3 style=\"padding:10px;\">" . __("Uninstall","WPBDM") . "</h3>";
		if(isset($message)
			&& !empty($message))
		{
			$html .= $message;
		}
		$html .= "<p>" . __("You have arrived at this page by clicking the Uninstall link. If you are certain you wish to uninstall the plugin, please click the link below to proceed. Please note that all your data related to the plugin, your ads, images and everything else created by the plugin will be destroyed","WPBDM") . "<p><b>" . __("Important Information","WPBDM") . "</b></p><blockquote><p>1." . __("If you want to keep your user uploaded images, please download the folder $dirname, which you will find inside your uploads directory, to your local drive for later use or rename the folder to something else so the uninstaller can bypass it","WPBDM") . "</p></blockquote>: <a href=\"?page=wpbdman_m1&action=wpbusdirman_d_install\">" . __("Proceed with Uninstalling Business Directory Plugin Uninstall","WPBDM") . "</a>";
		$html .= wpbdp_admin_footer();
	}

	echo $html;
}

function wpbusdirman_d_install()
{
	global $wpdb,$wpbusdirman_plugin_path,$table_prefix,$wpbusdirman_plugin_dir,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbdmposttype;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$wpbdmdraftortrash=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_47'];
	$wpbusdirman_myterms = get_terms($wpbdmposttypecategory, 'orderby=name&hide_empty=0');
	$html = '';


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
				$wpbusdirman_unints_postarr = array();
				$wpbusdirman_unints_postarr['ID'] = $wpbusdirman_post;
				$wpbusdirman_unints_postarr['post_type'] = $wpbdmposttype;
				$wpbusdirman_unints_postarr['post_status'] = $wpbdmdraftortrash;
				wp_update_post( $wpbusdirman_unints_postarr );
			}
		}

	$wpbusdirman_query="DELETE FROM $wpdb->options WHERE option_name LIKE '%wpbusdirman_%'";
	@mysql_query($wpbusdirman_query);
	wp_clear_scheduled_hook('wpbusdirman_listingexpirations_hook');
	$wpbdm_pluginfile=$wpbusdirman_plugin_dir."/wpbusdirman.php";
	$wpbusdirman_current = get_option('active_plugins');
	array_splice($wpbusdirman_current, array_search( $wpbdm_pluginfile, $wpbusdirman_current), 1 );
	update_option('active_plugins', $wpbusdirman_current);
	do_action('deactivate_' . $wpbdm_pluginfile );
	$html .= "<div style=\"padding:50px;font-weight:bold;\"><p>" . __("Almost done...","WPBDM") . "</p><h1>" . __("One More Step","WPBDM") . "</h1><a href=\"plugins.php?plugin=$wpbusdirman_plugin_dir&deactivate=true\">" . __("Please click here to complete the uninstallation process","WPBDM") . "</a></h1></div>";
//Mike Bronner: is this needed?
//	die;

	return $html;
}
