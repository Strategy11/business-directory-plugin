<div id="wpbdp-listing-<?php echo $listing->get_id(); ?>"
     class="wpbdp-listing wpbdp-excerpt excerpt wpbdp-listing-excerpt wpbdp-listing-<?php echo $listing->get_id(); ?> single <?php echo $listing->get_sticky_status(); ?> <?php echo apply_filters( 'wpbdp_excerpt_view_css', '', $listing->get_id() ); ?>">

     <!-- add odd/even indication -->
    <?php echo wpbdp_capture_action( 'wpbdp_before_excerpt_view', $listing_id ); ?>
    <?php echo $content; ?>
    <?php echo wpbdp_capture_action( 'wpbdp_after_excerpt_view', $listing_id ); ?>

    <!-- buttons here -->
    <?php echo wpbdp_render('parts/listing-buttons', array( 'listing_id' => $listing_id, 'view' => 'excerpt' ), false ); ?>

</div>

