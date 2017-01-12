<ul id="wpbdp-listing-metabox-tab-selector" class="wpbdp-admin-tab-nav">
    <li class="active">
        <a href="#wpbdp-listing-metabox-plan-info"><?php _ex( 'Fee Details', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="#wpbdp-listing-metabox-payments"><?php _ex( 'Recent Payments', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="#wpbdp-listing-metabox-other"><?php _ex( 'Access Key', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
</ul>

<!-- {{  Fee plan info. -->
<div id="wpbdp-listing-metabox-plan-info" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="1">
    <dl>
        <dt><?php _ex( 'Status', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
        <?php
        $status = apply_filters( 'wpbdp_admin_listing_display_status', array( $listing->get_status(), $listing->get_status_label() ), $listing );
        ?>
        <span class="tag plan-status paymentstatus <?php echo $status[0]; ?>"><?php echo $status[1]; ?></span>
        </dd>
        <dt><?php _ex( 'Fee Plan', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <select name="listing_plan[fee_id]" data-confirm-text="<?php echo esc_attr( _x( 'Do you want to override current listing fee details with those from "%s"?', 'listing metabox', 'WPBDM' ) ); ?>">
            <?php foreach ( $plans as $p ): ?>
            <?php
            $plan_info = array( 'id' => $p->id, 'label' => $p->label, 'days' => $p->days, 'images' => $p->images, 'sticky' => $p->sticky, 'expiration_date' => $p->calculate_expiration_time( $listing->get_expiration_time() ) );
            ?>
                <option value="<?php echo $p->id; ?>" <?php selected( $p->id, $current_plan ? $current_plan->fee_id : 0 ); ?> data-plan-info="<?php echo esc_attr( json_encode( $plan_info ) ); ?>"><?php echo $p->label; ?></option>
            <?php endforeach; ?>
            </select>

            <?php if ( $current_plan && $current_plan->is_recurring ): ?>
            <br /><span class="tag"><?php _ex( 'Recurring', 'listing metabox', 'WPBDM' ); ?>
            <?php endif; ?>
        </dd>
        <dt><?php _ex( 'Amount', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd><?php echo $current_plan ? wpbdp_currency_format( $current_plan->fee_price ) : '-'; ?></dd>
        <dt><?php _ex( 'Expires On', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <input type="text" name="listing_plan[expiration_date]" value="<?php echo $current_plan ? $current_plan->expiration_date : ''; ?>" placeholder="<?php _ex( 'Never', 'listing metabox', 'WPBDM' ); ?>" />
        </dd>
        <dt><?php _ex( '# of images', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd><input type="text" name="listing_plan[fee_images]" value="<?php echo $current_plan ? $current_plan->fee_images : 0; ?>" size="2" /></dd>
        <dt><?php _ex( 'Is Featured?', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <input type="checkbox" name="listing_plan[is_sticky]" value="1" <?php checked( $current_plan && $current_plan->is_sticky ); ?>>
        </dd>
    </dl>

    <ul class="wpbdp-listing-metabox-renewal-actions">
        <li>
            <a href="#" class="button button-small" onclick="window.prompt('<?php _ex( 'Renewal url (copy & paste)', 'admin infometabox', 'wpbdm' ); ?>', '<?php echo $listing->get_renewal_url(); ?>'); return false;"><?php _ex( 'Get renewal URL', 'admin infometabox', 'WPBDM' ); ?></a>
            <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'send-renewal-email', 'listing_id' => $listing->get_id() ) ) ); ?>">
                <?php _ex( 'Send renewal e-mail', 'admin infometabox', 'WPBDM' ); ?>
            </a>
        </li>
        <?php if ( $current_plan && $current_plan->expired ): ?>
        <li>
            <a href="<?php echo esc_url( add_query_arg( 'wpbdmaction', 'renewlisting' ) ); ?>" class="button-primary button button-small"><?php _ex( 'Renew listing', 'admin infometabox', 'WPBDM'); ?></a>
        </li>
        <?php endif; ?>
    </ul>
</div>
<!-- }} -->

<!-- {{ Recent payments. -->
<div id="wpbdp-listing-metabox-payments" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="2">
    <?php if ( $payments ): ?>
        <?php _ex( 'Click a transaction to see its details (and approve/reject).', 'listing metabox', 'WPBDM' ); ?>

            <?php foreach ( $payments as $payment ): ?>
            <?php $payment_link = esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->id ) ); ?>
            <div class="wpbdp-payment-item wpbdp-payment-status-<?php echo $payment->status; ?> cf">
                <div class="wpbdp-payment-item-row">
                    <div class="wpbdp-payment-date">
                        <a href="<?php echo $payment_link; ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->created_on ) ); ?></a>
                    </div>
                    <div class="wpbdp-payment-status"><span class="tag paymentstatus <?php echo $payment->status; ?>"><?php echo $payment->status; ?></span></div>
                </div>
                <div class="wpbdp-payment-item-row">
                    <div class="wpbdp-payment-summary"><a href="<?php echo $payment_link; ?>" title="<?php echo esc_attr( $payment->summary ); ?>"><?php echo $payment->summary; ?></a></div>
                    <div class="wpbdp-payment-total"><?php echo wpbdp_currency_format( $payment->amount ); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
    <?php else: ?>
        <?php _ex( 'This listing has no payments associated.', 'listing metabox', 'WPBDM' ); ?>
    <?php endif; ?>
</div>
<!-- }} -->

<div id="wpbdp-listing-metabox-other" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="3">
    <dl>
        <dt><?php _ex( 'Access Key', 'admin infometabox', 'WPBDM' ); ?></dt>
        <dd><input type="text" value="<?php esc_attr_e( $listing->get_access_key() ); ?>" /></dd>
    </dl>

</div>
