<?php // phpcs:disable ?>
<table class="wpbdp-payment-items-table" id="wpbdp-payment-items-<?php echo $payment->id; ?>">
    <thead>
        <tr>
            <th><?php _ex( 'Item', 'payment_items', 'WPBDM' ); ?></th>
            <th><?php _ex( 'Amount', 'payment_items', 'WPBDM' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $payment->payment_items as $item ) : ?>
        <tr class="item <?php echo $item['type']; ?>">
            <td>
                <?php print esc_html( $item['description'] ); ?>
                <?php if ( ! empty( $item['fee_id'] ) && wpbdp_get_option( 'include-fee-description' ) ) : ?>
                    <div  class="item-fee-description" class="fee-description"><?php print esc_html( wpbdp_get_fee_plan( $item['fee_id'] )->description ); ?></div>
                <?php endif; ?>
            </td>
            <td><?php echo wpbdp_currency_format( $item['amount'] ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th><?php _ex( 'Total', 'payment_items', 'WPBDM' ); ?></th>
            <td class="total"><?php echo wpbdp_currency_format( $payment->amount ); ?>
        </tr>
    </tfoot>
</table>
