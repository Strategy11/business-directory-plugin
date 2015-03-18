<?php
    echo wpbdp_admin_header(null, 'admin-fees', wpbdp_get_option('payments-on') ? array(
        array(_x('Add New Listing Fee', 'fees admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfee'))),
    ) : null);
?>
    <?php wpbdp_admin_notices(); ?>

    <?php if (!wpbdp_get_option('payments-on')): ?>
    <div class="wpbdp-note"><p>
    <?php _ex('Payments are currently turned off.', 'fees admin', 'WPBDM' ); ?><br />
    <?php echo str_replace( '<a>',
                            '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=payment' ) . '">',
                            _x( 'To manage fees you need to go to the <a>Manage Options - Payment</a> page and check the box next to \'Turn On Payments\' under \'Payment Settings\'.',
                                'fees admin',
                                'WPBDM' ) ); ?></p>
    </div>
    <?php else: ?>
        <div class="fees-order">
            <form>
            <input type="hidden" name="action" value="wpbdp-admin-fees-set-order" />
            <?php wp_nonce_field( 'change fees order' ); ?>
            <b><?php _ex( 'Order fees on the frontend by:', 'fees admin', 'WPBDM' ); ?></b><br />
            <select name="fee_order[method]">
            <?php foreach ( $order_options as $k => $l ): ?>
            <option value="<?php echo $k; ?>" <?php echo ( $k == $current_order['method'] ) ? 'selected="selected"' : ''; ?> ><?php echo $l; ?></option>
            <?php endforeach; ?>
            </select>

            <select name="fee_order[order]" style="<?php echo ( 'custom' == $current_order['method'] ) ? 'display: none;' : ''; ?>">
            <?php foreach ( array( 'asc' => _x( '↑ Ascending', 'fees admin', 'WPBDM' ), 'desc' => _x( '↓ Descending', 'fees admin', 'WPBDM' ) ) as $o => $l ): ?>
                <option value="<?php echo $o; ?>" <?php echo ( $o == $current_order['order'] ) ? 'selected="selected"' : ''; ?> ><?php echo $l; ?></option>
            <?php endforeach; ?>
            </select>

            <?php if ( 'custom' == $current_order['method'] ): ?>
            <span><?php _ex( 'Drag and drop to re-order fees.', 'fees admin', 'WPBDM' ); ?></span>
            <?php endif; ?>

            </form>
        </div>

        <?php $table->views(); ?>
        <?php $table->display(); ?>

        <hr />
        <?php
        $modules = array(
            array( 'paypal-gateway-module', _x( 'PayPal Gateway Module', 'admin sidebar', 'WPBDM' ), 'PayPal' ),
            array( '2checkout-gateway-module', _x( '2Checkout Gateway Module', 'admin sidebar', 'WPBDM' ), '2Checkout' ),
            array( 'payfast-payment-module', _x( 'PayFast Payment Module', 'admin sidebar', 'WPBDM' ), 'PayFast' ),
            array( 'stripe-payment-module', _x( 'Stripe Payment Module', 'admin sidebar', 'WPBDM' ), 'Stripe' )
        );

        global $wpbdp;
        ?>
        <?php if ( ! $wpbdp->payments->payments_possible() ): ?>
        <p>
        <?php
        echo str_replace( '<a>',
                          '<a href="' . admin_url( 'admin.php' ) . '?page=wpbdp_admin_settings&groupid=payment">',
                          sprintf ( _x( 'It does not appear you have any of the payment gateway modules enabled. Either <a>enable the default Authorize.net gateway</a> with your account info, or purchase a different payment gateway module in order to charge a fee for listings. To purchase additional payment gateways use the buttons below or visit %s.','admin templates', 'WPBDM' ),
                                    '<a href="http://businessdirectoryplugin.com/premium-modules/" target="_blank">http://businessdirectoryplugin.com/premium-modules/</a>' ) );
        ?></p>
        <?php endif; ?>

        <div class="purchase-gateways cf">
        <?php
        foreach ( $modules as $mod_info ):
        ?>
        <div class="gateway <?php echo $mod_info[0]; ?> <?php echo $wpbdp->has_module( $mod_info[0] ) ? 'installed' : ''; ?>">
            <a href="http://businessdirectoryplugin.com/downloads/<?php echo $mod_info[0]; ?>/?ref=wp" target="_blank">
                <img src="<?php echo WPBDP_URL; ?>admin/resources/<?php echo $mod_info[0]; ?>.png" class="gateway-logo"><br />
                <a href="http://">
            </a>
            <?php if ( $wpbdp->has_module( $mod_info[0] ) ): ?>
                <a href="http://businessdirectoryplugin.com/downloads/<?php echo $mod_info[0]; ?>/?ref=wp"><?php echo $mod_info[1]; ?></a><br />
                <span class="check-mark">✓</span> <?php _ex( 'Already installed.', 'admin templates', 'WPBDM' ); ?>
            <?php else: ?>
            <?php echo str_replace(
                '<a>',
                '<a href="http://businessdirectoryplugin.com/downloads/' . $mod_info[0] . '/?ref=wp" target="_blank">',
                sprintf( _x( 'You can buy the <a>%s</a> to add <a>%s</a> as a payment option for your users.',
                             'admin templates',
                             'WPBDM' ), $mod_info[1], $mod_info[2] )
            ); ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>

    <?php endif; ?>

<?php echo wpbdp_admin_footer(); ?>
