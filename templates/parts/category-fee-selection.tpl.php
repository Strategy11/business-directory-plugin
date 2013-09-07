<div class="fee-options-for-category">
	<?php if ( isset( $state->categories) && ( count( $state->categories ) > 1 ) ): ?><h4><?php echo sprintf( _x( '"%s" fee options', 'templates', 'WPBDM' ), $category->name ); ?></h4><?php endif; ?>
	<table class="fee-options">
		<thead>
			<th class="fee-selection"></th>
			<th class="fee-label"><?php echo _x( 'Fee', 'templates', 'WPBDM' ); ?></th>
			<th class="fee-amount"><?php echo _x( 'Price', 'templates', 'WPBDM' ); ?></th>					
			<th class="fee-duration"><?php echo _x( 'Duration', 'templates', 'WPBDM' ); ?></th>
			<th class="fee-images"><?php echo _x( 'Images Allowed', 'templates', 'WPBDM' ); ?></th>
			<?php // do_action( 'wpbdp_fee_selection_extra_headers' ); ?>
		</thead>
		<tbody>
			<?php $i = 0; foreach ( $fees as &$fee ): ?>					
			<tr class="fee-option fee-id-<?php echo $fee->id; ?>">
				<td class="fee-selection">
					<?php $fee_selected = ( !isset( $state->fees[ $category->term_id ] ) && $i == 0 ) || ( isset( $state->fees[ $category->term_id ] ) && $state->fees[ $category->term_id ] == $fee->id ) ? true : false; ?>
					<input type="radio" id="wpbdp-fees-radio-<?php echo $fee->id; ?>" name="fees[<?php echo $category->term_id; ?>]" value="<?php echo $fee->id; ?>" <?php echo $fee_selected ? 'checked="checked"' : ''; ?> />
				</td>
				<td class="fee-label">
					<label for="wpbdp-fees-radio-<?php echo $fee->id; ?>"><?php echo esc_html( $fee->label ); ?></label>
				</td>
				<td class="fee-amount">
					<?php echo wpbdp_format_currency( $fee->amount ); ?>
				</td>
				<td class="fee-duration">
					<?php if ( $fee->days == 0 ): ?>
						<?php echo _x( 'Unlimited', 'templates', 'WPBDM' ); ?>
					<?php else : ?>
						<?php echo sprintf( _nx( '%d day', '%d days', $fee->days, 'templates', 'WPBDM' ), $fee->days ); ?>
					<?php endif; ?>
				</td>
				<td class="fee-images">
					<?php echo wpbdp_get_option('allow-images') ? $fee->images : '—'; ?>
				</td>
				<?php // do_action( 'wpbdp_fee_selection_extra_columns', $fee ); ?>
			</tr>
			<?php if ( $fee_description = apply_filters( 'wpbdp_fee_selection_fee_description', '', $fee ) ): ?>
			<tr class="fee-description fee-id-<?php echo $fee->id; ?>">
				<td></td>
				<td colspan="4"><?php echo $fee_description; ?></td>
			</tr>
			<?php endif; ?>
			<?php $i++; endforeach; ?>
		</tbody>
	</table>

</div>