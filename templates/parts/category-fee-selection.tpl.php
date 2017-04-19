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
<div class="fee-options-for-category" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-fee-options-for-category">
	<?php if ( $multiple_categories ): ?>
        <h4><?php echo sprintf( _x( '"%s" fee options', 'templates', 'WPBDM' ), $category->name ); ?></h4>
    <?php endif; ?>

    <?php if ( ! $category_fees ): ?>
    <div><?php _ex( 'There are no fees available for this category.', 'templates', 'WPBDM'); ?></div>
    <?php else: ?>
    <?php
        $rows_html = '';
        ob_start();
    ?>
    <?php $i = 0; foreach ( $category_fees as &$fee ): ?>
    <div class="wpbdp-plan wpbdp-plan-info-box wpbdp-cf">
        <div class="wpbdp-plan-duration">
            <span class="wpbdp-plan-duration-amount">
                <?php echo $fee->days ? $fee->days : '∞'; ?>
            </span>
            <span class="wpbdp-plan-duration-period"><?php echo _nx( 'day', 'days', $fee->days, 'templates', 'WPBDM' ); ?></span>
        </div>
        <div class="wpbdp-plan-details">
            <div class="wpbdp-plan-label">
                <?php echo esc_html( apply_filters( 'wpbdp_category_fee_selection_label', $fee->label, $fee ) ); ?>
            </div>

            <?php
                $description = $fee->description ? wpautop( wp_kses_post( $fee->description ) ) : '';
                $description = apply_filters( 'wpbdp_fee_selection_fee_description', $description, $fee );
            ?>

            <?php if ( $description ): ?>
            <div class="wpbdp-plan-description"><?php echo $description; ?></div>
            <?php endif; ?>

            <?php if ( wpbdp_get_option( 'allow-images' ) ): ?>
            <ul class="wpbdp-plan-feature-list">
                <li><?php echo sprintf( _nx( '%d image', '%d images', $fee->images, 'templates', 'WPBDM' ), $fee->images ); ?></li>
            </ul>
            <?php endif; ?>
        </div>
        <div class="wpbdp-plan-price">
            <?php $selected = ( ( $current_fee === null && $i == 0 ) || ( $current_fee == $fee->id ) ); ?>
            <label>
                <input type="radio"
                       id="wpbdp-plan-select-radio-<?php echo $fee->id; ?>"
                       name="fees[<?php echo $category->term_id; ?>]"
                       value="<?php echo $fee->id; ?>"
                       <?php checked( absint( $fee->id ), absint( $selected ) ); ?> />
                <span class="wpbdp-plan-price-amount"><?php echo wpbdp_currency_format( $fee->amount ); ?></span>
            </label>
        </div>
    </div>
    <?php $i++; endforeach; ?>
    <?php
        $rows_html = ob_get_clean();

        if ( isset( $fee_rows_filter ) && is_callable( $fee_rows_filter )  ) {
            $rows_html = call_user_func( $fee_rows_filter, $rows_html, $category );
        }

        echo $rows_html;
    ?>
    <?php endif; ?>
</div>
