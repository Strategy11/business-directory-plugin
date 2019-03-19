<?php
/**
 * Checkout template
 *
 * @package BDP/Templates/Checkout
 */

// phpcs:disable
?>

<h2><?php _ex( 'Checkout', 'checkout', 'WPBDM' ); ?></h2>

<div class="wpbdp-payment-invoice">
    <?php echo $invoice; ?>
</div>

<form id="wpbdp-checkout-form" action="" method="POST">
    <input type="hidden" name="payment" value="<?php echo $payment->payment_key; ?>" />
    <input type="hidden" name="action" value="do_checkout" />
    <input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />

    <?php echo $checkout_form_top; ?>

    <div class="wpbdp-checkout-errors wpbdp-checkout-section">
        <?php if ( ! empty( $errors ) ) : ?>
            <?php foreach ( $errors as $error ) : ?>
            <div class="wpbdp-msg error wpbdp-checkout-error"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ( $payment->has_item_type( 'discount_code' ) && $payment->has_item_type( 'recurring_plan' ) && 0.0 == $payment->amount ) : ?>
            <div class="wpbdp-msg notice"><?php _ex( 'Recurring fee plans require a payment method to renew your listing at the end of the term.', 'checkout', 'WPBDM' ); ?></div>
        <?php endif; ?>
    </div>


    <div class="wpbdp-checkout-gateway-selection wpbdp-checkout-section">
        <h3><?php _ex( 'Select a Payment Method', 'checkout', 'WPBDM' ); ?></h3>
        <?php foreach ( wpbdp()->payment_gateways->get_available_gateways( array( 'currency_code' => $payment->currency_code ) ) as $gateway ) : ?>
        <label><input type="radio" name="gateway" value="<?php echo $gateway->get_id(); ?>" <?php checked( $chosen_gateway->get_id(), $gateway->get_id() ); ?>/> <?php echo $gateway->get_logo(); ?></label>
        <?php endforeach; ?>
        <div class="wpbdp-checkout-submit wpbdp-no-js"><input type="submit" value="<?php _ex( 'Next', 'checkout', 'WPBDM' ); ?>" /></div>
    </div>
    <!-- end .wpbdp-checkout-gateway-selection -->

    <div id="wpbdp-checkout-form-fields" class="wpbdp-payment-gateway-<?php echo $chosen_gateway->get_id(); ?>-form-fields">
        <?php echo $checkout_form; ?>
    </div>

    <?php echo $checkout_form_bottom; ?>
</form>

