<!-- {{  Fee plan info. -->
<?php
/**
 * Listing information plan metabox
 *
 * @package Admin Templates/listing information plan
 */

// phpcs:disable

echo wp_nonce_field( 'update listing plan', 'wpbdp-admin-listing-plan-nonce', false, false );
?>
<div id="wpbdp-listing-metabox-plan-info" class="wpbdp-listing-metabox-tab wpbdp-admin-tab-content" tabindex="1">
    <h4><?php _ex( 'General Info', 'listing metabox', 'WPBDM' ); ?></h4>
    <dl>
        <dt><?php _ex( 'Listing Status', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
        <?php
        $status = apply_filters( 'wpbdp_admin_listing_display_status', array( $listing->get_status(), $listing->get_status_label() ), $listing );
        ?>
        <?php if ( 'incomplete' == $status[0] ): ?>
            <?php _ex( 'N/A', 'listing metabox', 'WPBDM' ); ?>
        <?php else: ?>
            <span class="tag plan-status paymentstatus <?php echo $status[0]; ?>"><?php echo $status[1]; ?></span>
        <?php endif; ?>
        </dd>
        <dt><?php _ex( 'Last renew date', 'listing metabox', 'WPBDM' ); ?></dt>
        <?php if ( $renewal_date = $listing->get_renewal_date() ): ?>
        <dd><?php echo esc_html( $renewal_date ); ?></dd>
        <?php else: ?>
        <dd><?php _ex( 'N/A', 'listing metabox', 'WPBDM' ); ?></dd>
        <?php endif; ?>
    </dl>

    <h4><?php _ex( 'Plan Details', 'listing metabox', 'WPBDM' ); ?></h4>
    <dl>
        <dt><?php _ex( 'Fee Plan', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-label">
                <?php if ( $current_plan ): ?>
                    <a href="<?php echo admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=edit-fee&id=' . $current_plan->fee_id ); ?>"><?php echo $current_plan->fee_label; ?></a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </span>
            <a href="#" class="edit-value-toggle"><?php _ex( 'Change plan', 'listing metabox', 'WPBDM' ); ?></a>
            <div class="value-editor">
                <input type="hidden" name="listing_plan[fee_id]" value="<?php echo $current_plan ? $current_plan->fee_id : ''; ?>" />
                <select name="" id="wpbdp-listing-plan-select">
                <?php foreach ( $plans as $p ): ?>
                <?php
                $plan_info = array( 'id' => $p->id, 'label' => $p->label, 'amount' => $p->amount ? wpbdp_currency_format( $p->amount ) : '', 'days' => $p->days, 'images' => $p->images, 'sticky' => $p->sticky, 'recurring' => $p->recurring,  'expiration_date' => $p->calculate_expiration_time( $listing->get_expiration_time() ) );
                ?>
                    <option value="<?php echo $p->id; ?>" <?php selected( $p->id, $current_plan ? $current_plan->fee_id : 0 ); ?> data-plan-info="<?php echo esc_attr( json_encode( $plan_info ) ); ?>"><?php echo $p->label; ?></option>
                <?php endforeach; ?>
                </select>

                <a href="#" class="update-value button"><?php _ex( 'OK', 'listing metabox', 'WPBDM' ); ?></a>
                <a href="#" class="cancel-edit button-cancel"><?php _ex( 'Cancel', 'listing metabox', 'WPBDM' ); ?></a>
        </div>
        </dd>
        <dt><?php _ex( 'Amount', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-amount">
                <?php echo $current_plan ? wpbdp_currency_format( $current_plan->fee_price ) : '-'; ?>
            </span>
        </dd>
        <dt><?php _ex( 'Expires On', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-expiration">
                <?php echo ( $current_plan && $current_plan->expiration_date ) ? wpbdp_date_full_format( strtotime( $current_plan->expiration_date ) ) : ( $listing->get_fee_plan() ? 'Never' : '-' ); ?>
            </span>
            <a href="#" class="edit-value-toggle"><?php _ex( 'Edit', 'listing metabox', 'WPBDM' ); ?></a>
            <div class="value-editor">
                <input type="text" name="listing_plan[expiration_date]" value="<?php echo ( $current_plan && $current_plan->expiration_date ) ? $current_plan->expiration_date : ''; ?>" placeholder="<?php _ex( 'Never', 'listing metabox', 'WPBDM' ); ?>" />

                <p>
                    <a href="#" class="update-value button"><?php _ex( 'OK', 'listing metabox', 'WPBDM' ); ?></a>
                    <a href="#" class="cancel-edit button-cancel"><?php _ex( 'Cancel', 'listing metabox', 'WPBDM' ); ?></a>
                </p>
            </div>
        </dd>
        <dt><?php _ex( '# of images', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-images">
                <?php echo $current_plan ? $current_plan->fee_images : '-'; ?>
            </span>
            <a href="#" class="edit-value-toggle"><?php _ex( 'Edit', 'listing metabox', 'WPBDM' ); ?></a>
            <div class="value-editor">
                <input type="text" name="listing_plan[fee_images]" value="<?php echo $current_plan ? $current_plan->fee_images : 0; ?>" size="2" />

                <a href="#" class="update-value button"><?php _ex( 'OK', 'listing metabox', 'WPBDM' ); ?></a>
                <a href="#" class="cancel-edit button-cancel"><?php _ex( 'Cancel', 'listing metabox', 'WPBDM' ); ?></a>
            </div>
        </dd>
        <dt><?php _ex( 'Is Featured?', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-is_sticky">
                <?php echo $current_plan && $current_plan->is_sticky ? _x( 'Yes', 'listing metabox', 'WPBDM' ) : _x( 'No', 'listing metabox', 'WPBDM' ); ?>
            </span>
<!-- Removed the ability to set a listing as "Featured" in "info" metabox for 5.1.6 according to instructions on issue #3413 -->
        </dd>
        <dt><?php _ex( 'Is Recurring?', 'listing metabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="display-value" id="wpbdp-listing-plan-prop-is_recurring">
                <?php echo $current_plan && $current_plan->is_recurring ? _x( 'Yes', 'listing metabox', 'WPBDM' ) : _x( 'No', 'listing metabox', 'WPBDM' ); ?>
            </span>
        </dd>
    </dl>

    <ul class="wpbdp-listing-metabox-renewal-actions">
        <li>
            <a href="#" class="button button-small" onclick="window.prompt('<?php _ex( 'Renewal url (copy & paste)', 'admin infometabox', 'wpbdm' ); ?>', '<?php echo $listing->get_renewal_url(); ?>'); return false;"><?php _ex( 'Get renewal URL', 'admin infometabox', 'WPBDM' ); ?></a>
            <a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'send-renewal-email', 'listing_id' => $listing->get_id() ) ) ); ?>">
                <?php _ex( 'Send renewal e-mail', 'admin infometabox', 'WPBDM' ); ?>
            </a>
        </li>
        <?php if ( 'pending_renewal' == $listing->get_status() || ( $current_plan && $current_plan->expired ) ): ?>
        <li>
            <a href="<?php echo esc_url( add_query_arg( 'wpbdmaction', 'renewlisting' ) ); ?>" class="button-primary button button-small"><?php _ex( 'Renew listing', 'admin infometabox', 'WPBDM'); ?></a>
        </li>
        <?php endif; ?>
    </ul>
</div>
<!-- }} -->
