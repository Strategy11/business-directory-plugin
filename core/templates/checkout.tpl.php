<?php
$nonce = wp_create_nonce( 'wpbdp-checkout-' . $payment->id );
?>
<h2><?php _ex( 'Checkout', 'checkout', 'WPBDM') ;?></h2>

<div class="wpbdp-payment-invoice">
    <?php echo $invoice; ?>
</div>

<div class="wpbdp-checkout-gateway-selection wpbdp-checkout-section">
    <h3><?php _ex( 'Select a Payment Method', 'checkout', 'WPBDM' ); ?></h3>
    <form action="<?php echo remove_query_arg( 'gateway' ); ?>" method="POST">
        <input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />
        <input type="hidden" name="action" value="select_gateway" />
        <?php foreach ( wpbdp()->payment_gateways->get_available_gateways( array( 'currency_code' => $payment->currency_code ) ) as $gateway ): ?>
        <label><input type="radio" name="gateway" value="<?php echo $gateway->get_id(); ?>" <?php checked( $chosen_gateway->get_id(), $gateway->get_id() ); ?>/> <?php echo $gateway->get_title(); ?></label>
        <?php endforeach; ?>
        <div class="wpbdp-checkout-submit wpbdp-no-js"><input type="submit" value="<?php _ex( 'Next', 'checkout', 'WPBDM' ); ?>" /></div>
    </form>
</div>
<!-- end .wpbdp-checkout-gateway-selection -->

<?php if ( ! empty( $errors ) ): ?>
    <?php var_dump( $errors ); ?>
<?php endif; ?>

<form id="wpbdp-checkout-form" action="" method="POST">
    <input type="hidden" name="action" value="do_checkout" />
    <input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />
    <input type="hidden" name="gateway" value="<?php echo $chosen_gateway->get_id(); ?>" />
    <?php echo $checkout_form; ?>

    <div class="wpbdp-checkout-submit"><input type="submit" value="<?php _ex( 'Pay Now', 'checkout', 'WPBDM' ); ?>" /></div>
</form>
