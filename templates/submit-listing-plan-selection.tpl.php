<?php if ( ! empty( $selected_categories ) ): ?>
    <?php echo $category_field->render( (array) $selected_categories, 'submit' ); ?>
<?php else: ?>
    <?php echo $category_field->render(); ?>
<?php endif; ?>

<?php
echo wpbdp_render( 'plan-selection',
                   array( 'plans' => $plans,
                          'selected' => ( ! empty( $selected_plan ) ? $selected_plan : 0 ) ) );
?>
