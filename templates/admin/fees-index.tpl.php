<?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    wpbdp_admin_header(
        array( 
            'id'      => 'admin-fees',
            'buttons' => array(
                __( 'Add New Listing Fee', 'business-directory-plugin' ) => esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) )
            ),
            'echo' => true,
        )
    );
	?>
    <?php wpbdp_admin_notices(); ?>

    <?php if ( ! wpbdp_get_option( 'payments-on' ) ) : ?>
	<p class="howto">
		<?php esc_html_e( 'Payments are currently turned off.', 'business-directory-plugin' ); ?><br />
        <?php
            echo sprintf(
                /* translators: %1$s is a opening <a> tag, %2$s is a closing </a> tag. */
                esc_html__( 'To manage fees you need to go to the %1$sManage Options - Payment%2$s page and check the box next to \'Turn On Payments\' under \'Payment Settings\'.', 'business-directory-plugin' ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) ) . '">',
                '</a>'
            );
        ?>
    </p>
    <?php endif; ?>

    <?php if ( 'active' == $table->get_current_view() || 'all' == $table->get_current_view() ) : ?>
        <div class="fees-order">
            <form>
            <input type="hidden" name="action" value="wpbdp-admin-fees-set-order" />
            <?php wp_nonce_field( 'change fees order' ); ?>
            <b><?php esc_html_e( 'Order fees on the frontend by:', 'business-directory-plugin' ); ?></b><br />
            <select name="fee_order[method]">
            <?php foreach ( $order_options as $k => $l ) : ?>
            <option value="<?php echo esc_attr( $k ); ?>" <?php echo $k == $current_order['method'] ? 'selected="selected"' : ''; ?> ><?php echo esc_html( $l ); ?></option>
            <?php endforeach; ?>
            </select>

            <select name="fee_order[order]" style="<?php echo ( 'custom' == $current_order['method'] ) ? 'display: none;' : ''; ?>">
            <?php
            foreach ( array(
				'asc'  => __( '↑ Ascending', 'business-directory-plugin' ),
				'desc' => __( '↓ Descending', 'business-directory-plugin' ),
			) as $o => $l ) :
				?>
                <option value="<?php echo esc_attr( $o ); ?>" <?php echo $o == $current_order['order'] ? 'selected="selected"' : ''; ?> ><?php echo esc_html( $l ); ?></option>
            <?php endforeach; ?>
            </select>

            <?php if ( 'custom' == $current_order['method'] ) : ?>
            <span><?php esc_html_e( 'Drag and drop to re-order fees.', 'business-directory-plugin' ); ?></span>
            <?php endif; ?>

            </form>
        </div>

        <br class="clear" />
        <?php endif; ?>

		<p class="howto">
        <?php
        switch ( $table->get_current_view() ) :
			case 'active':
				?>
                <?php
                printf(
                    /* translators: %1$s is directory payment mode (Free or Paid), %2$s is a opening <a> tag, %3$s is a closing </a> tag. */
                    esc_html__( 'These are all of the fee plans displayed to the user when they place a listing. Your current mode of "%1$s" restricts what you see here. Those on the %2$sNot Available%3$s filter will become active when you change the payment mode.', 'business-directory-plugin' ),
                    wpbdp_payments_possible() ? esc_html__( 'Paid', 'business-directory-plugin' ) : esc_html__( 'Free', 'business-directory-plugin' ),
                    '<a href="' . esc_url( add_query_arg( 'fee_status', 'unavailable', admin_url( 'admin.php?page=wpbdp-admin-fees' ) ) ) . '">',
                    '</a>'
                );
				?>
            <?php break; ?>
            <?php
            case 'unavailable':
				?>
                <?php
                printf(
                    /* translators: %s is directory payment mode (Free or Paid) */
                    esc_html__( 'These are all of the fee plans that aren\'t available because you\'re in "%s" mode. Those on the Active filter will become Not Available when you change the payment mode.', 'business-directory-plugin' ),
                    wpbdp_payments_possible() ? esc_html__( 'Paid', 'business-directory-plugin' ) : esc_html__( 'Free', 'business-directory-plugin' )
                );
				?>
            <?php break; ?>
            <?php
            case 'disabled':
				?>
                <?php
                esc_html_e( 'These fee plans were disabled by the admin and will not show to the end user regardless of mode until you enable them.', 'business-directory-plugin' );
				?>
            <?php break; ?>
            <?php
			default:
				?>
				<?php
				echo sprintf(
                    /* translators: %1$s is directory payment mode (Free or Paid), %2$s is a opening <a> tag, %3$s is a closing </a> tag. */
                    esc_html__( 'These are all of the fee plans you have configured. Not all of them are available for the current mode (currently set to "%1$s"). To see the fee plans for this mode click %2$sActive%3$s.', 'business-directory-plugin' ),
                    esc_html( wpbdp_payments_possible() ? __( 'Paid', 'business-directory-plugin' ) : __( 'Free', 'business-directory-plugin' ) ),
                    '<a href="' . esc_url( add_query_arg( 'fee_status', 'active', admin_url( 'admin.php?page=wpbdp-admin-fees' ) ) ) . '">',
                    '</a>'
                );
				?>
            <?php break; ?>
        <?php endswitch; ?>
		</p>


        <?php $table->views(); ?>
        <?php $table->display(); ?>

        <hr />
        <?php
        $modules = array(
            array( 'stripe', 'stripe-payment-module', 'Stripe' ),
            array( 'paypal', 'paypal-gateway-module', 'PayPal' ),
            array( 'payfast', 'payfast-payment-module', 'PayFast' ),
        );

        global $wpbdp;
        ?>

        <div class="purchase-gateways cf postbox">
            <div class="inside">
                <h2 class="aligncenter">
                    <?php
                    if ( ! wpbdp_payments_possible() ) {
                        esc_html_e( 'Set up a payment gateway to charge a fee for listings', 'business-directory-plugin' );
                    } else {
                        esc_html_e( 'Add a payment gateway to increase conversion rates', 'business-directory-plugin' );
                    }
                    ?>
                </h2>
            </div>
        <?php
		$modules_obj = wpbdp()->modules;
        foreach ( $modules as $mod_info ) :
            if ( $modules_obj->is_loaded( $mod_info[0] ) ) {
                continue;
            }
			?>
        <div class="gateway inside <?php echo esc_attr( $mod_info[0] ); ?> <?php echo $modules_obj->is_loaded( $mod_info[0] ) ? 'installed' : ''; ?>">
            <a href="https://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?ref=wp" target="_blank" rel="noopener">
                <img src="<?php echo esc_url( WPBDP_URL ); ?>assets/images/<?php echo esc_attr( $mod_info[1] ); ?>.png" class="gateway-logo">
            </a><br/>
			<?php
			echo sprintf(
                // translators: %s: payment gateway name */
                esc_html__( 'Add the %s gateway as a payment option.', 'business-directory-plugin' ),
                '<a href="https://businessdirectoryplugin.com/downloads/' . esc_attr( $mod_info[1] ) . '/?utm_campaign=liteplugin" target="_blank" rel="noopener">' . esc_html( $mod_info[2] ) . '</a>'
            );
			?>
            <p>
                <a href="https://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?utm_campaign=liteplugin" target="_blank" rel="noopener" class="button-primary">
                    <?php esc_html_e( 'Upgrade', 'business-directory-plugin' ); ?>
                </a>
            </p>
        </div>
        <?php endforeach; ?>
        <?php if ( ! wpbdp_payments_possible() ) : ?>
        <div class="gateway">
            <h3>Authorize.net</h3>
            <?php esc_html_e( 'Set up Authorize.net as a payment option.', 'business-directory-plugin' ); ?>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) ); ?>" class="button-primary">
                    <?php esc_html_e( 'Set Up', 'business-directory-plugin' ); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
        </div>

<?php wpbdp_admin_footer( 'echo' ); ?>
