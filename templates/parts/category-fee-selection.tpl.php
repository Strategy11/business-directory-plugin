<?php
/*
 * Template vars:
 *  $multiple_categories boolean TRUE if multiple categories are being selected during this session.
 *  $category object Category for which the fee is being selected.
 *  $category_fees array Fees available for the category.
 *  $current_fee int NULL if no fee is currently associated to this category, the fee ID otherwise.
 *  $fee_rows_filter callback Allows to modify the HTML for the fee rows before display.
 */
?>
<div class="fee-options-for-category">
	<?php if ( $multiple_categories ): ?>
        <h4><?php echo sprintf( _x( '"%s" fee options', 'templates', 'WPBDM' ), $category->name ); ?></h4>
    <?php endif; ?>
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
            <?php if ( ! $category_fees ): ?>
            <tr class="fee-option fee-id-none">
                <td colspan="5">
                    <?php _ex( 'There are no fees available for this category.', 'templates', 'WPBDM'); ?>
                </td>
            </tr>
            <?php else: ?>
		    <?php
		        $rows_html = '';
		        ob_start();
		    ?>
			<?php $i = 0; foreach ( $category_fees as &$fee ): ?>					
			<tr class="fee-option fee-id-<?php echo $fee->id; ?>">
				<td class="fee-selection">
					<?php $fee_selected = ( ( $current_fee === null && $i == 0 ) || ( $current_fee == $fee->id ) ); ?>
					<input type="radio" id="wpbdp-fees-radio-<?php echo $fee->id; ?>" name="fees[<?php echo $category->term_id; ?>]" value="<?php echo $fee->id; ?>" <?php echo $fee_selected ? 'checked="checked"' : ''; ?> data-canrecur="<?php echo ( $fee->days > 0 && $fee->amount > 0.0 ) ? 1 : 0  ?>" />
				</td>
				<td class="fee-label">
					<label for="wpbdp-fees-radio-<?php echo $fee->id; ?>"><?php echo esc_html( apply_filters( 'wpbdp_category_fee_selection_label', $fee->label, $fee ) ); ?></label>
				</td>
				<td class="fee-amount">
					<?php echo wpbdp_currency_format( $fee->amount ); ?>
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
			<?php
    			$rows_html = ob_get_clean();

    			if ( isset( $fee_rows_filter ) && is_callable( $fee_rows_filter )  ) {
    			    $rows_html = call_user_func( $fee_rows_filter, $rows_html, $category );
    			}

                echo $rows_html;
            ?>
            <?php endif; ?>
		</tbody>
	</table>

</div>
