<h3><?php _ex( 'Manage Recurring Payments', 'manage recurring', 'WPBDM' ); ?></h3>

<table id="wpbdp-manage-recurring">
    <thead>
        <th class="listing-title"><?php _ex( 'Listing', 'manage recurring', 'WPBDM' ); ?></th>
        <th class="subscription-details"><?php _ex( 'Subscription / Fee Plan', 'manage subscriptions', 'WPBDM' ); ?></th>
    </thead>
    <tbody>
    <?php
    foreach ( $subscriptions as $listing_subscription ):
        $listing = $listing_subscription['listing'];
        $subscriptions = $listing_subscription['subscriptions'];
    ?>
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
        <?php foreach ( $subscriptions as $s ): ?>
            <b><?php echo $s->fee->label; ?>:</b><br />
            <?php printf( _x( '%s each %s days. Next renewal is on %s.', 'manage recurring', 'WPBDM' ),
                          wpbdp_currency_format( $s->fee->amount ),
                          '<i>' . $s->fee_days . '</i>',
                          '<i>' . date_i18n( get_option( 'date_format' ), strtotime( $s->expires_on ) ) . '</i>' ); ?><br />
            <a href="<?php echo esc_url( add_query_arg( 'cancel', $listing->get_renewal_hash( $s->term_id ) ) ); ?>" class="cancel-subscription"><?php _ex( 'Cancel recurring payment', 'manage recurring', 'WPBDM' ); ?></a>
        <?php endforeach; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
