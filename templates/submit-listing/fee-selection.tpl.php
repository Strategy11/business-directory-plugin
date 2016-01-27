<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Fee/Upgrade Selection', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-fees" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

	<?php
    foreach ( $fee_selection as &$f ):
    ?>
		<?php echo wpbdp_render( 'parts/category-fee-selection',
								 array( 'category' => $f['term'],
                                        'multiple_categories' => count( $fee_selection ) > 1,
                                        'current_fee' => $f['fee_id'],
								 		'category_fees' => $f[ 'options' ] ) ); ?>
	<?php endforeach; ?>

<?php if ( $upgrade_option ): ?>
<div class="upgrade-to-featured-option">
	<b><?php echo sprintf( _x('Would you like to upgrade your listing to "%s" for %s more?', 'templates', 'WPBDM'), esc_attr( $upgrade_option->name ), wpbdp_get_option( 'currency-symbol' ) . ' ' . $upgrade_option->cost ); ?></b>	
	<p class="description"><?php echo wpautop( wp_kses_post( $upgrade_option->description ) ); ?></p>
	<p>
		<label><input type="checkbox" name="upgrade-listing" value="upgrade" <?php echo wpbdp_getv( $_POST, 'upgrade-listing', '') == 'upgrade' ? 'checked="checked"' : ''; ?> /> <?php _ex( 'Yes, upgrade my listing now.', 'templates', 'WPBDM'); ?></label>
	</p>
</div>
<?php endif; ?>

<?php if ( $allow_recurring ): ?>
<?php if ( wpbdp_get_option( 'listing-renewal-auto-dontask' ) ): ?>
<input type="hidden" name="autorenew_fees" value="autorenew" />
<?php else: ?>
<div class="make-charges-recurring-option">
    <b><?php echo _x( 'Would you like to make your fee renew automatically at the end of the period?', 'submit', 'WPBDM' ); ?></b>
    <input type="checkbox" name="autorenew_fees" value="autorenew" <?php echo wpbdp_getv( $_POST, 'autorenew_fees' ) == 'autorenew' ? 'checked="checked"' : ''; ?> />
</div>
<?php endif; ?>
<?php endif; ?>

	<input type="submit" class="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />
</form>
