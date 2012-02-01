<?php

function wpbusdirman_install()
{

	global $wpdb,$wpbusdirman_db_version,$wpbdmposttype,$wpbusdirmanconfigoptionsprefix;
	$wpbusdirman_config_options=get_wpbusdirman_config_options();
	$installed_ver = get_option( "wpbusdirman_db_version" );


	// Form Labels
	$wpbusdirman_postform_field_label_1=__("Business Name","WPBDM");
	$wpbusdirman_postform_field_label_2=__("Business Genre","WPBDM"); // Display Listing associated categories
	$wpbusdirman_postform_field_label_3=__("Short Business Description","WPBDM");
	$wpbusdirman_postform_field_label_4=__("Long Business Description","WPBDM");
	$wpbusdirman_postform_field_label_5=__("Business Website Address","WPBDM");
	$wpbusdirman_postform_field_label_6=__("Business Phone Number","WPBDM");
	$wpbusdirman_postform_field_label_7=__("Business Fax","WPBDM");
	$wpbusdirman_postform_field_label_8=__("Business Contact Email","WPBDM");
	$wpbusdirman_postform_field_label_9=__("Business Tags","WPBDM");

			if( isset($wpbusdirman_config_options) && !empty($wpbusdirman_config_options) && (is_array($wpbusdirman_config_options)) )
			{
				$wpbusdirman_installed_already=1;
			}
			else { $wpbusdirman_installed_already=0; }


	if(!$wpbusdirman_installed_already)
	{
	  	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	  	//	Install the plugin
	  	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

		  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			// Add Version Number
			add_option("wpbusdirman_db_version", $wpbusdirman_db_version);

			// Add settings options


			// Add default form options
			add_option("wpbusdirman_postform_field_label_1", $wpbusdirman_postform_field_label_1);
			add_option("wpbusdirman_postform_field_label_2", $wpbusdirman_postform_field_label_2);
			add_option("wpbusdirman_postform_field_label_3", $wpbusdirman_postform_field_label_3);
			add_option("wpbusdirman_postform_field_label_4", $wpbusdirman_postform_field_label_4);
			add_option("wpbusdirman_postform_field_label_5", $wpbusdirman_postform_field_label_5);
			add_option("wpbusdirman_postform_field_label_6", $wpbusdirman_postform_field_label_6);
			add_option("wpbusdirman_postform_field_label_7", $wpbusdirman_postform_field_label_7);
			add_option("wpbusdirman_postform_field_label_8", $wpbusdirman_postform_field_label_8);
			add_option("wpbusdirman_postform_field_label_9", $wpbusdirman_postform_field_label_9);

			// text = 1, select = 2, textarea=3 radio =4 multiselect =5 checkbox =6
			add_option("wpbusdirman_postform_field_type_1", 1);
			add_option("wpbusdirman_postform_field_type_2", 2);
			add_option("wpbusdirman_postform_field_type_3", 3);
			add_option("wpbusdirman_postform_field_type_4", 3);
			add_option("wpbusdirman_postform_field_type_5", 1);
			add_option("wpbusdirman_postform_field_type_6", 1);
			add_option("wpbusdirman_postform_field_type_7", 1);
			add_option("wpbusdirman_postform_field_type_8", 1);
			add_option("wpbusdirman_postform_field_type_9", 1);

			add_option("wpbusdirman_postform_field_options_1", '');
			add_option("wpbusdirman_postform_field_options_2", '');
			add_option("wpbusdirman_postform_field_options_3", '');
			add_option("wpbusdirman_postform_field_options_4", '');
			add_option("wpbusdirman_postform_field_options_5", '');
			add_option("wpbusdirman_postform_field_options_6", '');
			add_option("wpbusdirman_postform_field_options_7", '');
			add_option("wpbusdirman_postform_field_options_8", '');
			add_option("wpbusdirman_postform_field_options_9", '');

			add_option("wpbusdirman_postform_field_order_1", 1);
			add_option("wpbusdirman_postform_field_order_2", 2);
			add_option("wpbusdirman_postform_field_order_3", 3);
			add_option("wpbusdirman_postform_field_order_4", 4);
			add_option("wpbusdirman_postform_field_order_5", 5);
			add_option("wpbusdirman_postform_field_order_6", 6);
			add_option("wpbusdirman_postform_field_order_7", 7);
			add_option("wpbusdirman_postform_field_order_8", 8);
			add_option("wpbusdirman_postform_field_order_9", 9);


			add_option("wpbusdirman_postform_field_association_1", 'title');
			add_option("wpbusdirman_postform_field_association_2", 'category');
			add_option("wpbusdirman_postform_field_association_3", 'excerpt');
			add_option("wpbusdirman_postform_field_association_4", 'description');
			add_option("wpbusdirman_postform_field_association_5", 'meta');
			add_option("wpbusdirman_postform_field_association_6", 'meta');
			add_option("wpbusdirman_postform_field_association_7", 'meta');
			add_option("wpbusdirman_postform_field_association_8", 'meta');
			add_option("wpbusdirman_postform_field_association_9", 'tags');

			add_option("wpbusdirman_postform_field_validation_1", 'missing');
			add_option("wpbusdirman_postform_field_validation_2", 'missing');
			add_option("wpbusdirman_postform_field_validation_3", '');
			add_option("wpbusdirman_postform_field_validation_4", 'missing');
			add_option("wpbusdirman_postform_field_validation_5", 'url');
			add_option("wpbusdirman_postform_field_validation_6", '');
			add_option("wpbusdirman_postform_field_validation_7", '');
			add_option("wpbusdirman_postform_field_validation_8", 'email');
			add_option("wpbusdirman_postform_field_validation_9", '');

			add_option("wpbusdirman_postform_field_required_1", 'yes');
			add_option("wpbusdirman_postform_field_required_2", 'yes');
			add_option("wpbusdirman_postform_field_required_3", 'no');
			add_option("wpbusdirman_postform_field_required_4", 'yes');
			add_option("wpbusdirman_postform_field_required_5", 'no');
			add_option("wpbusdirman_postform_field_required_6", 'no');
			add_option("wpbusdirman_postform_field_required_7", 'no');
			add_option("wpbusdirman_postform_field_required_8", 'yes');
			add_option("wpbusdirman_postform_field_required_9", 'no');


			add_option("wpbusdirman_postform_field_showinexcerpt_1", 'yes');
			add_option("wpbusdirman_postform_field_showinexcerpt_2", 'yes');
			add_option("wpbusdirman_postform_field_showinexcerpt_3", 'no');
			add_option("wpbusdirman_postform_field_showinexcerpt_4", 'no');
			add_option("wpbusdirman_postform_field_showinexcerpt_5", 'yes');
			add_option("wpbusdirman_postform_field_showinexcerpt_6", 'yes');
			add_option("wpbusdirman_postform_field_showinexcerpt_7", 'no');
			add_option("wpbusdirman_postform_field_showinexcerpt_8", 'no');
			add_option("wpbusdirman_postform_field_showinexcerpt_9", 'no');

			add_option("wpbusdirman_postform_field_hide_1", 'no');
			add_option("wpbusdirman_postform_field_hide_2", 'no');
			add_option("wpbusdirman_postform_field_hide_3", 'no');
			add_option("wpbusdirman_postform_field_hide_4", 'no');
			add_option("wpbusdirman_postform_field_hide_5", 'no');
			add_option("wpbusdirman_postform_field_hide_6", 'no');
			add_option("wpbusdirman_postform_field_hide_7", 'no');
			add_option("wpbusdirman_postform_field_hide_8", 'no');
			add_option("wpbusdirman_postform_field_hide_9", 'no');


		/*wp_schedule_event( time(), 'daily', 'wpbusdirman_listings_expirations' );*/

	 }
     else
     {

	  	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	  	//	Update the plugin
	  	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		if( $installed_ver != $wpbusdirman_db_version )
		{
			update_option("wpbusdirman_db_version", $wpbusdirman_db_version);
		}


    }

    $plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'WPBDM', null, $plugin_dir.'/languages' );


}

function wpbusdirman_dir_post_type()
{

	global $wpbdmposttype,$wpbdmposttypecategory,$wpbdmposttypetags,$wpbusdirmanconfigoptionsprefix;

$wpbusdirman_config_options=get_wpbusdirman_config_options();


if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_49']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_49'])){$wpbdmposttypeslug=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_49'];}else {$wpbdmposttypeslug=$wpbdmposttype;}
if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_50']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_50'])){$wpbdmposttypecategoryslug=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_50'];}else {$wpbdmposttypecategoryslug=$wpbdmposttypecategory;}
if(isset($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_51']) && !empty($wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_51'])){$wpbdmposttypetagslug=$wpbusdirman_config_options[$wpbusdirmanconfigoptionsprefix.'_settings_config_51'];}else {$wpbdmposttypetagslug=$wpbdmposttypetags;}



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
			    'rewrite' => array('slug'=>$wpbdmposttypeslug,'with_front'=>false),
			    'capability_type' => 'post',
			    'hierarchical' => false,
			    'menu_position' => null,
			    'supports' => array('title','editor','author','categories','tags','thumbnail','excerpt','comments','custom-fields','trackbacks')
			  );
			  register_post_type($wpbdmposttype,$args);

	//Register directory category taxonomy
	register_taxonomy( $wpbdmposttypecategory, $wpbdmposttype, array( 'hierarchical' => true, 'label' => 'Directory Categories', 'singular_name' => 'Directory Category', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count','query_var' => true, 'rewrite' => array('slug'=>$wpbdmposttypecategoryslug) ) );
	register_taxonomy( $wpbdmposttypetags, $wpbdmposttype, array( 'hierarchical' => false, 'label' => 'Directory Tags', 'singular_name' => 'Directory Tag', 'show_in_nav_menus' => true, 'update_count_callback' => '_update_post_term_count', 'query_var' => true, 'rewrite' => array('slug'=>$wpbdmposttypetagslug) ) );


		if(function_exists('flush_rewrite_rules')){flush_rewrite_rules( false );}
	}



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
		$html .= wpbusdirman_admin_head();
		$html .= "<h3 style=\"padding:10px;\">" . __("Uninstall","WPBDM") . "</h3>";
		if(isset($message)
			&& !empty($message))
		{
			$html .= $message;
		}
		$html .= "<p>" . __("You have arrived at this page by clicking the Uninstall link. If you are certain you wish to uninstall the plugin, please click the link below to proceed. Please note that all your data related to the plugin, your ads, images and everything else created by the plugin will be destroyed","WPBDM") . "<p><b>" . __("Important Information","WPBDM") . "</b></p><blockquote><p>1." . __("If you want to keep your user uploaded images, please download the folder $dirname, which you will find inside your uploads directory, to your local drive for later use or rename the folder to something else so the uninstaller can bypass it","WPBDM") . "</p></blockquote>: <a href=\"?page=wpbdman_m1&action=wpbusdirman_d_install\">" . __("Proceed with Uninstalling Business Directory Plugin Uninstall","WPBDM") . "</a>";
		$html .= wpbusdirman_admin_foot();
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

?>