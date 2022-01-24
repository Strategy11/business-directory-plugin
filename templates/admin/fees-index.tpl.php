<?php
WPBDP_Admin_Pages::show_tabs(
	array(
		'id'      => 'admin-fees',
		'sub'     => __( 'Plans', 'business-directory-plugin' ),
		'buttons' => array(
			__( 'Add New Plan', 'business-directory-plugin' ) => esc_url( admin_url( 'admin.php?page=wpbdp-admin-fees&wpbdp-view=add-fee' ) )
		),
	)
);
?>

	<p class="howto">
		<?php if ( ! wpbdp_get_option( 'payments-on' ) ) : ?>
			<span class="title"><?php esc_html_e( 'Payments are currently turned off.', 'business-directory-plugin' ); ?></span>
			<?php
				echo sprintf(
					/* translators: %1$s is a opening <a> tag, %2$s is a closing </a> tag. */
					esc_html__( 'To manage fees you need to go to the %1$sManage Options - Payment%2$s page and check the box next to \'Turn On Payments\' under \'Payment Settings\'.', 'business-directory-plugin' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) ) . '">',
					'</a>'
				);
			?>
		<?php endif; ?>
		<br/>
		<?php
		printf(
			/* translators: %1$s is directory payment mode (Free or Paid) */
			esc_html__( 'All plans may not be available in "%1$s" mode.', 'business-directory-plugin' ),
			esc_html( wpbdp_payments_possible() ? __( 'Paid', 'business-directory-plugin' ) : __( 'Free', 'business-directory-plugin' ) )
		);
		?>
    </p>

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
			<a class="button-secondary fee-order-submit"><?php esc_html_e( 'Order on the frontend', 'business-directory-plugin' ); ?></a>
            <?php if ( 'custom' == $current_order['method'] ) : ?>
				<span><?php esc_html_e( 'Drag and drop to re-order plans.', 'business-directory-plugin' ); ?></span>
            <?php endif; ?>

            </form>
        </div>

        <br class="clear" />
        <?php endif; ?>


        <?php $table->views(); ?>
        <?php $table->display(); ?>

		<?php require_once WPBDP_PATH . 'includes/admin/views/fields/delete.php'; ?>

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
                <h2>
                    <?php
                    if ( ! wpbdp_payments_possible() ) {
                        esc_html_e( 'Set up a payment gateway to charge a fee for listings', 'business-directory-plugin' );
                    } else {
                        esc_html_e( 'Add a payment gateway to increase conversion rates', 'business-directory-plugin' );
                    }
                    ?>
                </h2>
            </div>
		<div class="wpbdp-fee-gateway-list wpbdp-grid">
        <?php
		$modules_obj = wpbdp()->modules;
        foreach ( $modules as $mod_info ) :
            if ( $modules_obj->is_loaded( $mod_info[0] ) ) {
                continue;
            }
			?>
        <div class="gateway inside wpbdp-col-3 <?php echo esc_attr( $mod_info[0] ); ?> <?php echo $modules_obj->is_loaded( $mod_info[0] ) ? 'installed' : ''; ?>">
            <a class="gateway-title" href="https://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?ref=wp" target="_blank" rel="noopener">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL ); ?>images/modules/<?php echo esc_attr( $mod_info[1] ); ?>.svg" class="gateway-logo">
            </a>
			<div class="gateway-description">
				<?php
				echo sprintf(
					// translators: %s: payment gateway name */
					esc_html__( 'Add the %s gateway as a payment option.', 'business-directory-plugin' ),
					esc_html( $mod_info[2] )
				);
				?>
			</div>
            <p class="gateway-footer">
                <a href="https://businessdirectoryplugin.com/downloads/<?php echo esc_attr( $mod_info[1] ); ?>/?utm_campaign=liteplugin" target="_blank" rel="noopener" class="button-primary">
                    <?php esc_html_e( 'Upgrade', 'business-directory-plugin' ); ?>
                </a>
            </p>
        </div>
        <?php endforeach; ?>
        <?php if ( ! wpbdp_payments_possible() ) : ?>
        <div class="gateway inside wpbdp-col-3">
			<div class="gateway-title">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL ); ?>images/modules/authorize-net-payment-module.svg" class="gateway-logo">
			</div>
			<div class="gateway-description">
				<?php esc_html_e( 'Set up Authorize.net as a payment option.', 'business-directory-plugin' ); ?>
			</div>
            <p class="gateway-footer">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=payment' ) ); ?>" class="button-primary">
                    <?php esc_html_e( 'Set Up', 'business-directory-plugin' ); ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
		</div>
        </div>

<?php WPBDP_Admin_Pages::show_tabs_footer( array( 'sub' => true ) ); ?>
