<?php
$field_name = ! isset( $field_name ) ? 'listing_plan' : $field_name;
$categories = ! isset( $categories ) ? array() : $categories;

// if ( ! isset( $selected ) ) {
//     $plans_ids = wp_list_pluck( $plans, 'id' );
//     $selected = reset( $plans_ids );
// }
// $selected = 0;
?>
<div class="wpbdp-plan-selection-list" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-fee-options-for-category">
    <?php foreach ( $plans as $plan ): ?>
        <?php
        $args = compact( 'plan', 'field_name', 'categories', 'selected' );

        if ( $plan->recurring && current_user_can( 'administrator' ) ):
            $args['disabled'] = true;
        endif;
        ?>
        <?php echo wpbdp_render( 'plan-selection-plan', $args ); ?>
    <?php endforeach; ?>
</div>
