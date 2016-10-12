<ul id="wpbdp-listing-metabox-tab-selector" class="wpbdp-admin-tab-nav">
    <li class="active">
        <a href="#wpbdp-listing-metabox-plan-info"><?php _ex( 'Fee Details', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="#wpbdp-listing-metabox-payments"><?php _ex( 'Recent Payments', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
    <li>
        <a href="#wpbdp-listing-metabox-other"><?php _ex( 'Other', 'listing metabox', 'WPBDM' ); ?></a>
    </li>
</ul>

<!-- {{  Fee plan info. -->
<div id="wpbdp-listing-metabox-plan-info" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="1">
    <dl>
        <dt><?php _ex( 'Status', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="tag plan-status paymentstatus <?php echo $current_plan->status; ?>">
            <?php
            // todo: before next-release
                switch ( $current_plan->status ):
                    case 'expired':
                        _ex( 'Expired', 'admin infometabox', 'WPBDM' );
                        break;
                    case 'pending':
                        _ex( 'Payment Pending', 'admin infometabox', 'WPBDM' );
                        break;
                    case 'ok':
                    default:
                        _ex( 'OK', 'admin infometabox', 'WPBDM');
                endswitch;
                ?>
            </span>
        </dd>
        <dt><?php _ex( 'Fee Plan', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <select name="listing_plan[fee_id]">
            <?php foreach ( $plans as $p ): ?>
                <option value="<?php echo $p->id; ?>" <?php checked( $p->id, $current_plan->fee_id ); ?>><?php echo $p->label; ?></option>
            <?php endforeach; ?>
            </select>

            <?php if ( $current_plan->is_recurring ): ?>
            <br /><span class="tag"><?php _ex( 'Recurring', 'listing metabox', 'WPBDM' ); ?>
            <?php endif; ?>
        </dd>
        <dt><?php _ex( 'Amount', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd><?php echo $current_plan->fee_price; ?></dd>
        <dt><?php _ex( 'Expires On', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <input type="text" name="listing_plan[expiration_date]" value="<?php echo $current_plan->expiration_date; ?>" placeholder="<?php _ex( 'Never', 'listing metabox', 'WPBDM' ); ?>" />
        </dd>
        <dt><?php _ex( '# of images', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd><input type="text" name="listing_plan[fee_images]" value="<?php echo $current_plan->fee_images; ?>" size="2" /></dd>
        <dt><?php _ex( 'Is Featured?', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <input type="checkbox" name="listing_plan[is_sticky]" value="1" <?php checked( $current_plan->is_sticky ); ?>>
        </dd>
    </dl>

    <ul class="wpbdp-listing-metabox-renewal-actions">
        <li>
            <a href="#" class="button button-small" onclick="window.prompt('<?php _ex( 'Renewal url (copy & paste)', 'admin infometabox', 'wpbdm' ); ?>', '<?php echo $listing->get_renewal_url(); ?>'); return false;"><?php _ex( 'Get renewal URL', 'admin infometabox', 'WPBDM' ); ?></a>
            <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'send-renewal-email', 'listing_id' => $listing->get_id() ) ) ); ?>">
                <?php _ex( 'Send renewal e-mail', 'admin infometabox', 'WPBDM' ); ?>
            </a>
        </li>
        <?php if ( $current_plan->expired ): ?>
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

        <table>
            <tbody>
            <?php foreach ( $payments as $payment ): ?>
                <?php $payment_link = esc_url( admin_url( 'admin.php?page=wpbdp_admin_payments&wpbdp-view=details&payment-id=' . $payment->get_id() ) ); ?>
                <tr class="wpbdp-payment-status-<?php echo $payment->get_status(); ?>">
                    <td class="wpbdp-payment-date">
                        <a href="<?php echo $payment_link; ?>"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->get_created_on() ) ); ?></a>
                    </td>
                    <td class="wpbdp-payment-total"><?php echo wpbdp_currency_format( $payment->get_total() ); ?></td>
                    <td class="wpbdp-payment-status"><span class="tag paymentstatus <?php echo $payment->get_status(); ?>"><?php echo $payment->get_status(); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
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
