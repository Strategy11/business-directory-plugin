
<table class="wpbdp-payment-items-table" id="wpbdp-payment-items-<?php echo $payment->get_id(); ?>">
    <thead>
        <tr>
            <th><?php _ex( 'Item', 'payment_items', 'WPBDM' ); ?></th>
            <th><?php _ex( 'Amount', 'payment_items', 'WPBDM' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $payment->get_items() as $item ): ?>
        <tr class="item <?php echo $item->item_type; ?>">
            <td><?php print esc_html( $item->description ); ?></td>
            <td><?php echo wpbdp_currency_format( $item->amount ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th><?php _ex( 'Total', 'payment_items', 'WPBDM' ); ?></th>
            <td class="total"><?php echo wpbdp_currency_format( $payment->get_total() ); ?>
        </tr>
    </tfoot>
</table>
