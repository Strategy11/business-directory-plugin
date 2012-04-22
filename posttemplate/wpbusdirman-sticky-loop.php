<?php

//if(!is_paged()) :

// Run a loop for custom post stickies

if(isset($wpbdmisindex) && ($wpbdmisindex ==1))
{
	$stickies = array(
		'post_type' => $wpbdmposttype,
		'posts_per_page' => 0,
		'post_status' => 'publish',
		'paged'=>$paged,
		'orderby'=>$wpbdmorderlistingsby,
		'order'=> $wpbdmsortorderlistings,
		'meta_query' => array(
			array(
				'key' => '_wpbdp_sticky',
				'value' => 'approved'
			)
		)
	);
}
elseif(isset($isforsearch) && ( $isforsearch == 1 ))
{

	$sepstickies = 1;
}
else
{

	$catortag=$term->taxonomy;
	if($catortag == $wpbdmposttypecategory)
	{

		$stickies = array(
		$wpbdmposttypecategory => $term->name,
			'post_type' => $wpbdmposttype,
			'posts_per_page' => 0,
			'post_status' => 'publish',
			'paged'=>$paged,
			'orderby'=>$wpbdmorderlistingsby,
			'order'=> $wpbdmsortorderlistings,
			'meta_query' => array(
				array(
					'key' => '_wpbdp_sticky',
					'value' => 'approved'
				)
			)
		);
	}
	elseif($catortag == $wpbdmposttypetags) {
		$stickies = array(
		$wpbdmposttypetags => $term->name,
			'post_type' => $wpbdmposttype,
			'posts_per_page' => 0,
			'post_status' => 'publish',
			'paged'=>$paged,
			'orderby'=>$wpbdmorderlistingsby,
			'order'=> $wpbdmsortorderlistings,
			'meta_query' => array(
				array(
					'key' => '_wpbdp_sticky',
					'value' => 'approved'
				)
			)
		);
	}

}

if(isset($sepstickies) && ($sepstickies == 1))
{
	$ids = array();
	if (have_posts()) :
	$count = 0;
	while (have_posts()) : the_post();
	$count++;
	$stickypost=get_post_meta($post->ID,'_wpbdp_sticky',true);

	if( $stickypost == "approved"){

		$ids[] = get_the_ID();

		print(wpbusdirman_post_excerpt($count));
	}

		endwhile;
	endif;
}
else
{

	query_posts($stickies);
	$ids = array();
	if (have_posts()) :
	$count = 0;
	while (have_posts()) : the_post();
	$count++;
		$ids[] = get_the_ID();

		print(wpbusdirman_post_excerpt($count));

		endwhile;
	endif;
}

wp_reset_query();

//endif;