<?php	
	$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );

	//print_r($term);
		$wpbdm_order_listings_by=wpbdp_get_option('listings-order-by');

	if(isset($wpbdm_order_listings_by) && !empty($wpbdm_order_listings_by)){$wpbdmorderlistingsby=$wpbdm_order_listings_by;}
	else { $wpbdmorderlistingsby='date';}

		$wpbdm_sort_order_listings=wpbdp_get_option('listings-sort');

	if(isset($wpbdm_sort_order_listings) && !empty($wpbdm_sort_order_listings)){$wpbdmsortorderlistings=$wpbdm_sort_order_listings;}
	else { $wpbdmsortorderlistings='ASC';}


	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

	$ids = !isset($ids) ? array() : $ids;