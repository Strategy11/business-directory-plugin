
<div class="wpbdmentry">
<div class="menubuttons">
<?php
	print(wpbusdirman_post_catpage_title());
	print(wpbusdirman_post_menu_buttons());?>
</div>

<?php
	wpbusdirman_catpage_query();
	//query_posts($wpbdmposttypecategory.'=Test Category 2&post_statuts=publish&post_type='.$wpbdmposttype);
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
		_e("No listings found in category","WPBDM");
	}
?>
</div>
