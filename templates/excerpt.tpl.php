<?php
/**
 * Template listing excerpt view.
 *
 * @package BDP/Templates/Excerpt
 */

// phpcs:disable
$__template__ = array( 'blocks' => array( 'before', 'after' ) );
?>
<div id="<?php echo $listing_css_id; ?>" class="<?php echo $listing_css_class; ?>" data-breakpoints='{"medium": [560,780], "large": [780,999999]}' data-breakpoints-class-prefix="wpbdp-listing-excerpt">
    <?php echo $blocks['before']; ?>
    <?php if ( in_array( 'excerpt', wpbdp_get_option( 'display-sticky-badge' ) ) ) : ?>
        <?php echo $sticky_tag; ?>
    <?php endif; ?>
    <?php wpbdp_x_part( 'excerpt_content' ); ?>
    <?php echo $blocks['after']; ?>

    <?php echo wpbdp_the_listing_actions(); ?>
</div>
