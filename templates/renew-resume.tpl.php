<?php
/**
 * Template Renew listing resume.
 *
 * @package Templates/Renew Resume
 */

?>

<h2><?php echo esc_html( $listing->get_title() ); ?> - <?php echo esc_html_x( 'Renew Fee Resume', 'renewal', 'WPBDM' ); ?></h2>

<p>
    <?php

    // phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
    esc_html(
        printf(
            esc_html_x( 'You are about to renew the listing %s.', 'renewal', 'WPBDM' ),
            '<a href="' . esc_url( $listing->get_permalink() ) . '">' . esc_html( $listing->get_title() ) . '</a>'
        )
    );
    ?>
    <br />
    <?php echo esc_html_x( 'In order to complete the renewal, please confirm fee selection.', 'renewal', 'WPBDM' ); ?>
</p>

<div class="wpbdp-payment-invoice">
    <?php
    // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
    echo $invoice_resume;
    ?>
</div>

<div id="wpbdp-claim-listings-confirm-fees">
    <div class="inner">
        <form action="" method="post">
            <?php wp_nonce_field( 'cancel renewal fee ' . $payment->id ); ?>
            <input type="submit" name="proceed-to-checkout" value="<?php echo esc_html_x( 'Continue to checkout', 'templates', 'wpbdp-claim-listings' ); ?>" />
            <input type="submit" name="return-to-fee-select" value="<?php echo esc_html_x( 'Return to fee selection', 'templates', 'wpbdp-claim-listings' ); ?>" />
        </form>
    </div>
</div>
