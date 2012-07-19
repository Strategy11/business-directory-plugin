<?php if (!$stickies && !have_posts()): ?>
    <?php _ex("No listings found in category.", 'templates', "WPBDM"); ?>
<?php else: ?>
    <?php echo $stickies; ?>

    <?php while (have_posts()): the_post(); ?>
        <?php echo wpbdp_render_listing(null, 'excerpt'); ?>
    <?php endwhile; ?>

    <div class="pagination">
        <?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?>
        <?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?>
    </div>
<?php endif; ?>