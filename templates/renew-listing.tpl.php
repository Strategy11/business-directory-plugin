<?php
/**
 * Template Renew listing.
 *
 * @package Templates/Renew Listing
 */

// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
?>
<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">

    <h2><?php echo esc_html_x( 'Renew Listing', 'templates', 'WPBDM' ); ?></h2>

    <?php if ( isset( $payment ) && $payment ) : ?>
        <form action="<?php echo esc_url( $payment->get_checkout_url() ); ?>" method="POST">
        <input type="submit" name="go-to-checkout" value="<?php echo esc_html_x( 'Proceed to Checkout', 'renewal', 'WPBDM' ); ?>" />
        </form>
    <?php else : ?>
        <p>
            <?php printf( esc_html_x( 'You are about to renew your listing "%s" publication.', 'templates', 'WPBDM' ), esc_html( $listing->get_title() ) ); ?>
        </p>
        <p>
            <?php echo esc_html_x( 'Please select a fee option or click "Do not renew my listing" to remove your listing from the directory.', 'templates', 'WPBDM' ); ?>
        </p>

        <form id="wpbdp-renewlisting-form" method="post" action="">
            <?php
            echo wpbdp_render(
                'plan-selection',
                array(
                    'plans'      => $plans,
                    'selected'   => $current_plan->fee_id,
                    'categories' => $listing->get_categories(),
                )
            );
            ?>

            <p><input type="submit" class="submit" name="go-to-checkout" value="<?php echo esc_html_x( 'Continue', 'templates', 'WPBDM' ); ?>" /></p>

            <div class="do-not-renew-listing">
                <p><?php echo esc_html_x( 'Clicking the button below will cause your listing to be permanently removed from the directory.', 'renewal', 'WPBDM' ); ?></p>

                <input type="submit" class="submit" name="cancel-renewal" value="<?php echo esc_html_x( 'Do not renew my listing', 'templates', 'WPBDM' ); ?>" />
            </div>
        </form>
    <?php endif; ?>

</div>
