<?php echo $category_field->render(); ?>
<?php echo wpbdp_render( 'plan-selection', array( 'plans' => $plans  ) ); ?>

<?php if ( $allow_recurring && ! wpbdp_get_option( 'listing-renewal-auto-dontask' ) ): ?>
<div class="make-charges-recurring-option">
    <b><?php echo _x( 'Would you like to make your fee renew automatically at the end of the period?', 'submit', 'WPBDM' ); ?></b>
    <input type="checkbox" name="autorenew_fees" value="autorenew" <?php echo wpbdp_getv( $_POST, 'autorenew_fees' ) == 'autorenew' ? 'checked="checked"' : ''; ?> />
</div>
<?php endif; ?>
