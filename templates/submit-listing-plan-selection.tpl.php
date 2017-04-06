<div class="wpbdp-category-selection-with-tip">
    <?php if ( ! empty( $selected_categories ) ): ?>
        <?php echo $category_field->render( (array) $selected_categories, 'submit' ); ?>
    <?php else: ?>
        <?php if ( 'multiselect' == $category_field->get_field_type_id() ): ?>
        <div class="wpbdp-msg tip"><?php _ex( 'You need to pick the categories first and then you\'ll be shown the available fee plans for your listing.', 'submit', 'WPBDM' ); ?></div>
        <?php endif; ?>
        <?php echo $category_field->render(); ?>
    <?php endif; ?>
</div>

<div class="wpbdp-plan-selection-with-tip">
    <div class="wpbdp-msg tip"><?php _ex( 'Please choose a fee plan for your listing:', 'submit', 'WPBDM' ); ?></div>
    <?php
    echo wpbdp_render( 'plan-selection',
                       array( 'plans' => $plans,
                              'selected' => ( ! empty( $selected_plan ) ? $selected_plan : 0 ) ) );
?>
</div>
