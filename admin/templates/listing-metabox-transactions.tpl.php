<?php
$_transaction_types = array(
        'initial' => _x('Initial Payment', 'admin infometabox', 'WPBDM'),
        'edit' => _x('Listing Edit', 'admin infometabox', 'WPBDM'),
        'renewal' => _x('Listing Renewal', 'admin infometabox', 'WPBDM'),
        'upgrade-to-sticky' => _x('Upgrade to sticky', 'admin infometabox', 'WPBDM'),
);

?>
<strong><?php _ex('Payments History', 'admin', 'WPBDM'); ?></strong>
<?php if ( current_user_can( 'administrator' ) ): ?>
<p><?php _ex( 'Click a payment to see the details or approve/reject the transaction.', 'admin listing metabox', 'WPBDM' ); ?></p>
<?php endif; ?>

<?php if ( ! $payments ): ?>
<p><?php _ex( 'There are no transactions associated to this listing.', 'listing metabox', 'WPBDM' ); ?></p>
<?php else: ?>
<table class="payments-list">
<?php foreach ( $payments as &$payment ): ?>
    <tr class="payment <?php echo $payment->get_status(); ?>">
        <td class="date">
            <a href="#" class="payment-details-link" data-id="<?php echo $payment->get_id(); ?>">
                <?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->get_created_on() ) ); ?>
            </a>
        </td>
        <td class="total">
            <a href="#" class="payment-details-link" data-id="<?php echo $payment->get_id(); ?>">
                <?php echo wpbdp_currency_format( $payment->get_total(), array( 'currency' => $payment->get_currency_code() ) ); ?>
            </a></td>
        <td class="status"><span class="tag paymentstatus <?php echo $payment->get_status(); ?>"><?php echo $payment->get_status(); ?></span></td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
