<?php
/**
 * Listings display template
 *
 * @package BDP/Templates/Listings
 */

// phpcs:disable
wpbdp_the_listing_sort_options();
?>

<div id="wpbdp-listings-list" class="listings wpbdp-listings-list list">
    <?php if ( ! $query->have_posts() ): ?>
        <span class="no-listings">
            <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
        </span>
    <?php else: ?>
        <?php
        while ( $query->have_posts() ) :
            $query->the_post();
            echo wpbdp_render_listing( null, 'excerpt' );
        endwhile;
        ?>

        <div class="wpbdp-pagination">
        <?php
        if ( function_exists('wp_pagenavi' ) ) :
            wp_pagenavi( array( 'query' => $query ) );
        else:
        ?>
            <span class="prev"><?php previous_posts_link( _x( '&laquo; Previous ', 'templates', 'WPBDM' ) ); ?></span>
            <span class="next"><?php next_posts_link( _x( 'Next &raquo;', 'templates', 'WPBDM' ), $query->max_num_pages ); ?></span>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
