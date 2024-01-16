<div id="wpbdp-renewal-page" class="wpbdp-renewal-page businessdirectory-renewal businessdirectory wpbdp-page">
	<h2><?php esc_html_e( 'Recurring Plan Management', 'business-directory-plugin' ); ?></h2>

	<p><?php esc_html_e( 'Because you are on a recurring plan you don\'t have to renew your listing right now as this will be handled automatically when renewal comes.', 'business-directory-plugin' ); ?></p>

	<h4><?php esc_html_e( 'Current Plan Details', 'business-directory-plugin' ); ?></h4>

	<dl class="recurring-fee-details">
		<dt><?php esc_html_e( 'Name:', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo esc_html( $plan->fee_label ); ?></dd>
		<dt><?php esc_html_e( 'Number of images:', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo esc_html( $plan->fee_images ); ?></dd>
		<dt><?php esc_html_e( 'Expiration date:', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $plan->expiration_date ) ) ); ?></dd>
	</dl>

	<?php
	if ( $show_cancel_subscription_button ) :

		$url = add_query_arg(
			array(
				'wpbdp_view' => 'manage_recurring',
				'action'     => 'cancel-subscription',
				'listing'    => $listing->get_id(),
				'nonce'      => wp_create_nonce( 'cancel-subscription-' . $listing->get_id() ),
			),
			wpbdp_url( 'main' )
		);

		?>
	<p><?php
	// translators: %1$s start link, %2$s closing link tag.
	printf(
		esc_html__( 'However, if you want to cancel your subscription you can do that on %1$sthe manage recurring payments page%2$s. When the renewal time comes you\'ll be able to change your settings again.', 'business-directory-plugin' ),
		'<a href="' . esc_url( $url ) . '">',
		'</a>'
	);
	?></p>

	<p><a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Go to Manage Recurring Payments page', 'business-directory-plugin' ); ?></a>
	<?php endif; ?>
</div>
