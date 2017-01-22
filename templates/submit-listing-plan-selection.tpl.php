<?php if ( ! empty( $selected_categories ) ): ?>
    <?php echo $category_field->render( (array) $selected_categories, 'submit' ); ?>
<?php else: ?>
    <?php if ( 'multiselect' == $category_field->get_field_type_id() ): ?>
    <div class="wpbdp-msg tip">Click the field below to add categories.</div>
    <?php endif; ?>
    <?php echo $category_field->render(); ?>
<?php endif; ?>

<?php
echo wpbdp_render( 'plan-selection',
                   array( 'plans' => $plans,
                          'selected' => ( ! empty( $selected_plan ) ? $selected_plan : 0 ) ) );
?>
