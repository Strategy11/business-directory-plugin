
<form class="wpbdp-form" action="" method="post">
	<?php wp_nonce_field( 'request_access_keys' ); ?>
	<input type="hidden" name="redirect_to" value="<?php echo esc_attr( esc_url( $redirect_to ) ); ?>" />

	<div class="wpbdp-form-row wpbdp-form-textfield">
		<label for="wpbdp-listing-email"><?php esc_html_e( 'Enter your email address', 'business-directory-plugin' ); ?></label>
		<input type="text" name="email" id="wpbdp-listing-email">
	</div>

	<p><input class="submit" type="submit" value="<?php esc_attr_e( 'Continue', 'business-directory-plugin' ); ?>" /></p>
</form>
