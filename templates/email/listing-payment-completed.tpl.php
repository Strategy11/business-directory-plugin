<?php
    _ex( 'A new listing payment has been completed. Payment details can be found below.', 'emails', 'business-directory-plugin' );
?>

----

<?php _ex( 'Payment ID', 'notify email', 'business-directory-plugin' ); ?>: <?php
    echo sprintf( '<a href="%s">%s</a>',
        esc_url(
            add_query_arg(
                array(
                    'page'       => 'wpbdp_admin_payments',
                    'wpbdp-view' => 'details',
                    'payment-id' => $payment->id,
                ),
                admin_url( 'admin.php?' )
            )
        ),
        $payment->id
    );?>


<?php if( ! empty( $payment_datails ) ) : ?>
    <?php _ex( 'Payment Details', 'notify email', 'business-directory-plugin' ); ?>: 
        <?php echo $payment_datails; ?>
<?php else: ?>
    <?php _ex( 'Amount', 'notify email', 'business-directory-plugin' ); ?>: <?php echo $plan->fee_amount; ?>
<?php endif; ?>


<?php _ex('Plan', 'notify email', 'business-directory-plugin' ); ?>: <?php 
    echo sprintf( '<a href="%s">%s</a>',
        esc_url(
            add_query_arg(
                array(
                    'page'       => 'wpbdp-admin-fees',
                    'wpbdp-view' => 'edit-fee',
                    'id'         => $plan->fee_id,
                ),
                admin_url( 'admin.php?' )
            )
        ),
        $plan->fee_label
    );?>


<?php _ex( 'Listing URL', 'notify email', 'business-directory-plugin' ); ?>: <?php echo $listing->is_published() ? $listing->get_permalink() : get_preview_post_link( $listing->get_id() ); ?>

<?php _ex( 'Listing admin URL', 'notify email', 'business-directory-plugin' ); ?>: <?php echo wpbdp_get_edit_post_link( $listing->get_id() ); ?>
