<?php
    wpbdp_admin_header(
		array(
            'id'      => 'admin-fees',
            'buttons' => array(
                __( 'Add New Plan', 'business-directory-plugin' ) => esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) )
            ),
            'echo' => true,
        )
    );
	?>
    <?php wpbdp_admin_notices(); ?>

    <?php if ( 'active' == $table->get_current_view() || 'all' == $table->get_current_view() ) : ?>
        <div class="fees-order">
            <form>
            <input type="hidden" name="action" value="wpbdp-admin-fees-set-order" />
            <?php wp_nonce_field( 'change fees order' ); ?>
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

			<a class="button-secondary fee-order-submit">
				<?php esc_html_e( 'Save front-end order', 'business-directory-plugin' ); ?>
			</a>

            <?php if ( 'custom' == $current_order['method'] ) : ?>
            <span><?php esc_html_e( 'Drag and drop to re-order plans.', 'business-directory-plugin' ); ?></span>
            <?php endif; ?>

            </form>
        </div>

        <br class="clear" />
        <?php endif; ?>

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
                <img src="<?php echo esc_url( WPBDP_ASSETS_URL ); ?>images/<?php echo esc_attr( $mod_info[1] ); ?>.png" class="gateway-logo">
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
