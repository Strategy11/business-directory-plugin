<?php
$plan = $listing->get_fee_plan();
$image_count = count( $listing->get_images( 'ids' ) );
?>
<strong><?php _ex('Fee Information', 'admin infometabox', 'WPBDM'); ?></strong>

<?php _ex('Payment Mode:', 'admin infometabox', 'WPBDM'); ?> <?php echo wpbdp_payments_api()->payments_possible() ? _x('Paid', 'admin infometabox', 'WPBDM') : _x('Free', 'admin infometabox', 'WPBDM'); ?><br />
<?php
    if (current_user_can('administrator')) {
        echo sprintf(_x('To change your payment mode, go to <a href="%s">Payment Settings</a>.', 'admin infometabox', 'WPBDM'), 
             admin_url('admin.php?page=wpbdp_admin_settings&groupid=payment')  );
    }
?>

<?php if (!wpbdp_payments_api()->payments_possible() && current_user_can('administrator')): ?>
    <p><i><?php _ex('Note: In Free mode, the fee plans will always be set to "Free Listing" below.', 'admin infometabox', 'WPBDM'); ?></i></p>
<?php endif; ?>

<div class="listing-plan">
    <?php if ( current_user_can( 'administrator' ) ): ?><div class="spinner"></div><?php endif; ?>
    <div class="plan-details <?php if ( $plan->expired ): ?>expired<?php endif; ?>">
    <dl>
        <dt><?php _ex( 'Plan', 'admin infometabox', 'WPBDM' ); ?></dt>
        <dd class="plan-label"><?php echo $plan->fee_label; ?></dd>

        <dt><?php _ex( 'Status', 'admin infometabox', 'WPBDM' ); ?></dt>
        <dd>
            <span class="tag plan-status <?php echo $plan->status; ?>">
            <?php
            // todo: before next-release
                switch ( $plan->status ):
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

        <dt><?php _ex('# images', 'admin infometabox', 'WPBDM'); ?></dt>
        <dd><?php echo min( $image_count, $plan->fee_images); ?> / <?php echo $plan->fee_images; ?></dd>

        <dt>
            <?php if ( $plan->expired ): ?>
                <?php _ex('Expired On', 'admin infometabox', 'WPBDM'); ?>
            <?php else: ?>
                <?php _ex('Expires On', 'admin infometabox', 'WPBDM'); ?>
            <?php endif; ?> 
        </dt>
        <dd class="expiration-date-info">
            <span class="expiration-date">
                <a href="#" class="expiration-change-link"
                   title="<?php _ex( 'Click to manually change expiration date.', 'admin infometabox', 'wpbdm' ); ?>"
                   data-listing_id="<?php echo $listing->get_id(); ?>"
                   data-date="<?php echo $plan->expiration_date ? date('Y-m-d', strtotime( $plan->expiration_date ) ) : date( 'y-m-d', strtotime( '+10 years' ) ); ?>"
                   data-never-text="<?php _ex( 'Never Expires', 'admin infometabox', 'wpbdm' ); ?>">
                    <?php echo $plan->expiration_date ? date_i18n( get_option( 'date_format' ), strtotime( $plan->expiration_date ) ) : _x( 'Never', 'admin infometabox', 'wpbdm' ); ?>
                </a>
            </span>
            <div class="datepicker"></div>
        </dd>
    </dl>
    </div>

    <?php if ( current_user_can( 'administrator' ) ): ?>
    <ul class="admin-actions">
    <!-- todo: before next-release -->
    <?php if ( isset( $plan->payment_id ) ): ?>
        <li><a href="#" class="payment-details-link" data-id="<?php echo $plan->payment_id; ?>"><?php _ex( 'See payment info', 'admin infometabox', 'wpbdm' ); ?></a></li>
    <?php endif; ?>

    <li>
        <a href="#" onclick="window.prompt('<?php _ex( 'Renewal url (copy & paste)', 'admin infometabox', 'wpbdm' ); ?>', '<?php echo $listing->get_renewal_url(); ?>'); return false;"><?php _ex( 'Show renewal link', 'admin infometabox', 'wpbdm' ); ?></a>
    </li>
    <li>
        <a href="<?php echo esc_url( add_query_arg( array( 'wpbdmaction' => 'send-renewal-email', 'listing_id' => $listing->get_id() ) ) ); ?>">
            <?php _ex( 'Send renewal e-mail to user', 'admin infometabox', 'wpbdm' ); ?>
        </a>
    </li>
    <li>
        <a href="#" data-listing-id="<?php echo $listing->get_id(); ?>" class="change-fee">
            <?php ( $plan->expired ? _ex( 'Renew manually...', 'admin infometabox', 'wpbdm' ) : _ex('Change fee...', 'admin infometabox', 'wpbdm') ); ?>
        </a>
    </li>
    </ul>
    <?php endif; ?>

    <?php if ( $plan->expired ): ?>
    <a href="<?php echo esc_url( add_query_arg( 'wpbdmaction', 'renewlisting' ) ); ?>" class="button-primary button"><?php _ex( 'Renew listing using the current plan', 'admin infometabox', 'wpbdm'); ?></a>
    <?php endif; ?>
</div>
