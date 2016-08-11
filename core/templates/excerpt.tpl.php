<?php
$__template__ = array( 'blocks' => array( 'before', 'after' ) );
?>

<div id="wpbdp-listing-<?php echo $listing->get_id(); ?>"
     class="wpbdp-listing wpbdp-excerpt excerpt wpbdp-listing-excerpt wpbdp-listing-<?php echo $listing->get_id(); ?> <?php echo $listing->get_sticky_status(); ?> <?php echo apply_filters( 'wpbdp_excerpt_view_css', '', $listing->get_id() ); ?> <?php echo $even_or_odd; ?>">

    <?php echo $blocks['before']; ?>
    <?php wpbdp_x_part( 'excerpt_content' ); ?>
    <?php echo $blocks['after']; ?>

    <?php echo wpbdp_the_listing_actions(); ?>
</div>
