<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">

    <h2><?php _ex('Renew Listing', 'templates', 'WPBDM'); ?></h2>

    <?php if ( isset( $payment ) && $payment ): ?>
        <form action="<?php echo esc_url( $payment->get_checkout_url() ); ?>" method="POST">
        <input type="submit" value="<?php _ex( 'Proceed to Checkout', 'renewal', 'WPBDM' ); ?>" />
        </form>
    <?php else: ?>
        <p><?php printf( _x( 'You are about to renew your listing "%s" publication inside category "%s".',
                             'templates',
                             'WPBDM' ),
                         esc_html( $listing->get_title() ),
                         esc_html( wpbdp_get_term_name( $category->id ) ) ); ?></p>
        <p><?php _ex( 'Please select a fee option or click "Do not renew my listing" to cancel your renewal.', 'WPBDM' ); ?></p>

        <form id="wpbdp-renewlisting-form" method="post" action="">
        <?php echo wpbdp_render( 'parts/category-fee-selection', array( 'category' => get_term( $category->id, WPBDP_CATEGORY_TAX ),
                                                                        'category_fees' => $fees,
                                                                        'current_fee' => $category->fee_id,
                                                                        'multiple_categories' => false  ), false ); ?>
        <input type="submit" class="submit" name="submit" value="<?php _ex('Continue', 'templates', 'WPBDM'); ?>" />

        <div class="do-not-renew-listing">
            <div class="header"><?php _ex( 'Cancel Listing Renewal', 'renewal', 'WPBDM' ); ?></div>
            <input type="submit" class="submit" name="cancel-renewal" value="<?php _ex('Do not renew my listing', 'templates', 'WPBDM'); ?>" />
        </div>

        </form>
    <?php endif; ?>

</div>
