<?php
echo wpbdp_admin_header(
    str_replace( '<id>',
                 $payment->id,
                 _x( 'Payment #<id>', 'admin payments', 'WPBDM' ) ),
    'payments-details'
);
?>
<?php wpbdp_admin_notices(); ?>

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">

<div id="postbox-container-1" class="postbox-container">

<!-- Basic details. {{ -->
<div id="wpbdp-admin-payment-info-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Overview', 'admin payments', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Payment ID:', 'admin payments', 'WPBDM' ); ?></label>
                <?php echo $payment->id; ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Listing:', 'admin payments', 'WPBDM' ); ?></label>
                <a href="<?php echo $payment->get_listing()->get_admin_edit_link(); ?>"><?php echo esc_html( $payment->get_listing()->get_title() ); ?></a>
            </div>
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Status:', 'admin payments', 'WPBDM' ); ?></label>

                <select name="payment[status]">
                <?php foreach ( WPBDP_Payment::get_stati() as $status_id => $status_label ): ?>
                    <option value="<?php echo $status_id; ?>" <?php selected( $status_id, $payment->status ); ?>><?php echo $status_label; ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Date:', 'admin payments', 'WPBDM' ); ?></label>
                <input type="text" name="payment[created_on_date]" value="<?php echo date( 'Y-m-d', strtotime( $payment->created_on ) ); ?>" />
            </div>
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Time:', 'admin payments', 'WPBDM' ); ?></label>
                <input type="text" maxlength="2" name="payment[created_on_time_hour]" value="<?php echo $payment->created_on_time['hour']; ?>" class="small-text" /> : 
                <input type="text" maxlength="2" name="payment[created_on_time_min]" value="<?php echo $payment->created_on_time['minute']; ?>" class="small-text" />
            </div>
            <div class="wpbdp-admin-box-row">
                <label><?php _ex( 'Gateway:', 'admin payments', 'WPBDM' ); ?></label>
                <?php echo $payment->gateway ? $payment->gateway : '--'; ?>
            </div>
        </div>
    </div>
    <div id="major-publishing-actions">
        <div id="delete-action">
            <a href="#" class="wpbdp-admin-delete-link"><?php _ex( 'Delete Payment', 'payments admin', 'WPBDM' ); ?></a>
        </div>
        <input type="submit" class="button button-primary right" value="<?php _ex( 'Save Payment', 'payments admin', 'WPBDM' ); ?>" />
        <div class="clear"></div>
    </div>
</div>
<!-- }} -->

</div>

<div id="postbox-container-2" class="postbox-container">

<div id="wpbdp-admin-payment-items-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Details', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <?php foreach ( $payment->payment_items as $item ): ?>
            <div class="wpbdp-admin-box-row payment-item cf">
                <span class="payment-item-type"><?php echo $item['type']; ?></span>
                <span class="payment-item-description"><?php echo $item['description']; ?></span>
                <span class="payment-item-amount"><?php echo wpbdp_currency_format( $item['amount'] ); ?></span>
            </div>
            <?php endforeach; ?>
            <div class="wpbdp-admin-box-row payment-totals payment-item cf">
                <span class="payment-item-type">&nbsp;</span>
                <span class="payment-item-description"><?php _ex( 'Total:', 'payments admin', 'WPBDM' ); ?></span>
                <span class="payment-item-amount"><?php echo wpbdp_currency_format( $payment->amount ); ?></span>
            </div>
        </div>
    </div>
</div>

<div id="wpbdp-admin-payment-details-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Customer Details', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <?php print_r( $payment->payer_details ); ?>
        </div>
    </div>
</div>

<div id="wpbdp-admin-payment-notes-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Notes & Log', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <div id="wpbdp-payment-notes">
                <div class="no-notes" style="<?php if ( $payment->payment_notes ): ?>display: none;<?php endif; ?>"><?php _ex( 'No notes.', 'payments admin', 'WPBDM' ); ?></div>
                <?php foreach ( $payment->payment_notes as $note ): ?>
                    <?php echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/payments-note.tpl.php', array( 'note' => $note, 'payment_id' => $payment->id ) ); ?>
                <?php endforeach; ?>
            </div>

            <div class="wpbdp-payment-notes-and-log-form">
                <textarea name="payment_note" class="large-text"></textarea>
                <p>
                    <button id="wpbdp-payment-notes-add" class="button button-secondary right" data-payment-id="<?php echo $payment->id; ?>"><?php _ex( 'Add Note', 'payment admins', 'WPBDM' ); ?></button>
                </p>
            </div>
        </div>
        <div class="clear"></div>
    </div>
</div>

</div>


</div>
</div>
<?php echo wpbdp_admin_footer(); ?>
