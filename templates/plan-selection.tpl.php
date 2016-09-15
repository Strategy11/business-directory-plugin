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
        <?php
            $rows_html = '';
            ob_start();
        ?>
        <?php $i = 0; foreach ( $plans as $fee ): ?>
        <tr class="fee-option fee-id-<?php echo $fee->id; ?>">
            <td class="fee-selection">
            <?php
            $fee_selected = false;

            if ( isset( $selected ) ) {
                $fee_selected = ( is_object( $selected ) ? $selected->id == $fee->id : $selected == $fee->id );
            } else {
                $fee_selected = ( $i == 0 ? true : false );
            }
            ?>
                <input type="radio" id="wpbdp-fees-radio-<?php echo $fee->id; ?>" name="listing_plan" value="<?php echo $fee->id; ?>" <?php echo $fee_selected ? 'checked="checked"' : ''; ?> data-canrecur="1" />
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
        <?php $fee_description = $fee->description ? wpautop( wp_kses_post( $fee->description ) ) : ''; ?>
        <?php $fee_description = apply_filters( 'wpbdp_fee_selection_fee_description', $fee_description, $fee ); ?>
        <?php if ( $fee_description ) : ?>
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
    </tbody>
</table>
