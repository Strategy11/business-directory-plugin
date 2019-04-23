<?php
/**
 * Submit Listing Done Template
 *
 * @package WPBDP/Templates
 */

?>
<h3><?php echo esc_html_x( 'Submission Received', 'templates', 'WPBDM' ); ?></h3>

<?php if ( ! $editing ) : ?>
    <p><?php echo esc_html_x( 'Your listing has been submitted.', 'templates', 'WPBDM' ); ?></p>
    <?php if ( $payment && $payment->amount > 0.0 ) : ?>
        <p>
        <?php echo esc_html( wpbdp_get_option( 'payment-message' ) ); ?>
        </p>
        <div id="wpbdp-checkout-confirmation-receipt">
            <?php echo wpbdp()->payments->render_receipt( $payment ); ?>
        </div>
    <?php endif; ?>
<?php else : ?>
    <p><?php echo esc_html_x( 'Your listing changes were saved.', 'templates', 'WPBDM' ); ?></p>
<?php endif; ?>

    <p>
        <?php if ( 'publish' === get_post_status( $listing->get_id() ) ) : ?>
            <a href="<?php echo esc_attr( get_permalink( $listing->get_id() ) ); ?>"><?php echo esc_html_x( 'Go to your listing', 'templates', 'WPBDM' ); ?></a> | 
        <?php else : ?>
            <?php echo esc_html_x( 'Your listing requires admin approval. You\'ll be notified once your listing is approved.', 'templates', 'WPBDM' ); ?>
    </p>
    <p>
        <?php endif; ?>
        <a href="<?php echo esc_attr( wpbdp_get_page_link( 'main' ) ); ?>"><?php echo esc_html_x( 'Return to directory.', 'templates', 'WPBDM' ); ?></a>
    </p>
