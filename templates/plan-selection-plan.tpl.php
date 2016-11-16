<?php
$field_name = isset( $field_name ) ? $field_name : '';
$display_only = isset( $display_only ) ? $display_only : false;
$selected = isset( $selected ) ? $selected : 0;

$description = $plan->description ? wpautop( wp_kses_post( $plan->description ) ) : '';
$description = apply_filters( 'wpbdp_fee_selection_fee_description', $description, $plan );
?>
    <div class="wpbdp-plan wpbdp-plan-info-box wpbdp-clearfix <?php if ( $display_only ): ?>display-only<?php endif; ?>"
         data-id="<?php echo $plan->id; ?>"
         data-free-text="<?php echo esc_attr( wpbdp_currency_format( 0.0 ) ); ?>"
         data-categories="<?php echo implode( ',', (array) $plan->supported_categories ); ?>"
         data-pricing-model="<?php echo $plan->pricing_model; ?>"
         data-amount="<?php echo $plan->amount; ?>"
         data-amount-format="<?php echo esc_attr( wpbdp_currency_format( 'placeholder' ) ); ?>"
         data-pricing-details="<?php echo esc_attr( json_encode( $plan->pricing_details ) ); ?>" >
        <div class="wpbdp-plan-duration">
            <span class="wpbdp-plan-duration-amount">
                <?php echo $plan->days ? $plan->days : '∞'; ?>
            </span>
            <span class="wpbdp-plan-duration-period"><?php _ex( 'days', 'plan selection', 'WPBDM' ); ?></span>
        </div>
        <div class="wpbdp-plan-details">
            <div class="wpbdp-plan-label"><?php echo esc_html( apply_filters( 'wpbdp_category_fee_selection_label', $plan->label ) ); ?></div>

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
                       <?php checked( absint( $plan->id ), absint( $selected ) ); ?> />
                <?php endif; ?>
                <span class="wpbdp-plan-price-amount"><?php echo wpbdp_currency_format( $plan->calculate_amount( $categories ) ); ?></span>
            </label>
        </div>
    </div>

