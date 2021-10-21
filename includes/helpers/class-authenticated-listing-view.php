<?php

class WPBDP__Authenticated_Listing_View extends WPBDP__View {

    /**
     * Load resources required for the view
     *
     * @since x.x
     */
	public function enqueue_resources() {
		// CSS used for plan buttons on the listing page only.
		$custom_css = "
		.wpbdp-plan-info-box .wpbdp-plan-price input[type=radio]+ label span:before{
			content: '" . esc_attr__( 'Select', 'business-directory-plugin' ) . "';
		}
		.wpbdp-plan-info-box .wpbdp-plan-price input[type=radio]:checked + label span:before{
			content: '" . esc_attr__( 'Selected', 'business-directory-plugin' ) . "';
		}";
		wp_add_inline_style( 'wpbdp-base-css', $custom_css );
		
		$this->enqueue_custom_resources();
	}

    /**
     * @since x.x
     */
	public function enqueue_custom_resources() {
		// Load custom resources in classes that extend this class.
	 	// Defaults to empty function if not overriden in the child class.
	}

	protected function authenticate() {
		if ( ! $this->listing )
			die();

		if ( current_user_can( 'administrator' ) ) {
			return true;
		}

		if ( is_user_logged_in() && $this->listing->owned_by_user() ) {
			return true;
		}

		if ( 'WPBDP__Views__Submit_Listing' == get_class( $this ) && empty( $this->editing ) && ! wpbdp_get_option( 'require-login' ) )
			return true;

		//if ( is_user_logged_in() && ( $this->listing->get_auth ) )

		$key_hash = wpbdp_get_var( array( 'param' => 'access_key_hash' ), 'request' );

		if ( wpbdp_get_option( 'enable-key-access' ) && $key_hash )
			return $this->listing->validate_access_key_hash( $key_hash );

		return false;
	}

}
