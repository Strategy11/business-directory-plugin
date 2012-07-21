<?php if (!isset($stickies)) $stickies = null; ?>

<?php if (!$stickies && !have_posts()): ?>
    <?php _ex("No listings found in category.", 'templates', "WPBDM"); ?>
<?php else: ?>
    <div class="listings">
        <?php echo $stickies; ?>

        <?php while (have_posts()): the_post(); ?>
            <?php echo wpbdp_render_listing(null, 'excerpt'); ?>
        <?php endwhile; ?>

        <div class="wpbdp-pagination">
            <span class="prev"><?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?></span>
            <span class="next"><?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php
/*
<div id="wpbdmentry">
<?php if(!$excludebuttons): ?>
	<div id="lco">
		<div class="title">
			<?php print(wpbusdirman_post_menu_button_submitlisting());?>
			<?php print(wpbusdirman_post_menu_button_directory());?>
		</div>
		<div class="button" style="margin:0;padding:0;"></div>
		<div style="clear:both;"></div>
	</div>
<?php endif; ?>

		<?php if (!have_posts()): ?>
			<?php _ex("No listings found.", 'templates', "WPBDM"); ?>
			<br />
			<?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
						 	   _x('Return to directory', 'templates', 'WPBDM')); ?>
		<?php endif; ?>

		<?php if (have_posts()): ?>
			<?php while(have_posts()): the_post(); ?>
				<?php echo wpbusdirman_post_excerpt(); ?>
			<?php endwhile; ?>

			<div class="navigation">
				<?php if (function_exists('wp_pagenavi')): ?>
					<?php wp_pagenavi(null); ?>
				<?php elseif (function_exists('wp_paginate')): ?>
					<?php wp_paginate(); ?>
				<?php else: ?>
					<div class="alignleft"><?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?></div>
					<div class="alignright"><?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
</div>*/?>