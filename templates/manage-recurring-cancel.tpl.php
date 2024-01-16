<h3><?php esc_html_e( 'Manage Recurring Payments - Cancel Subscription', 'business-directory-plugin' ); ?></h3>

<div id="wpbdp-manage-recurring-cancel">
	<h4><?php esc_html_e( 'Plan Details', 'business-directory-plugin' ); ?></h4>

	<dl>
		<dt>
			<?php esc_html_e( 'Name:', 'business-directory-plugin' ); ?>
		</dt>
		<dd>
			<?php echo esc_html( $plan->fee_label ); ?>
		</dd>
		<dt>
			<?php esc_html_e( 'Cost:', 'business-directory-plugin' ); ?>
		</dt>
		<dd>
			<?php
			printf(
				/* translators: %1$s: amount, %2$s: number of days */
				esc_html__( '%1$s every %2$s days.', 'business-directory-plugin' ),
				esc_html( wpbdp_currency_format( $plan->fee_price ) ),
				esc_html( $plan->fee_days )
			);
			?>
		</dd>
		<!--<dt>
			<?php esc_html_e( 'Number of images:', 'business-directory-plugin' ); ?>
		</dt>
		<dd>
			<?php echo esc_html( $plan->fee_images ); ?>
		</dd>-->
		<dt>
			<?php esc_html_e( 'Expires on:', 'business-directory-plugin' ); ?>
		</dt>
		<dd>
			<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $plan->expiration_date ) ) ); ?>
		</dd>
	</dl>

	<form class="wpbdp-cancel-subscription-form" action="" method="post">
		<p><?php echo esc_html_x( 'Are you sure you want to cancel this subscription?', 'manage recurring', 'business-directory-plugin' ); ?></p>
		<p>
			<input class="button button-primary" type="submit" name="cancel-subscription" value="<?php echo esc_attr( _x( 'Yes, cancel subscription', 'manage recurring', 'business-directory-plugin' ) ); ?>" />
			<input class="button" type="submit" name="return-to-subscriptions" value="<?php echo esc_attr( _x( 'No, go back to my subscriptions', 'manage recurring', 'business-directory-plugin' ) ); ?>" />
		</p>
	</form>
</div>
