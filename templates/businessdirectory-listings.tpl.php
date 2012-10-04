<?php if (!isset($stickies)) $stickies = null; ?>
<?php if (!isset($excludebuttons)) $excludebuttons = true; ?>

<?php if (!$excludebuttons): ?>
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>
<?php endif; ?>

<?php wpbdp_the_listing_sort_options(); ?>

<?php if (!have_posts()): ?>
    <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
<?php else: ?>
    <div class="listings">
        <?php while (have_posts()): the_post(); ?>
            <?php echo wpbdp_render_listing(null, 'excerpt'); ?>
        <?php endwhile; ?>

        <div class="wpbdp-pagination">
        <?php if (function_exists('wp_pagenavi')) : ?>
                <?php wp_pagenavi(); ?>
        <?php elseif (function_exists('wp_paginate')): ?>
                <?php wp_paginate(); ?>
        <?php else: ?>
            <span class="next"><?php previous_posts_link(_x('&laquo; Previous ', 'templates', 'WPBDM')); ?></span>
            <span class="prev"><?php next_posts_link(_x('Next &raquo;', 'templates', 'WPBDM')); ?></span>
        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>