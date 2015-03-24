<?php
$in_shortcode = ! isset( $in_shortcode ) ? false : (bool) $in_shortcode;
?>
<div id="wpbdp-category-page" class="wpbdp-category-page businessdirectory-category businessdirectory wpbdp-page">
    <?php if ( ! $in_shortcode ): ?>
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
        <?php wpbdp_the_search_form(); ?>
    </div>
    <?php endif; ?>

    <?php echo $__page__['before_content']; ?>

    <?php if ( $title ): ?>
        <h2 class="category-name">
            <?php if ( $is_tag ): ?>
                <?php echo sprintf( _x( 'Listings tagged: %s', 'templates', 'WPBDM' ), $title ); ?>
            <?php else: ?>
                <?php echo $title; ?>
            <?php endif; ?>
        </h2>
    <?php endif; ?>

    <?php do_action( 'wpbdp_before_category_page', $category ); ?>
    <?php
    	echo apply_filters( 'wpbdp_category_page_listings', wpbdp_render('businessdirectory-listings', array('excludebuttons' => true)), $category );
    ?>
    <?php do_action( 'wpbdp_after_category_page', $category ); ?>

</div>
