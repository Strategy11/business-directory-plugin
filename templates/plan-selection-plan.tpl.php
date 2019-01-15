<?php
/**
 * Plan Selection Template displayed when submitting a listing
 *
 * @package BDP/Templates
 */

// phpcs:disable

$field_name   = isset( $field_name ) ? $field_name : '';
$display_only = isset( $display_only ) ? $display_only : false;
$disabled     = isset( $disabled ) ? $disabled : false;
$selected     = isset( $selected ) ? $selected : 0;

$description = $plan->description ? wpautop( wp_kses_post( apply_filters( 'wpbdp_plan_description_for_display', $plan->description, $plan ) ) ) : '';
$description = apply_filters( 'wpbdp_fee_selection_fee_description', $description, $plan );
?>
    <div class="wpbdp-plan wpbdp-plan-<?php echo $plan->id; ?> wpbdp-plan-info-box wpbdp-clearfix <?php if ( $display_only ): ?>display-only<?php endif; ?> <?php if ( $disabled ): ?>wpbdp-plan-disabled<?php endif; ?>"
         data-id="<?php echo $plan->id; ?>"
         data-disabled="<?php echo $disabled ? 1 : 0; ?>"
         data-recurring="<?php echo $plan->recurring ? 1 : 0; ?>"
         data-free-text="<?php echo esc_attr( wpbdp_currency_format( 0.0 ) ); ?>"
         data-categories="<?php echo implode( ',', (array) $plan->supported_categories ); ?>"
         data-pricing-model="<?php echo $plan->pricing_model; ?>"
         data-amount="<?php echo $plan->amount; ?>"
         data-amount-format="<?php echo esc_attr( wpbdp_currency_format( 'placeholder' ) ); ?>"
         data-pricing-details="<?php echo esc_attr( json_encode( $plan->pricing_details ) ); ?>" >
        <div class="wpbdp-plan-duration">
            <?php if ( $plan->days > 0 ): ?>
            <span class="wpbdp-plan-duration-amount">
                <?php echo $plan->days; ?>
            </span>
            <span class="wpbdp-plan-duration-period"><?php _ex( 'days', 'plan selection', 'WPBDM' ); ?></span>
                <?php if ( $plan->recurring ): ?>
                <span class="wpbdp-plan-is-recurring"><?php _ex( '(Recurring)', 'plan selection', 'WPBDM' ); ?></span>
                <?php endif; ?>
            <?php else: ?>
            <span class="wpbdp-plan-duration-never-expires">
                <?php _ex( 'Never Expires', 'plan selection', 'WPBDM' ); ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="wpbdp-plan-details">
        <div class="wpbdp-plan-label"><?php echo esc_html( apply_filters( 'wpbdp_category_fee_selection_label', $plan->label, $plan ) ); ?></div>

            <?php if ( $description ): ?>
            <div class="wpbdp-plan-description"><?php echo $description; ?></div>
            <?php endif; ?>

            <ul class="wpbdp-plan-feature-list">
                <?php foreach ( $plan->get_feature_list() as $feature ): ?>
                <li><?php echo $feature; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="wpbdp-plan-price">
            <label>
                <?php if ( ! $display_only ): ?>
                <input type="radio"
                       id="wpbdp-plan-select-radio-<?php echo $plan->id; ?>"
                       name="<?php echo $field_name; ?>"
                       value="<?php echo $plan->id; ?>"
                        <?php disabled( $disabled, true ); ?>
                       <?php echo $disabled ? '': checked( absint( $plan->id ), absint( $selected ), false ); ?> />
                <?php endif; ?>
                <span class="wpbdp-plan-price-amount"><?php echo wpbdp_currency_format( $plan->calculate_amount( $categories ) ); ?></span>
            </label>
        </div>

        <?php if ( $disabled ): ?>
        <div class="wpbdp-msg wpbdp-plan-disabled-msg">
            <?php _ex( 'This plan can\'t be used for admin submits. For a recurring plan to work, end users need to pay for it using a supported gateway.', 'plan selection', 'WPBDM' ); ?>
        </div>
        <?php endif; ?>
        <?php if ( ! empty( $plan->extra_data['private'] ) ) : ?>
            <div class="wpbdp-plan-private-msg">
                (<?php _ex( 'Private fee plan', 'plan selection', 'WPBDM' ); ?>)
            </div>
        <?php endif; ?>

    </div>

