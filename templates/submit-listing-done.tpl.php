<h3><?php _ex( 'Submission Received', 'templates', 'WPBDM' ); ?></h3>

<?php if ( ! $editing ): ?>
    <p><?php _ex( 'Your listing has been submitted.', 'templates', 'WPBDM' ); ?></p>
    <?php if ( $payment && $payment->amount > 0.0 ): ?>
    <div id="wpbdp-checkout-confirmation-receipt">
        <?php echo wpbdp()->payments->render_receipt( $payment ); ?>
    </div>
    <?php endif; ?>
<?php else: ?>
    <p><?php _ex('Your listing changes were saved.', 'templates', 'WPBDM'); ?></p>
<?php endif; ?>

    <p>
        <?php if ( 'publish' == get_post_status( $listing->get_id() ) ): ?>
            <a href="<?php echo get_permalink( $listing->get_id() ); ?>"><?php _ex( 'Go to your listing', 'templates', 'WPBDM' ); ?></a> | 
        <?php else: ?>
            <?php _ex( 'Your listing requires admin approval. You\'ll be notified once your listing is approved.', 'templates', 'WPBDM' ); ?>
    </p>
    <p>
        <?php endif; ?>
        <a href="<?php echo wpbdp_get_page_link( 'main' ); ?>"><?php _ex( 'Return to directory.', 'templates', 'WPBDM' ); ?></a>
    </p>
