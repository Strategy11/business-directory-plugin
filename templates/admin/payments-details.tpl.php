<?php
echo wpbdp_admin_header(
    str_replace( '<id>',
                 $payment->id,
                 _x( 'Payment #<id>', 'admin payments', 'WPBDM' ) ),
    'payments-details',
    array(
        array( _x( '← Return to "Payment History"', 'payments admin', 'WPBDM' ), esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments' ) ) )
    )
);
?>
<?php wpbdp_admin_notices(); ?>

<form action="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=payment_update' ) ); ?>" method="post">
    <input type="hidden" name="payment[id]" value="<?php echo $payment->id; ?>" />

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">

<div id="postbox-container-1" class="postbox-container">

<!-- Basic details. {{ -->
<div class="meta-box-sortables">
    <div id="wpbdp-admin-payment-info-box" class="postbox">
        <button type="button" class="handlediv button-link" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
        <h2 class="hndle"><span><?php _ex( 'Overview', 'admin payments', 'WPBDM' ); ?></span></h2>
        <div class="inside">
            <div class="wpbdp-admin-box with-separators">
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
                    <input type="text" name="payment[created_at_date]" value="<?php echo date( 'Y-m-d', strtotime( $payment->created_at ) ); ?>" />
                </div>
                <div class="wpbdp-admin-box-row">
                    <label><?php _ex( 'Time:', 'admin payments', 'WPBDM' ); ?></label>
                    <input type="text" maxlength="2" name="payment[created_at_time_hour]" value="<?php echo str_pad( $payment->created_at_time['hour'], 2, '0', STR_PAD_LEFT ); ?>" class="small-text" /> : 
                    <input type="text" maxlength="2" name="payment[created_at_time_min]" value="<?php echo str_pad( $payment->created_at_time['minute'], 2, '0', STR_PAD_LEFT ); ?>" class="small-text" />
                </div>
                <div class="wpbdp-admin-box-row">
                    <label><?php _ex( 'Gateway:', 'admin payments', 'WPBDM' ); ?></label>
                    <?php /* translators: Gateway: (Not yet set) */ ?>
                    <?php echo $payment->gateway ? $payment->gateway : _x( '(Not yet set)', 'payments admin', 'WPBDM' ); ?>
                </div>
            </div>
        </div>
        <div id="major-publishing-actions">
            <div id="delete-action">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=payment_delete&payment-id=' . $payment->id ) ); ?>" class="wpbdp-admin-delete-link wpbdp-admin-confirm"><?php _ex( 'Delete Payment', 'payments admin', 'WPBDM' ); ?></a>
            </div>
            <input type="submit" class="button button-primary right" value="<?php _ex( 'Save Payment', 'payments admin', 'WPBDM' ); ?>" />
            <div class="clear"></div>
        </div>
    </div>
</div>
<!-- }} -->

</div>

<div id="postbox-container-2" class="postbox-container">
<div class="meta-box-sortables">

<div id="wpbdp-admin-payment-items-box" class="postbox">
    <button type="button" class="handlediv button-link" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
    <h2 class="hndle"><span><?php _ex( 'Details', 'payments admin', 'WPBDM' ); ?></span></h2>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <div class="wpbdp-admin-box-row payment-item-header cf">
                <span class="payment-item-type"><?php _ex( 'Item Type', 'payments admin', 'WPBDM' ); ?></span>
                <span class="payment-item-description"><?php _ex( 'Description', 'payments admin', 'WPBDM' ); ?></span>
                <span class="payment-item-amount"><?php _ex( 'Amount', 'payments admin', 'WPBDM' ); ?></span>
            </div>
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

<?php
$customer = $payment->payer_details;
?>
<div id="wpbdp-admin-payment-details-box" class="postbox closed">
    <button type="button" class="handlediv button-link" aria-expanded="false"><span class="toggle-indicator" aria-hidden="true"></span></button>
    <h2 class="hndle"><span><?php _ex( 'Customer Details', 'payments admin', 'WPBDM' ); ?></span></h2>
    <div class="inside">
        <div class="wpbdp-admin-box with-separators">
            <div class="wpbdp-admin-box-row customer-info-basic cf">
                <div class="customer-email">
                    <label><?php _ex( 'E-Mail:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_email]" value="<?php echo $customer['email']; ?>" />
                </div>

                <div class="customer-first-name">
                    <label><?php _ex( 'First Name:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_first_name]" value="<?php echo $customer['first_name']; ?>" />
                </div>

                <div class="customer-last-name">
                    <label><?php _ex( 'Last Name:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_last_name]" value="<?php echo $customer['last_name']; ?>" />
                </div>
            </div>
            <div class="wpbdp-admin-box-row customer-info-address cf">
                <div class="customer-address-country">
                    <label><?php _ex( 'Country:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][country]" value="<?php echo $customer['country']; ?>" />
                </div>
                <div class="customer-address-state">
                    <label><?php _ex( 'State:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][state]" value="<?php echo $customer['state']; ?>" />
                </div>
                <div class="customer-address-city">
                    <label><?php _ex( 'City:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][city]" value="<?php echo $customer['city']; ?>" />
                </div>
                <div class="customer-address-zipcode">
                    <label><?php _ex( 'ZIP Code:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][zip]" value="<?php echo $customer['zip']; ?>" />
                </div>
                <div class="customer-address-line1">
                    <label><?php _ex( 'Address Line 1:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][address]" value="<?php echo $customer['address']; ?>" />
                </div>
                <div class="customer-address-line2">
                    <label><?php _ex( 'Address Line 2:', 'payments admin', 'WPBDM' ); ?></label>
                    <input type="text" name="payment[payer_data][address_2]" value="<?php echo $customer['address_2']; ?>" />
                </div>
            </div>
        </div>
    </div>
</div>

<div id="wpbdp-admin-payment-notes-box" class="postbox">
    <button type="button" class="handlediv button-link" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
    <h2 class="hndle"><span><?php _ex( 'Notes & Log', 'payments admin', 'WPBDM' ); ?></span></h2>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <div id="wpbdp-payment-notes">
                <div class="no-notes" style="<?php if ( $payment->payment_notes ): ?>display: none;<?php endif; ?>"><?php _ex( 'No notes.', 'payments admin', 'WPBDM' ); ?></div>
                <?php foreach ( $payment->payment_notes as $note ): ?>
                    <?php echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/payments-note.tpl.php', array( 'note' => $note, 'payment_id' => $payment->id ) ); ?>
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
</div>

</form>
<?php echo wpbdp_admin_footer(); ?>
