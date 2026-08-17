<?php

class WPBDP__Authenticated_Listing_View extends WPBDP__View {

	protected $listing;

	protected function authenticate() {
		if ( ! $this->listing ) {
			die();
		}

		if ( wpbdp_user_is_admin() ) {
			return true;
		}

		if ( is_user_logged_in() && $this->listing->owned_by_user() ) {
			return true;
		}

		if ( class_exists( 'WPBDP__Views__Submit_Listing', false ) && $this instanceof WPBDP__Views__Submit_Listing && empty( $this->editing ) && ! wpbdp_get_option( 'require-login' ) ) {
			$status = get_post_status( $this->listing->get_id() );
			if ( ! $status || 'auto-draft' === $status ) {
				return true;
			}
		}

		return wpbdp_request_has_valid_access_key( $this->listing->get_id() );
	}
}
