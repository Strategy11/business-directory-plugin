<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDP_Inbox_API extends WPBDP_Modules_API {

	use WPBDP_Who;

	public function __construct( $license = null ) {
		$this->set_cache_key();
	}

	/**
	 * @return void
	 */
	protected function set_cache_key() {
		$this->cache_key = 'wpbdp_inbox';
	}

	/**
	 * @return string
	 */
	protected function api_url() {
		return 'https://businessdirectoryplugin.com/wp-json/inbox/v1/message/';
	}

	/**
	 * @param array $message;
	 *
	 * @return bool
	 */
	public function should_include_message( $message ) {
		if ( empty( $message['key'] ) || ! $this->is_in_correct_timeframe( $message ) ) {
			return false;
		}

		return empty( $message['who'] ) || $this->matches_who( $message['who'] );
	}

	/**
	 * Check if a message is in the correct timeframe.
	 *
	 * @param array $message The message to check.
	 *
	 * @return bool
	 */
	private function is_in_correct_timeframe( $message ) {
		if ( ! empty( $message['expires'] ) && $message['expires'] + DAY_IN_SECONDS < time() ) {
			return false;
		}

		return empty( $message['starts'] ) || $message['starts'] < time();
	}
}
