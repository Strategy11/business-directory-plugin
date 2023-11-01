<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

//WPBDPStrpAppHelper::fee_education( 'settings' );
?>
<div style="margin-bottom:15px;" data-test-mode="<?php echo $test ? 1 : 0; ?>">
	<span style="margin-bottom: 5px;min-width:40px;display: inline-block;">
		<b style="color: var(--medium-grey);"><?php echo esc_html( $title ); ?></b>
	</span>
	<?php if ( $account_id ) { ?>
		<?php if ( $connected ) { ?>
			<?php // icon_by_class( 'wpbdpfont wpbdp_checkmark_icon wpbdp-yes' ); ?>
			<?php esc_html_e( 'Connected', 'business-directory-plugin' ); ?>
			&nbsp; &nbsp;
		<?php } else { ?>
			<strong>
				<span class="wpbdp-nope" style="color:#B94A48">&#10008;</span>
				<?php esc_html_e( 'Not connected!', 'business-directory-plugin' ); ?>
			</strong>
			<br/>
			<a id="wpbdp_reauth_stripe" class="button-primary wpbdp-button-primary" href="#">
				<?php WPBDPStrpConnectHelper::stripe_icon(); ?> &nbsp;
				<?php esc_html_e( 'Finish Stripe Setup', 'business-directory-plugin' ); ?>
			</a>
			or
		<?php } ?>
		<a id="wpbdp_disconnect_stripe" href="#" style="font-size:13px"><?php esc_html_e( 'Disconnect', 'business-directory-plugin' ); ?></a>
	<?php } else { ?>
		<br/>
		<a id="wpbdp_connect_with_oauth" class="button-primary wpbdp-button-primary">
			<?php WPBDPStrpConnectHelper::stripe_icon(); ?> &nbsp;
			<?php esc_html_e( 'Connect to Stripe', 'business-directory-plugin' ); ?>
		</a>
	<?php } ?>
</div>
