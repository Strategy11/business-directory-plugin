<?php do_action( 'wpbdp_before_submit_listing_page', $listing ); ?>
<div id="wpbdp-submit-listing" class="wpbdp-submit-page wpbdp-page">
    <form action="" method="post" data-ajax-url="<?php echo esc_url( wpbdp_ajax_url() ); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field( 'listing submit' ); ?>
        <input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing->get_id() ); ?>" />
        <input type="hidden" name="editing" value="<?php echo $editing ? '1' : '0'; ?>" />
        <input type="hidden" name="save_listing" value="1" />
        <input type="hidden" name="reset" value="" />

            <h3><?php esc_html_e( 'Add Listing', 'business-directory-plugin' ); ?></h3>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $messages['general'];
            ?>

            <?php foreach ( $sections as $section ): ?>
                <?php
                wpbdp_render(
                    'submit-listing-section',
                    array(
                        'echo'    => true,
                        'section' => $section,
                        'messages' => ( ! empty( $messages[ $section['id'] ] ) ? $messages[ $section['id'] ] : '' )
                    )
                );
                ?>
            <?php endforeach; ?>

        <div class="wpbdp-submit-listing-form-actions">
            <input type="reset" value="<?php esc_attr_e( 'Clear Form', 'business-directory-plugin' ); ?>" />
            <?php if ( $is_admin || ! wpbdp_payments_possible() || $submit->skip_plan_payment ): ?>
            <input type="submit" value="<?php esc_attr_e( 'Complete Listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
            <?php else: ?>
                <?php if ( $editing ): ?>
                <input type="submit" value="<?php esc_attr_e( 'Save Changes', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                <?php else: ?>
                <input type="submit" value="<?php esc_attr_e( 'Continue to Payment', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php do_action( 'wpbdp_after_submit_listing_page', $listing ); ?>
