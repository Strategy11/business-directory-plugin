<?php
	get_header();
?>
<div id="wpbdmentry"><div class="fixpadding">

<?php	if ( have_posts() )
	{ $count = 0;?>

<div id="lco">
<div class="title"><?php printf( __( 'Search Results for: %s', 'WPBDM' ), '<span>' . get_search_query() . '</span>' ); ?></div>
<div class="buttonform"><?php print(wpbusdirman_post_menu_buttons());?></div>
<div style="clear:both;"></div>
</div>

<?php

include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-template-globals.php');

// Display featured/sticky listings
$isforsearch=1;
include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-sticky-loop.php');


// Display regular listings

		while ( have_posts() )
		{

			the_post(); $count++;if(!in_array($post->ID,$ids)):
			print(wpbusdirman_post_excerpt($count));
			endif;
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
		<div class="alignleft"><?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?></div>
		<div class="alignright"><?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?></div>
<?php
		}
?>
	</div>
<?php
	}
	else
	{
		_ex("No listings found in category", 'templates', "WPBDM");
	}
?>
	</div></div><!--close div wpbdmentry--><!--close div fixpadding-->

<?php get_footer(); ?>