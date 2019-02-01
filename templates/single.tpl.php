<?php
/**
 * Template listing single view.
 *
 * @package BDP/Templates/Single
 */

// phpcs:disable
?>
<div id="<?php echo $listing_css_id; ?>" class="<?php echo $listing_css_class; ?>">
    <?php wpbdp_get_return_link(); ?>
    <div class="listing-title">
        <h2><?php echo $title; ?></h2>
    </div>
    <?php if ( in_array( 'single', wpbdp_get_option( 'display-sticky-badge' ) ) ) : ?>
        <?php echo $sticky_tag; ?>
    <?php endif; ?>

    <?php
    echo wpbdp_render(
        'parts/listing-buttons', array(
			'listing_id' => $listing_id,
			'view'       => 'single',
        ), false
    );
?>
    <?php wpbdp_x_part( 'single_content' ); ?>

</div>
