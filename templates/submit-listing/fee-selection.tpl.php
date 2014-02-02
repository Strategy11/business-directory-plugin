<h3><?php _ex( '2 - Fee/Upgrade Selection', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-fees" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

	<?php
    foreach ( $_state->categories as $cat_id ):
        $category = get_term( $cat_id, WPBDP_CATEGORY_TAX );
    ?>
		<?php echo wpbdp_render( 'parts/category-fee-selection',
								 array( 'category' => $category,
                                        'multiple_categories' => count( $_state->categories ) > 1,
                                        'current_fee' => isset( $_state->fees[ $cat_id ] ) ? $_state->fees[ $cat_id ] : null,
								 		'category_fees' => $fees[ $cat_id ] ) ); ?>
	<?php endforeach; ?>

<?php if ( $upgrade_option ): ?>
<div class="upgrade-to-featured-option">
	<b><?php echo sprintf( _x('Would you like to upgrade your listing to "%s" for %s more?', 'templates', 'WPBDM'), esc_attr( $upgrade_option->name ), wpbdp_get_option( 'currency-symbol' ) . ' ' . $upgrade_option->cost ); ?></b>	
	<p class="description"><?php echo esc_html( $upgrade_option->description ); ?></p>
	<p>
		<label><input type="checkbox" name="upgrade-listing" value="upgrade" <?php echo wpbdp_getv( $_POST, 'upgrade-listing', '') == 'upgrade' ? 'checked="checked"' : ''; ?> /> <?php _ex( 'Yes, upgrade my listing now.', 'templates', 'WPBDM'); ?></label>
	</p>
</div>
<?php endif; ?>

<?php if ( $allow_recurring ): ?>
<div class="make-charges-recurring-option">
    <b><?php echo _x( 'Would you like to make your fee renew automatically at the end of the period?', 'submit', 'WPBDM' ); ?></b>
    <input type="checkbox" name="autorenew_fees" value="autorenew" <?php echo wpbdp_getv( $_POST, 'autorenew_fees' ) == 'autorenew' ? 'checked="checked"' : ''; ?> />
</div>
<?php endif; ?>

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />
</form>