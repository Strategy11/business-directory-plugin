<?php
$plan = $listing->get_fee_plan()->fee;
$categories = wp_get_post_terms( $listing->get_id(), WPBDP_CATEGORY_TAX, array( 'fields' => 'ids' ) );
?>
<?php echo wpbdp_render( 'plan-selection-plan', array( 'plan' => $plan, 'categories' => $categories, 'display_only' => true, 'extra' ) ); ?>

<div id="change-plan-link" class="wpbdp-clearfix">
    <span class="dashicons dashicons-update"></span>
    <a href="#"><?php _ex( 'Change category/plan', 'listing submit', 'WPBDM'); ?></a>
</div>
