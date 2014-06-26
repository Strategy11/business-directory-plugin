<?php
    echo wpbdp_admin_header(null, 'admin-fees', wpbdp_get_option('payments-on') ? array(
        array(_x('Add New Listing Fee', 'fees admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfee'))),
    ) : null);
?>
    <?php wpbdp_admin_notices(); ?>

    <?php if (!wpbdp_get_option('payments-on')): ?>
        <p><?php _ex('Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to \'Turn on payments\' under \'General Payment Settings\'', 'fees admin', 'WPBDM'); ?></p>
    <?php else: ?>

        <?php $table->views(); ?>
        <?php $table->display(); ?>

        <hr />
        <p>
            <b><?php _ex('Installed Payment Gateway Modules', 'WPBDM'); ?></b>
            <ul>
                <?php if (wpbdp_payments_api()->has_gateway('googlecheckout')): ?>
                    <li style="background:url(<?php echo WPBDP_URL . 'admin/resources/check.png'; ?>) no-repeat left center; padding-left:30px;">
                        <?php _ex('Google Checkout', 'admin templates', 'WPBDM'); ?>
                    </li>
                <?php endif; ?>
                <?php if (wpbdp_payments_api()->has_gateway('paypal')): ?>
                    <li style="background:url(<?php echo WPBDP_URL . 'admin/resources/check.png'; ?>) no-repeat left center; padding-left:30px;">
                        <?php _ex('PayPal', 'admin templates', 'WPBDM'); ?>
                    </li>
                <?php endif; ?>
                <?php if (wpbdp_payments_api()->has_gateway('2checkout')): ?>
                    <li style="background:url(<?php echo WPBDP_URL . 'admin/resources/check.png'; ?>) no-repeat left center; padding-left:30px;">
                        <?php _ex('2Checkout', 'admin templates', 'WPBDM'); ?>
                    </li>
                <?php endif; ?>
            </ul></p>

            <?php if (!wpbdp_payments_api()->payments_possible()): ?>
            <p><?php _ex("It does not appear you have any of the payment gateway modules installed. You need to purchase a payment gateway module in order to charge a fee for listings. To purchase payment gateways use the buttons below or visit", 'admin templates', "WPBDM"); ?></p>
            <p><a href="http://businessdirectoryplugin.com/premium-modules/">http://businessdirectoryplugin.com/premium-modules/</a></p>            
            <?php endif; ?>

            <?php if (!wpbdp_payments_api()->has_gateway('2checkout') || !wpbdp_payments_api()->has_gateway('paypal')): ?>
            <div class="purchase-gateways cf">
                <?php if ( ! wpbdp_payments_api()->has_gateway( 'paypal' ) ): ?>
                <div class="gateway">
                    <?php echo str_replace( '<a>',
                                            '<a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/">',
                                            _x( 'You can buy the <a>PayPal</a> gateway module to add <a>PayPal</a> as a payment option for your users.',
                                                'admin templates',
                                                'WPBDM' ) ); ?><br />
                    <a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/" class="price">$49.99</a>
                </div>
                <?php endif; ?>

                <?php if ( ! wpbdp_payments_api()->has_gateway( '2checkout' ) ): ?>
                <div class="gateway">
                    <?php echo str_replace( '<a>',
                                            '<a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/">',
                                            _x( 'You can buy the <a>2Checkout</a> gateway module to add <a>2Checkout</a> as a payment option for your users.', 'WPBDM' ) ); ?><br />
                    <a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/" class="price">$49.99</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

    <?php endif; ?>

<?php echo wpbdp_admin_footer(); ?>

