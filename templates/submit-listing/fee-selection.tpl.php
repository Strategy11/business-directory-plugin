<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Fee/Upgrade Selection', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-fees" class="wpbdp-listing-form" method="POST" action="">
    <input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

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
                    <?php $fee_selected = ( $i == 0 ? true : false ); ?>
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

<?php
/*
<?php if ( $upgrade_option ): ?>
<div class="upgrade-to-featured-option">
    <b><?php echo sprintf( _x('Would you like to upgrade your listing to "%s" for %s more?', 'templates', 'WPBDM'), esc_attr( $upgrade_option->name ), wpbdp_get_option( 'currency-symbol' ) . ' ' . $upgrade_option->cost ); ?></b>    
    <p class="description"><?php echo wpautop( wp_kses_post( $upgrade_option->description ) ); ?></p>
    <p>
        <label><input type="checkbox" name="upgrade-listing" value="upgrade" <?php echo wpbdp_getv( $_POST, 'upgrade-listing', '') == 'upgrade' ? 'checked="checked"' : ''; ?> /> <?php _ex( 'Yes, upgrade my listing now.', 'templates', 'WPBDM'); ?></label>
    </p>
</div>
<?php endif; ?>
 */
?>

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
