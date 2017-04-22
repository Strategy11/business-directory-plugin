<?php wpbdp_the_listing_sort_options(); ?>

<div class="listings wpbdp-listings-list list">
    <?php if ( ! $query->have_posts() ): ?>
        <span class="no-listings">
            <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
        </span>
    <?php else: ?>
        <?php while ( $query->have_posts() ): $query->the_post(); ?>
            <?php echo wpbdp_render_listing( null, 'excerpt' ); ?>
        <?php endwhile; ?>

        <div class="wpbdp-pagination">
        <?php if (function_exists('wp_pagenavi')) : ?>
                <?php wp_pagenavi( array( 'query' => $query ) ); ?>
        <?php else: ?>
            <span class="prev"><?php previous_posts_link( _x( '&laquo; Previous ', 'templates', 'WPBDM' ) ); ?></span>
            <span class="next"><?php next_posts_link( _x( 'Next &raquo;', 'templates', 'WPBDM'), $query->max_num_pages ); ?></span>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
