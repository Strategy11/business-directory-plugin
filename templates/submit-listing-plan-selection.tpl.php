<?php
/**
 * Submit Listing Plan Selection
 *
 * @package BDP/Templates/Plan Selection
 */

// phpcs:disable
?>
<div class="wpbdp-category-selection-with-tip">
    <?php if ( $editing ) : ?>
    <div class="wpbdp-msg tip">
    <?php
    _ex(
        'You can\'t change the plan your listing is on but you can modify the categories it appears in, using the field below. Details about the plan are shown for completeness.',
        'submit',
        'WPBDM'
    );
    ?>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $selected_categories ) ) : ?>
        <?php echo $category_field->render( (array) $selected_categories, 'submit' ); ?>
    <?php else : ?>
        <?php if ( ! $editing ) : ?>
        <div class="wpbdp-msg tip"><?php _ex( 'You need to pick the categories first and then you\'ll be shown the available fee plans for your listing.', 'submit', 'WPBDM' ); ?></div>
        <?php endif; ?>
        <?php echo $category_field->render(); ?>
    <?php endif; ?>
</div>

<?php if ( $_submit->skip_plan_selection ) : ?>
    <input type="hidden" name="listing_plan" value="<?php echo $_submit->fixed_plan_id; ?>" />
    <input type="hidden" name="skip_plan_selection" value="1" />

    <div class="wpbdp-msg tip"><?php _ex( 'Your plan\'s details:', 'submit', 'WPBDM' ); ?></div>
    <div class="wpbdp-plan-selection-wrapper" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-size">
        <div class="wpbdp-plan-selection">
            <div class="wpbdp-plan-selection-list">
                <?php
                echo wpbdp_render(
                    'plan-selection-plan', array(
						'plan'         => wpbdp_get_fee_plan( $selected_plan ),
						'categories'   => $selected_categories,
						'display_only' => true,
						'extra',
                    )
                );
?>
            </div>
        </div>
        <?php if ( $_submit->category_specific_fields ) : ?>
        <div class="wpbdp_continue_to_fields_action">
            <input type="submit" name="continue-to-fields" value="<?php _ex( 'Continue', 'submit listing', 'WPBDM' ); ?>" id="wpbdp-submit-listing-submit-btn" />
        </div>
        <?php endif; ?>
    </div>
<?php else : ?>
    <div class="wpbdp-plan-selection-wrapper" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-size">
        <?php if ( ! $editing ) : ?>
            <div class="wpbdp-plan-selection wpbdp-plan-selection-with-tip">
                <div class="wpbdp-msg tip"><?php _ex( 'Please choose a fee plan for your listing:', 'submit', 'WPBDM' ); ?></div>
                <?php
                echo wpbdp_render(
                    'plan-selection',
                    array(
						'plans'    => $plans,
						'selected' => ( ! empty( $selected_plan ) ? $selected_plan : 0 ),
                    )
                );
                ?>
            </div>
        <?php else : ?>
        <div class="wpbdp-current-plan">
            <?php
            echo wpbdp_render(
                'plan-selection-plan', array(
					'plan'         => wpbdp_get_fee_plan( $selected_plan ),
					'categories'   => array(),
					'display_only' => true,
					'extra',
                )
            );
?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
