<div id="wpbdp-submit-listing" class="wpbdp-submit-page wpbdp-page">
    <form action="" method="post" data-ajax-url="<?php echo admin_url( 'admin-ajax.php' ); ?>">
        <?php wp_nonce_field( 'listing submit' ); ?>
        <input type="hidden" name="listing_id" value="<?php echo $listing->get_id(); ?>" />

            <h2><?php _ex( 'Submit A Listing', 'submit listing', 'WPBDM' ); ?></h2>
            <?php echo $messages['general']; ?>

            <?php foreach ( $sections as $section ): ?>
                <?php echo wpbdp_render( 'submit-listing-section',
                                         array( 'section' => $section,
                                                'messages' => ( ! empty( $messages[ $section['id'] ] ) ? $messages[ $section['id'] ] : '' ) ) );
                ?>
            <?php endforeach; ?>

        <div class="wpbdp-submit-listing-form-actions">
            <input type="reset" value="<?php _ex( 'Cancel', 'submit listing', 'WPBDM' ); ?>" />
            <input type="submit" value="<?php _ex( 'Continue to payment', 'submit listing', 'WPBDM' ); ?>" />
        </div>
    </form>
</div>
