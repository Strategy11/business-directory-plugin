<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Category Selection', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-categories" class="wpbdp-listing-form" method="POST" action="">
    <input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

    <?php echo $category_field->render( array_keys( $_state->categories ) ); ?>
    <?php echo wpbdp_render( 'plan-selection', array( 'plans' => $plans  ) ); ?>

    <?php if ( $allow_recurring && ! wpbdp_get_option( 'listing-renewal-auto-dontask' ) ): ?>
    <div class="make-charges-recurring-option">
        <b><?php echo _x( 'Would you like to make your fee renew automatically at the end of the period?', 'submit', 'WPBDM' ); ?></b>
        <input type="checkbox" name="autorenew_fees" value="autorenew" <?php echo wpbdp_getv( $_POST, 'autorenew_fees' ) == 'autorenew' ? 'checked="checked"' : ''; ?> />
    </div>
    <?php endif; ?>

    <input type="submit" class="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />
</form>
