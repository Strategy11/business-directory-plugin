<?php get_header(); ?>
<div id="content">

<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>

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
                <span class="prev"><?php next_posts_link(_x('&laquo; Older Entries', 'templates', 'WPBDM')); ?></span>
                <span class="next"><?php previous_posts_link(_x('Newer Entries &raquo;', 'templates', 'WPBDM')); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

</div>
<?php get_footer(); ?>