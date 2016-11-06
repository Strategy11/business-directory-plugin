<?php
echo wpbdp_admin_header(
    str_replace( '<id>',
                 $payment->get_id(),
                 _x( 'Payment #<id>', 'admin payments', 'WPBDM' ) ) 
);
?>
<?php wpbdp_admin_notices(); ?>

<div id="poststuff">
<div id="post-body" class="metabox-holder">



<!-- Basic details. {{ -->
<div id="wpbdp-admin-payment-info-box" class="postbox">
    <h3 class="hndle"><span>XXX</span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <div class="wpbdp-admin-box-row">
                <label>Payment ID:</label>
                <?php echo $payment->get_id(); ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label>Listing:</label>
                <?php echo $payment->get_listing_id(); ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label>Status:</label>
                <?php echo $payment->get_status(); ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label>Date:</label>
                <?php echo $payment->created_on; ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label>Time:</label>
                <?php echo $payment->created_on; ?>
            </div>
            <div class="wpbdp-admin-box-row">
                <label>Gateway:</label>
                <?php echo $payment->get_gateway(); ?>
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

<div id="wpbdp-admin-payment-items-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Items', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <?php print_r( $payment->get_items() ); ?>
        </div>
    </div>
</div>

<div id="wpbdp-admin-payment-items-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Customer Details', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <?php print_r( $payment->payerinfo ); ?>
        </div>
    </div>
</div>

<div id="wpbdp-admin-payment-items-box" class="postbox">
    <h3 class="hndle"><span><?php _ex( 'Notes / Log', 'payments admin', 'WPBDM' ); ?></span></h3>
    <div class="inside">
        <div class="wpbdp-admin-box">
            <?php print_r( $payment->notes ); print_r( $payment->extra_data ); ?>
        </div>
    </div>
</div>


</div>
</div>
<?php echo wpbdp_admin_footer(); ?>
