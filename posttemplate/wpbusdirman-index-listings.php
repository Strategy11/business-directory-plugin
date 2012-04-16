<div id="wpbdmentry">
<?php

if(!isset($excludebuttons))
{?>
	<div id="lco">
	<div class="title"><?php print(wpbusdirman_post_menu_button_submitlisting());?>
	<?php print(wpbusdirman_post_menu_button_directory());?></div>
	<div class="button" style="margin:0;padding:0;"></div>
<div style="clear:both;"></div></div>

<?php }

include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-template-globals.php');

// Display featured/sticky listings
$wpbdmisindex=1;
include(WPBUSDIRMAN_TEMPLATES_PATH . '/wpbusdirman-sticky-loop.php');


// Display regular listings
	$args=array(
	  'post_type' => $wpbdmposttype,
	  'post_status' => 'publish',
	'paged'=>$paged,
	'orderby'=>$wpbdmorderlistingsby,
	'order'=>$wpbdmsortorderlistings,
	'post__not_in' => $ids
	);
	query_posts($args);

	if ( have_posts() )
	{ $count = 0;
		while ( have_posts() )
		{
			the_post();$count++;
			print(wpbusdirman_post_excerpt($count));
		}
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
?>
	<p><?php _e("There were no listings found in the directory","WPBDM"); ?></p>
<?php
	}
	wp_reset_query();
?>
</div>