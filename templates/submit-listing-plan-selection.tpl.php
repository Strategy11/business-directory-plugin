<?php
/**
 * Submit Listing Plan Selection
 *
 * @package BDP/Templates/Plan Selection
 */

$single_plan = ! ( count( $plans ) > 1 );
?>
<div class="wpbdp-category-selection-with-tip">
    <?php if ( $editing ) : ?>
    <div class="wpbdp-msg tip">
        <?php
        esc_html_e(
            'You can\'t change the plan your listing is on but you can modify the categories it appears in, using the field below.',
            'business-directory-plugin'
        );
        ?>
    </div>
    <?php endif; ?>

    <?php
	if ( ! empty( $selected_categories ) ) :
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $category_field->render( (array) $selected_categories, 'submit' );
	else :
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $category_field->render();
	endif;
	?>
</div>

<?php if ( $_submit->skip_plan_selection ) : ?>
    <input type="hidden" name="listing_plan" value="<?php echo esc_attr( $_submit->fixed_plan_id ); ?>" />
    <input type="hidden" name="skip_plan_selection" value="1" />

    <div class="wpbdp-plan-selection-wrapper" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-size">
        <div class="wpbdp-plan-selection">
            <div class="wpbdp-plan-selection-list">
                <?php
                wpbdp_render(
                    'plan-selection-plan', array(
                        'plan'         => wpbdp_get_fee_plan( $selected_plan ),
                        'categories'   => $selected_categories,
                        'display_only' => true,
                        'echo'         => true,
                        'extra',
                    )
                );
                ?>
            </div>
        </div>
    </div>
<?php else : ?>
    <div class="wpbdp-plan-selection-wrapper" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-size">
        <?php if ( ! $editing ) : ?>
            <div class="wpbdp-plan-selection <?php echo ! $single_plan ? esc_attr( 'wpbdp-plan-selection-with-tip' ) : ''; ?>">
                <?php
                wpbdp_render(
                    'plan-selection',
                    array(
                        'plans'    => $plans,
                        'selected' => ( ! empty( $selected_plan ) ? $selected_plan : 0 ),
                        'echo'     => true,
                    )
                );
                ?>
            </div>
        <?php else : ?>
        <div class="wpbdp-current-plan">
            <?php
            wpbdp_render(
                'plan-selection-plan', array(
                    'plan'         => wpbdp_get_fee_plan( $selected_plan ),
                    'categories'   => array(),
                    'display_only' => true,
                    'echo'         => true,
                    'extra',
                )
            );
            ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
