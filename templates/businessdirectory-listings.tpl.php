<?php
/**
 * Template parameters:
 *  $query - The WP_Query object for this page. Do not call query_posts() in this template.
 */
$query = isset( $query ) ? $query : wpbdp_current_query();
?>
<div id="wpbdp-view-listings-page" class="wpbdp-view-listings-page wpbdp-page <?php echo esc_attr( join(' ', $__page__['class'] ) ); ?>">

    <?php if (!isset($stickies)) $stickies = null; ?>
    <?php if (!isset($excludebuttons)) $excludebuttons = true; ?>

    <?php if (!$excludebuttons): ?>
        <div class="wpbdp-bar cf">
            <?php wpbdp_the_main_links(); ?>
            <?php wpbdp_the_search_form(); ?>
        </div>
    <?php endif; ?>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $__page__['before_content'];
    ?>

    <div class="wpbdp-page-content <?php echo esc_attr( join(' ', $__page__['content_class'] ) ); ?>">

        <?php wpbdp_the_listing_sort_options(); ?>

        <?php if ( ! $query->have_posts()): ?>
            <?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?>
        <?php else: ?>
            <div class="listings wpbdp-listings-list">
                <?php while ( $query->have_posts() ): $query->the_post(); ?>
                    <?php wpbdp_render_listing( null, 'excerpt', 'echo' ); ?>
                <?php endwhile; ?>

                <div class="wpbdp-pagination">
                <?php if (function_exists('wp_pagenavi')) : ?>
                        <?php wp_pagenavi( array( 'query' => $query ) ); ?>
                <?php else: ?>
                    <span class="prev"><?php previous_posts_link( __( '&larr; Previous ', 'business-directory-plugin' ) ); ?></span>
                    <span class="next"><?php next_posts_link( __( 'Next &rarr;', 'business-directory-plugin' ), $query->max_num_pages ); ?></span>
                <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>
