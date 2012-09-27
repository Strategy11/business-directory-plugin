<?php get_header(); ?>
<div id="content">

<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <?php wpbdp_the_bar(array('search' => true)); ?>

    <h2 class="category-name"><?php echo wpbusdirman_post_catpage_title(); ?></h2>    

    <?php wpbusdirman_sticky_loop(); ?>

    <?php if (!have_posts()): ?>
        <?php _ex("No listings found in category.", 'templates', "WPBDM"); ?>
    <?php else: ?>
        <div class="listings">
            
			<?php while(have_posts()): the_post(); ?>
				<?php wpbdp_the_listing_excerpt(); ?>
			<?php endwhile; ?>

            <div class="wpbdp-pagination">
            <?php if (function_exists('wp_pagenavi')) : ?>
                    <?php wp_pagenavi(); ?>
            <?php elseif (function_exists('wp_paginate')): ?>
                    <?php wp_paginate(); ?>
            <?php else: ?>
                <span class="next"><?php previous_posts_link(_x('&laquo; Previous', 'templates', 'WPBDM')); ?></span>
                <span class="prev"><?php next_posts_link(_x('Next &raquo;', 'templates', 'WPBDM')); ?></span>
            <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</div>
<?php get_footer(); ?>