<?php do_action( 'wpbdp_before_submit_listing_page', $listing ); ?>

<div id="wpbdp-submit-listing" class="wpbdp-submit-page wpbdp-page" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-submit-page">
    <form action="" method="post" data-ajax-url="<?php echo wpbdp_ajax_url(); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field( 'listing submit' ); ?>
        <input type="hidden" name="listing_id" value="<?php echo $listing->get_id(); ?>" />
        <input type="hidden" name="editing" value="<?php echo $editing ? '1' : '0'; ?>" />
        <input type="hidden" name="save_listing" value="1" />
        <input type="hidden" name="reset" value="" />

            <h3><?php echo esc_html_x( 'Add Listing', 'view', 'business-directory-plugin' ); ?></h3>
            <?php echo $messages['general']; ?>

            <?php foreach ( $sections as $section ): ?>
                <?php echo wpbdp_render( 'submit-listing-section',
                                         array( 'section' => $section,
                                                'messages' => ( ! empty( $messages[ $section['id'] ] ) ? $messages[ $section['id'] ] : '' ) ) );
                ?>
            <?php endforeach; ?>

        <div class="wpbdp-submit-listing-form-actions">
            <a class="reset" href="#"><?php echo esc_html_x( 'Clear Form', 'submit listing', 'business-directory-plugin' ); ?></a>
            <?php if ( $is_admin || ! wpbdp_payments_possible() || $submit->skip_plan_payment ): ?>
            <input type="submit" value="<?php _ex( 'Complete Listing', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
            <?php else: ?>
                <?php if ( $editing ): ?>
                <input type="submit" value="<?php _ex( 'Save Changes', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                <?php else: ?>
                <input type="submit" value="<?php _ex( 'Continue to Payment', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php do_action( 'wpbdp_after_submit_listing_page', $listing ); ?>
