<?php
wpbdp_the_listing_sort_options();
$limit_posts = ! empty( $remaining_posts ) && $remaining_posts < $query->query['posts_per_page'];
$num_posts = 0;
?>

<div class="listings wpbdp-listings-list list">
    <?php if ( ! $query->have_posts() ): ?>
        <span class="no-listings">
            <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
        </span>
    <?php else: ?>
        <?php
        while ( $query->have_posts() ) :

            $query->the_post();
            echo wpbdp_render_listing( null, 'excerpt' );

            if ( $limit_posts && ++$num_posts == $remaining_posts ) :
                break;
            endif;
        endwhile;
        ?>

        <div class="wpbdp-pagination">
        <?php
        if (function_exists('wp_pagenavi')) :
            wp_pagenavi( array( 'query' => $query ) );
        else:
            if ( is_front_page() ) :
                global $paged;
                $paged = $query->query['paged'];
            endif;
        ?>
            <span class="prev"><?php previous_posts_link( _x( '&laquo; Previous ', 'templates', 'WPBDM' ) ); ?></span>
            <span class="next"><?php next_posts_link( _x( 'Next &raquo;', 'templates', 'WPBDM' ), $query->max_num_pages ); ?></span>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
