<?php
$field_name = ! isset( $field_name ) ? 'listing_plan' : $field_name;
$categories = ! isset( $categories ) ? array() : $categories;

// if ( ! isset( $selected ) ) {
//     $plans_ids = wp_list_pluck( $plans, 'id' );
//     $selected = reset( $plans_ids );
// }
// $selected = 0;
?>
<div class="wpbdp-plan-selection-list">
    <?php foreach ( $plans as $plan ): ?>
        <?php echo wpbdp_render( 'plan-selection-plan', compact( 'plan', 'field_name', 'categories', 'selected' ) ); ?>
    <?php endforeach; ?>
</div>
