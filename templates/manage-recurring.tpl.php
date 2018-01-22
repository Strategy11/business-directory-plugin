<h3><?php _ex( 'Manage Recurring Payments', 'manage recurring', 'WPBDM' ); ?></h3>

<table id="wpbdp-manage-recurring">
    <thead>
        <th class="listing-title"><?php _ex( 'Listing', 'manage recurring', 'WPBDM' ); ?></th>
        <th class="subscription-details"><?php _ex( 'Subscription / Fee Plan', 'manage subscriptions', 'WPBDM' ); ?></th>
    </thead>
    <tbody>
    <?php foreach ( $listings as $listing ): ?>
    <tr>
        <td class="listing-title">
            <b><?php if ( $listing->is_published() ): ?>
                <?php printf( '<a href="%s">%s</a>',
                              esc_url( $listing->get_permalink() ),
                              $listing->get_title() ); ?>
            <?php else: ?>
                <?php echo $listing->get_title(); ?>
            <?php endif; ?></b>
        </td>
        <td class="subscription-details">
            <?php
                $fee = $listing->get_fee_plan();

                $subscription_amount = wpbdp_currency_format( $fee->fee_price );
                $subscription_days = '<i>' . $fee->fee_days . '</i>';
                $subscription_expiration_date = '<i>' . date_i18n( get_option( 'date_format' ), strtotime( $fee->expiration_date ) ) . '</i>';

                $subscription_details = _x( '%s each %s days. Next renewal is on %s.', 'manage recurring', 'WPBDM' );
                $subscription_details = sprintf( $subscription_details, $subscription_amount, $subscription_days, $subscription_expiration_date );

                $cancel_url = add_query_arg( array(
                    'action' => 'cancel-subscription',
                    'listing' => $listing->get_id(),
                    'nonce' => wp_create_nonce( 'cancel-subscription-' . $listing->get_id() ),
                ) );
            ?>
            <b><?php echo $fee->fee_label; ?>:</b><br />
            <?php echo $subscription_details; ?><br />
            <a href="<?php echo esc_url( $cancel_url ); ?>" class="cancel-subscription"><?php _ex( 'Cancel recurring payment', 'manage recurring', 'WPBDM' ); ?></a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
