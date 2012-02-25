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


?>