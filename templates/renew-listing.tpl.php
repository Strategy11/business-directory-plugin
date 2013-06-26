<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">

    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
    </div>

    <h2><?php _ex('Renew Listing', 'templates', 'WPBDM'); ?></h2>
    <p><?php printf( _x( 'You are about to renew your listing "%s" publication inside category "%s".',
                         'templates',
                         'WPBDM' ),
                     esc_html( $listing->post_title ),
                     esc_html( $category->name ) ); ?></p>
    <p><?php _ex( 'Please select a fee option or click "Do not renew my listing" to cancel your renewal.', 'WPBDM' ); ?></p>

    <form id="wpbdp-renewlisting-form" method="POST" action="">
    <?php echo wpbdp_render( 'parts/category-fee-selection', array( 'category' => $category, 'fees' => $fees  ), false ); ?>
    <input type="submit" class="submit" name="submit" value="<?php _ex('Proceed to checkout', 'templates', 'WPBDM'); ?>" />

    <div class="do-not-renew-listing">
        <div class="header"><?php _ex( 'Cancel Listing Renewal', 'renewal', 'WPBDM' ); ?></div>
        <input type="submit" class="submit" name="cancel-renewal" value="<?php _ex('Do not renew my listing', 'templates', 'WPBDM'); ?>" />
    </div>

    </form>

</div>