<?php

class WPBDP__Installer__Installation_Error {

	private $exception;

	public function __construct( $exception ) {
		$this->exception = $exception;

		add_action( 'admin_notices', array( $this, 'installation_error_notice' ) );
	}

	public function installation_error_notice() {
		print '<div class="notice notice-error"><p>';
		print '<strong>' . esc_html__( 'Business Directory - Installation Failed', 'business-directory-plugin' ) . '</strong>';
		print '<br />';
		esc_html_e( 'Business Directory installation failed. An exception with following message was generated:', 'business-directory-plugin' );
		print '<br/><br/>';
		print '<i>' . esc_html( $this->exception->getMessage() ) . '</i>';
		print '<br /><br />';

		printf(
			/* translators: %1$s is the contact link, %2$s is the closing tag for the link */
			esc_html__( 'Please %1$scontact customer support%2$s.', 'business-directory-plugin' ),
			'<a href="https://businessdirectoryplugin.com/contact/">',
			'</a>'
		);

		print '</p></div>';
	}
}
