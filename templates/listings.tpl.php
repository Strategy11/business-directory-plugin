<?php
/**
 * Listings display template
 *
 * @package BDP/Templates/Listings
 */

wpbdp_the_listing_sort_options();
?>

<div id="wpbdp-listings-list" class="listings wpbdp-listings-list list wpbdp-grid <?php echo esc_attr( apply_filters( 'wpbdp_listings_class', '' ) ); ?>">
    <?php if ( ! $query->have_posts() ): ?>
        <span class="no-listings">
            <?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?>
        </span>
    <?php else: ?>
        <?php
        while ( $query->have_posts() ) :
            $query->the_post();
            wpbdp_render_listing( null, 'excerpt', 'echo' );
        endwhile;
        ?>

        <div class="wpbdp-pagination">
        <?php
        if ( function_exists('wp_pagenavi' ) ) :
            wp_pagenavi( array( 'query' => $query ) );
        else:
        ?>
            <span class="prev"><?php previous_posts_link( __( '&larr; Previous ', 'business-directory-plugin' ) ); ?></span>
            <span class="next"><?php next_posts_link( __( 'Next &rarr;', 'business-directory-plugin' ), $query->max_num_pages ); ?></span>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
