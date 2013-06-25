<h3>
	<?php _ex( '2 - Fee/Upgrade Selection', 'templates', 'WPBDM' ); ?>
</h3>

<form id="wpbdp-listing-form-fees" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state; ?>" />

	<?php foreach ( $categories as $cat_id ): $category = get_term( $cat_id, WPBDP_CATEGORY_TAX ); ?>
		<?php echo wpbdp_render( 'parts/category-fee-selection',
								 array( 'category' => $category,
								 		'fees' => $fees[ $cat_id ],
								 		'state' => $state ) ); ?>
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

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />
</form>