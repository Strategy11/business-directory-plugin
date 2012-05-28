<?php get_header(); ?>

<div>
<div id="content">

<div id="wpbdmentry">
	<div class="fixpadding">

		<div id="lco">
			<div class="title"><?php print(wpbusdirman_post_catpage_title());?></div>
			<div class="button"><?php print(wpbusdirman_post_menu_buttons());?></div>
			<div style="clear:both;"></div>
		</div>

		<?php if (!have_posts()): ?>
			<?php _ex("No listings found in category.", 'templates', "WPBDM"); ?>		
		<?php endif; ?>

		<?php if (have_posts()): ?>
			<?php while(have_posts()): the_post(); ?>
				<?php echo wpbusdirman_post_excerpt(); ?>
			<?php endwhile; ?>

			<div class="navigation">
				<?php if (function_exists('wp_pagenavi')): ?>
					<?php wp_pagenavi(); ?>
				<?php elseif (function_exists('wp_paginate')): ?>
					<?php wp_paginate(); ?>
				<?php else: ?>
					<div class="alignleft"><?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?></div>
					<div class="alignright"><?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</div>

</div>
</div>


<?php get_footer(); ?>