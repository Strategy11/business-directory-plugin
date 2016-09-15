<?php
$current_plan = $listing->get_fee_plan();
?>
<div class="listing-fee-change">
    <h2>
        <?php _ex( 'Listing Fee Selection', 'admin listing fee', 'WPBDM' ); ?>
    </h2>
    <p><?php _ex( 'Select a fee plan to be used for this listing.', 'admin listing fee', 'WPBDM' ); ?></p>

    <div class="fee-selection">
    <?php foreach ( $plans as $f ): ?>
        <div class="fee">
            <?php if ( $current_plan->fee_id == $f->id ): ?>
                <span class="tag"><?php _ex( 'Current', 'admin listing fee', 'WPBDM' ); ?></span>
            <?php else: ?>
                <a href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'assignfee', 'fee_id' => $f->id ), admin_url( 'post.php?post=' . $listing->get_id() . '&action=edit' ) ) ); ?>" class="button choose-this">
                    <?php _ex( 'Use this fee', 'admin listing fee', 'WPBDM' ); ?>
                </a>
            <?php endif; ?>

            <strong><?php echo $f->label; ?></strong><br />
            <div class="details">
                <?php echo wpbdp_currency_format( $f->amount ); ?> &#149;
                <?php echo sprintf(_nx('%d image', '%d images', $f->images, 'admin infometabox', 'WPBDM'), $f->images); ?> &#149;
                <?php if ($f->days == 0): ?>
                    <?php _ex('Listing never expires', 'admin infometabox', 'WPBDM'); ?>
                <?php else: ?>
                    <?php echo sprintf(_nx('%d day', '%d days', $f->days, 'admin infometabox', 'WPBDM'), $f->days); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
