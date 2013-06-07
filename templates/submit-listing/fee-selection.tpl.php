<h3>
	<?php _ex( '2 - Fee/Upgrade Selection', 'templates', 'WPBDM' ); ?>
</h3>

<form id="wpbdp-listing-form-fees" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state; ?>" />

	<?php foreach ( $categories as $cat_id ): $category = get_term( $cat_id, WPBDP_CATEGORY_TAX ); ?>
		<div class="fee-options-for-category">
			<?php if ( count( $categories ) > 1 ): ?><h4><?php echo sprintf( _x( '"%s" fee options', 'templates', 'WPBDM' ), $category->name ); ?></h4><?php endif; ?>
			
			<table class="fee-options">
				<thead>
					<th></th>
					<th><?php echo _x( 'Fee', 'templates', 'WPBDM' ); ?></th>
					<th><?php echo _x( 'Price', 'templates', 'WPBDM' ); ?></th>					
					<th><?php echo _x( 'Duration', 'templates', 'WPBDM' ); ?></th>
					<th><?php echo _x( 'Images Allowed', 'templates', 'WPBDM' ); ?></th>
					<?php do_action( 'wpbdp_fee_selection_extra_headers' ); ?>
				</thead>
				<tbody>
					<?php foreach ( $fees[ $cat_id ] as &$fee ): ?>
					<tr class="fee-option">
						<td>
							<?php $fee_selected = isset( $state->fees[ $cat_id ] ) && $state->fees[ $cat_id ] == $fee->id ? true : false; ?>
							<input type="radio" name="fees[<?php echo $cat_id; ?>]" value="<?php echo $fee->id; ?>" <?php echo $fee_selected ? 'checked="checked"' : ''; ?> />
						</td>
						<td>
							<?php echo esc_html( $fee->label ); ?>
						</td>
						<td>
							<?php echo wpbdp_format_currency( $fee->amount ); ?>
						</td>
						<td>
							<?php if ( $fee->days == 0 ): ?>
								<?php echo _x( 'Unlimited', 'templates', 'WPBDM' ); ?>
							<?php else : ?>
								<?php echo sprintf( _nx( '%d day', '%d days', $fee->days, 'templates', 'WPBDM' ), $fee->days ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php echo $fee->images; ?>
						</td>
						<?php do_action( 'wpbdp_fee_selection_extra_columns', $fee ); ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		</div>
	<?php endforeach; ?>

<?php/* TODO:
		<?php if ( wpbdp_get_option( 'featured-offer-in-submit' ) && !$listing_id && $upgrade_option ): ?>
		<div class="upgrade-to-featured-option">
			<b><?php echo sprintf( _x('Would you like to upgrade your listing to "%s" for %s more?', 'templates', 'WPBDM'), esc_attr( $upgrade_option->name ), wpbdp_get_option( 'currency-symbol' ) . ' ' . $upgrade_option->cost ); ?></b>
			<p class="description"><?php echo esc_html( $upgrade_option->description ); ?></p>
			<p>
				<label><input type="checkbox" name="upgrade-listing" value="upgrade" /> <?php _ex( 'Yes, upgrade my listing now.', 'templates', 'WPBDM'); ?></label>
			</p>
		</div>
		<?php endif; ?> */ ?>

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />
</form>