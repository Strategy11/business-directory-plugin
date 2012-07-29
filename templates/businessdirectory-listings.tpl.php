<?php if (!isset($stickies)) $stickies = null; ?>
<?php if (!isset($excludebuttons)) $excludebuttons = true; ?>

<?php if (!$excludebuttons): ?>
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>
<?php endif; ?>

<?php if (!$stickies && !have_posts()): ?>
    <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
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