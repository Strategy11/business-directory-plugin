<?php
$__template__ = array( 'blocks' => array( 'before', 'after' ) );
?>
<div id="<?php echo $listing_css_id; ?>" class="<?php echo $listing_css_class; ?>">
    <?php echo $blocks['before']; ?>
    <?php wpbdp_x_part( 'excerpt_content' ); ?>
    <?php echo $blocks['after']; ?>

    <?php echo wpbdp_the_listing_actions(); ?>
</div>
