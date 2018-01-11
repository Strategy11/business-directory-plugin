<h3><?php _ex( 'Manage Recurring Payments - Cancel Subscription', 'manage recurring', 'WPBDM' ); ?></h3>

<div id="wpbdp-manage-recurring-cancel">
    <h4><?php _ex( 'Plan Details', 'manage recurring', 'WPBDM' ); ?></h4>

    <dl>
        <dt>
            <?php _ex( 'Name:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo $plan->fee_label; ?>
        </dd>
        <dt>
            <?php _ex( 'Cost:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php printf( _x( '%s every %s days.', 'manage recurring', 'WPBDM' ),
                              wpbdp_currency_format( $plan->fee_price ),
                              $plan->fee_days ); ?>
        </dd>
        <!--<dt>
            <?php _ex( 'Number of images:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo $plan->fee_images; ?>
        </dd>-->
        <dt>
            <?php _ex( 'Expires on:', 'manage recurring', 'WPBDM' ); ?>
        </dt>
        <dd>
            <?php echo date_i18n( get_option( 'date_format' ), strtotime( $plan->expiration_date ) ); ?>
        </dd>
    </dl>

    <form class="wpbdp-cancel-subscription-form" action="" method="post">
        <p><?php echo _x( 'Are you sure you want to cancel this subscription?', 'manage recurring', 'WPBDM' ); ?></p>
        <p>
            <input class="button button-primary" type="submit" name="cancel-subscription" value="<?php echo esc_attr( _x( 'Yes, cancel subscription', 'manage recurring', 'WPBDM' ) ); ?>" />
            <input class="button" type="submit" name="return-to-subscriptions" value="<?php echo esc_attr( _x( 'No, go back to my subscriptions', 'manage recurring', 'WPBDM' ) ); ?>" />
        </p>
    </form>
</div>
