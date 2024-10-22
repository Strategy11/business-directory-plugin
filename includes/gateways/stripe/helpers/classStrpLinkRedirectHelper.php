<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since x.x
 */
class WPBDPStrpLinkRedirectHelper {

	/**
	 * @var string Either a Payment Intent ID (prefixed with pi_) or a Setup Intent ID (prefixed with seti_).
	 */
	private $stripe_id;

	/**
	 * @var string
	 */
	private $client_secret;

	/**
	 * @var int|null The entry ID associated with the payment being handled.
	 */
	private $entry_id;

	/**
	 * @param string $stripe_id either a Payment Intent ID (prefixed with pi_) or a Setup Intent ID (prefixed with seti_).
	 * @param string $client_secret
	 * @return void
	 */
	public function __construct( $stripe_id, $client_secret ) {
		$this->stripe_id     = $stripe_id;
		$this->client_secret = $client_secret;
	}

	/**
	 * Set the entry ID to pull referer data from.
	 * This is separate from the constructor as the entry ID isn't known for some error cases.
	 *
	 * @param string|int $entry_id
	 * @return void
	 */
	public function set_entry_id( $entry_id ) {
		$this->entry_id = absint( $entry_id );
	}

	/**
	 * @param string $error_code
	 * @return void
	 */
	public function handle_error( $error_code ) {
		$referer = wpbdp_get_server_value( 'HTTP_REFERER' );

		$this->add_intent_info_and_redirect(
			add_query_arg( array( 'wpbdp_link_error' => $error_code ), $referer )
		);
	}

	/**
	 * Redirect to handle the form's on success condition similar to how 3D secure is handled after being redirected.
	 *
	 * @param stdClass $entry
	 * @param string   $charge_id
	 * @return void
	 */
	public function handle_success( $entry, $charge_id ) {
		$form = ''; // TODO Form::getOne( $entry->form_id );

		// Let a stripe link success message get handled the same as a 3D secure redirect.
		// When it shows a message, it adds a &wpbdpstrp= param to the URL.
		$redirect            = WPBDPStrpAuth::return_url( compact( 'form', 'entry' ) );
		$is_message_redirect = false !== strpos( $redirect, 'wpbdpstrp=' );

		if ( $this->url_is_external( $redirect ) || ! $is_message_redirect ) {
			wp_redirect( $redirect );
			die();
		}

		// $redirect may not include the whole link to the form, breaking the redirect as iDEAL/Sofort have an additional redirect.
		$referer_url = false; // TODO.
		if ( is_string( $referer_url ) ) {
			$parts = explode( '?', $redirect, 2 );
			if ( 2 === count( $parts ) ) {
				$redirect = $parts[1];
			}
			$redirect = $referer_url . '?' . $redirect;
		}

		if ( $charge_id ) {
			$redirect .= '&charge=' . $charge_id;
		}

		$this->add_intent_info_and_redirect( $redirect );
	}

	/**
	 * Determine if a redirect URL is going to an external site or not.
	 *
	 * @param string $url
	 */
	private function url_is_external( $url ) {
		if ( false === strpos( $url, 'http' ) ) {
			return false;
		}

		$home_url      = home_url();
		$parsed        = parse_url( $home_url );
		if ( is_array( $parsed ) ) {
			$home_url = $parsed['scheme'] . '://' . $parsed['host'];
		}
		return 0 !== strpos( $url, $home_url );
	}

	/**
	 * Delete the referer meta as we'll no longer need it.
	 *
	 * @param int $row_id
	 * @return void
	 */
	private static function delete_temporary_referer_meta( $row_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wpbdp_item_metas', array( 'id' => $row_id ) );
	}

	/**
	 * Redirect, have WPBDPStrpAuth::maybe_show_message handle it similar to 3D secure.
	 *
	 * @param string $url
	 */
	private function add_intent_info_and_redirect( $url ) {
		if ( 0 === strpos( $this->stripe_id, 'pi_' ) ) {
			$url = add_query_arg( 'payment_intent', $this->stripe_id, $url );
			$url = add_query_arg( 'payment_intent_client_secret', $this->client_secret, $url );
		} else {
			$url = add_query_arg( 'setup_intent', $this->stripe_id, $url );
			$url = add_query_arg( 'setup_intent_client_secret', $this->client_secret, $url );
		}

		// iDeal redirects URLs are incorrectly encoded.
		// This str_replace reverts that encoding issue.
		$url = str_replace( '%3Fwpbdpstrp%3D', '&wpbdpstrp=', $url );

		wp_redirect( $url );
		die();
	}
}
