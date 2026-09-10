<?php
if ( ! defined( 'WPBDP_VERSION' ) ) {
	die; // This page should not be called directly.
}

/**
 * @package admin
 */

if ( ! class_exists( 'WPBDP_SiteTracking' ) ) {

	/**
	 * Class used for anonymously tracking of users setups.
	 *
	 * @since 3.2
	 */
	class WPBDP_SiteTracking {

		public function site_hash() {
			$hash = get_option( 'wpbdp-site_tracking_hash', '' );

			if ( ! $hash ) {
				$hash = sha1( uniqid() . site_url() );
				update_option( 'wpbdp-site_tracking_hash', $hash, 'no' );
			}

			return $hash;
		}

		public function tracking() {
			_deprecated_function( __METHOD__, 'x.x' );
		}

		/**
		 * @since 3.5.2
		 */
		public function track_uninstall( $data = array() ) {
			_deprecated_function( __METHOD__, 'x.x' );
		}

		public static function handle_ajax_response() {
			_deprecated_function( __METHOD__, 'x.x' );
		}

		public static function request_js() {
			_deprecated_function( __METHOD__, 'x.x' );
		}
	}
}
