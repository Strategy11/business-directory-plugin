<div id="wpbdp-submit-listing" class="wpbdp-submit-page wpbdp-page">
    <form action="" method="post">
        <input type="hidden" name="listing_id" value="<?php echo $listing->get_id(); ?>" />

            <h2><?php _ex( 'Submit A Listing', 'submit listing', 'WPBDM' ); ?></h2>
            <?php echo $messages['general']; ?>

            <?php foreach ( $sections as $section ): ?>
            <div class="wpbdp-submit-listing-section wpbdp-submit-listing-section-<?php echo $section['id']; ?> <?php echo implode( ' ', $section['flags'] ); ?>" data-section-id="<?php echo $section['id']; ?>">
                <div class="wpbdp-submit-listing-section-header">
                    <span class="collapse-indicator collapsed">►</span><span class="collapse-indicator expanded">▼</span><span class="title"><?php echo $section['title']; ?></span>
                </div>
                <div class="wpbdp-submit-listing-section-content">
                    <?php if ( ! empty( $messages[ $section['id'] ] ) ): ?>
                        <div class="wpbdp-submit-listing-section-messages"><?php echo $messages[ $section['id'] ]; ?></div>
                    <?php endif; ?>

                    <?php echo $section['html']; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <div class="wpbdp-submit-listing-form-actions">
            <input type="reset" value="<?php _ex( 'Cancel', 'submit listing', 'WPBDM' ); ?>" />
            <input type="submit" value="<?php _ex( 'Continue to payment', 'submit listing', 'WPBDM' ); ?>" />
        </div>
    </form>
</div>
