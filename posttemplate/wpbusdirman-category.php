<?php get_header(); ?>

<div>
<div id="content">

<div id="wpbdmentry"><div class="fixpadding">

<div id="lco">
<div class="title"><?php print(wpbusdirman_post_catpage_title());?></div>
<div class="button"><?php print(wpbusdirman_post_menu_buttons());?></div>
<div style="clear:both;"></div>
</div>

<?php

include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-template-globals.php');

// Display featured/sticky listings
include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-sticky-loop.php');


// Display regular listings

//wpbusdirman_catpage_query();

	$catortag=$term->taxonomy;

	if($catortag == $wpbdmposttypecategory)
	{
		$args=array(
		  $wpbdmposttypecategory => $term->slug,
		  'post_type' => $wpbdmposttype,
		  'post_status' => 'publish',
		  'posts_per_page' => 0,
		'paged'=>$paged,
		'orderby'=>$wpbdmorderlistingsby,
		'order'=> $wpbdmsortorderlistings,
		'post__not_in' => $ids
		);
	}
	elseif($catortag == $wpbdmposttypetags) {
		$args=array(
		  $wpbdmposttypetags => $term->name,
		  'post_type' => $wpbdmposttype,
		  'post_status' => 'publish',
		  'posts_per_page' => 0,
		'paged'=>$paged,
		'orderby'=>$wpbdmorderlistingsby,
		'order'=> $wpbdmsortorderlistings,
		'post__not_in' => $ids
		);
	}

		query_posts($args);

	if ( have_posts() )
	{ $count = 0;
		while ( have_posts() )
		{
			the_post(); $count++;
			print(wpbusdirman_post_excerpt($count));
		}
		wp_reset_query();
?>
	<div class="navigation">
<?php
		if(function_exists('wp_pagenavi'))
		{
			wp_pagenavi();
		}
		elseif(function_exists('wp_paginate'))
		{
			wp_paginate();
		}
		else
		{
?>
		<div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
		<div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
<?php
		}
?>
	</div>
<?php
	}
	else
	{
		_e("No listings found in category.", "WPBDM");
		echo '<br />';
		echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
					 _x('Return to directory', 'WPBDM'));
	}
?>
	</div></div><!--close div wpbdmentry--><!--close div fixpadding-->

	</div>
	</div>

<?php get_footer(); ?>