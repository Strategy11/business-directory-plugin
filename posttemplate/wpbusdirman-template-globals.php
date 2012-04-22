<?php	global $wpbdmposttype,$wpbdmposttypecategory,$wpbusdirmanconfigoptionsprefix,$wpbdmposttypetags;

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