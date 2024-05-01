<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class WPBDP_Modules_API {

	protected $license       = '';
	protected $cache_key     = '';
	protected $cache_timeout = '+6 hours';

	/**
	 * The number of days an add-on is new.
	 *
	 * @var int
	 */
	protected $new_days = 90;

	/**
	 * @since 5.10
	 */
	public function __construct( $license = null ) {
		$this->set_license( $license );
		$this->set_cache_key();
	}

	/**
	 * @since 5.10
	 *
	 * @return void
	 */
	private function set_license( $license ) {
		if ( $license === null ) {
			$pro_license = $this->get_pro_license();
			if ( ! empty( $pro_license ) ) {
				$license = $pro_license;
			}
		}
		$this->license = $license;
	}

	/**
	 * @since 5.10
	 *
	 * @return string
	 */
	public function get_license() {
		return $this->license;
	}

	/**
	 * @since 5.10
	 *
	 * @return void
	 */
	protected function set_cache_key() {
		$this->cache_key = 'wpbdp_addons_l' . ( empty( $this->license ) ? '' : md5( $this->license ) );
	}

	/**
	 * @since 5.10
	 *
	 * @return string
	 */
	public function get_cache_key() {
		return $this->cache_key;
	}

	/**
	 * @since 5.10
	 *
	 * @return array
	 */
	public function get_api_info() {
		$url = $this->api_url();
		if ( ! empty( $this->license ) ) {
			$url .= '?l=' . urlencode( base64_encode( $this->license ) );
		}

		$addons = $this->get_cached();
		if ( is_array( $addons ) ) {
			return $addons;
		}

		if ( $this->is_running() ) {
			// If there's no saved cache, we'll need to wait for the current request to finish.
			return array();
		}

		$this->set_running();

		// We need to know the version number to allow different downloads.
		$agent = 'Business Directory/' . WPBDP_VERSION;

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 25,
				'user-agent' => $agent . '; ' . get_bloginfo( 'url' ),
			)
		);

		/* @phpstan-ignore-next-line */
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$addons = $response['body'] ? json_decode( $response['body'], true ) : array();
		}

		if ( ! is_array( $addons ) ) {
			$addons = array();
		}

		$addons['response_code'] = wp_remote_retrieve_response_code( $response );

		foreach ( $addons as $k => $addon ) {
			if ( ! is_array( $addon ) ) {
				continue;
			}

			if ( isset( $addon['categories'] ) ) {
				$cats = array_intersect( $this->skip_categories(), $addon['categories'] );
				if ( ! empty( $cats ) ) {
					unset( $addons[ $k ] );
					continue;
				}
			}

			if ( ! array_key_exists( 'is_new', $addon ) && array_key_exists( 'released', $addon ) ) {
				$addons[ $k ]['is_new'] = $this->is_new( $addon );
			}
		}

		$this->set_cached( $addons );
		$this->done_running();

		return $addons;
	}

	/**
	 * Prevent multiple requests from running at the same time.
	 *
	 * @since 6.4.2
	 *
	 * @return bool
	 */
	protected function is_running() {
		if ( $this->run_as_multisite() ) {
			return get_site_transient( $this->transient_key() );
		}
		return get_transient( $this->transient_key() );
	}

	/**
	 * @since 6.4.2
	 *
	 * @return void
	 */
	protected function set_running() {
		$expires = 2 * MINUTE_IN_SECONDS;
		if ( $this->run_as_multisite() ) {
			set_site_transient( $this->transient_key(), true, $expires );
			return;
		}

		set_transient( $this->transient_key(), true, $expires );
	}

	/**
	 * @since 6.4.2
	 *
	 * @return void
	 */
	protected function done_running() {
		if ( $this->run_as_multisite() ) {
			delete_site_transient( $this->transient_key() );
		}
		delete_transient( $this->transient_key() );
	}

	/**
	 * Only allow one site in the network to make the api request at a time.
	 * If there is a license for the request, run individually.
	 *
	 * @since 6.4.2
	 *
	 * @return bool
	 */
	protected function run_as_multisite() {
		return is_multisite() && empty( $this->license );
	}

	/**
	 * @since 6.4.2
	 *
	 * @return string
	 */
	protected function transient_key() {
		return strtolower( __CLASS__ ) . '_request_lock';
	}

	/**
	 * @since 5.10
	 *
	 * @return string
	 */
	protected function api_url() {
		if ( empty( $this->license ) ) {
			return 'https://plapi.businessdirectoryplugin.com/list/';
		}

		return 'https://businessdirectoryplugin.com/wp-json/s11edd/v1/updates/';
	}

	/**
	 * @since 5.10
	 *
	 * @return string[]
	 */
	protected function skip_categories() {
		return array();
	}

	/**
	 * @since 5.10
	 */
	public function get_pro_license() {
		$license = wpbdp_get_option( 'license-key-module-business-directory-premium' );
		if ( $license ) {
			$this->set_license( $license );
		}

		return $license;
	}

	/**
	 * @since 5.10
	 *
	 * @return array|bool
	 */
	protected function get_cached() {
		$cache = $this->get_cached_option();
		if ( empty( $cache ) ) {
			return false;
		}

		// If the api call is running, we can use the expired cache.
		if ( ! $this->is_running() ) {
			if ( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
				// Cache is expired.
				return false;
			}

			$version     = WPBDP_VERSION;
			$for_current = isset( $cache['version'] ) && $cache['version'] == $version;
			if ( ! $for_current ) {
				// Force a new check.
				return false;
			}
		}

		$values = json_decode( $cache['value'], true );

		return $values;
	}

	/**
	 * Get the cache for the network if multisite.
	 *
	 * @since 6.4.2
	 *
	 * @return mixed
	 */
	protected function get_cached_option() {
		if ( is_multisite() ) {
			$cached = get_site_option( $this->cache_key );
			if ( $cached ) {
				return $cached;
			}
		}

		return get_option( $this->cache_key );
	}

	/**
	 * @since 5.10
	 *
	 * @param array $addons
	 *
	 * @return void
	 */
	protected function set_cached( $addons ) {
		$data = array(
			'timeout' => strtotime( $this->get_cache_timeout( $addons ), time() ),
			'value'   => wp_json_encode( $addons ),
			'version' => WPBDP_VERSION,
		);

		if ( is_multisite() ) {
			update_site_option( $this->cache_key, $data );
		} else {
			update_option( $this->cache_key, $data, 'no' );
		}
	}

	/**
	 * If the last check was a a rate limit, we'll need to check again sooner.
	 *
	 * @since 6.4.2
	 *
	 * @param array $addons
	 *
	 * @return string
	 */
	protected function get_cache_timeout( $addons ) {
		$timeout = $this->cache_timeout;
		if ( isset( $addons['response_code'] ) && 429 === $addons['response_code'] ) {
			$timeout = '+5 minutes';
		}
		return $timeout;
	}

	/**
	 * @since 5.10
	 *
	 * @return void
	 */
	public function reset_cached() {
		if ( is_multisite() ) {
			delete_site_option( $this->cache_key );
		} else {
			delete_option( $this->cache_key );
		}
		delete_option( 'wpbdp_updates' );
		$this->done_running();
	}

	/**
	 * @since 5.10
	 *
	 * @return array
	 */
	public function error_for_license() {
		$errors = array();
		if ( ! empty( $this->license ) ) {
			$errors = $this->get_error_from_response();
		}

		return $errors;
	}

	/**
	 * @since 5.10
	 *
	 * @return array
	 */
	public function get_error_from_response( $addons = array() ) {
		if ( empty( $addons ) ) {
			$addons = $this->get_api_info();
		}
		$errors = array();
		if ( isset( $addons['error'] ) ) {
			if ( is_string( $addons['error'] ) ) {
				$errors[] = $addons['error'];
			} elseif ( ! empty( $addons['error']['message'] ) ) {
				$errors[] = $addons['error']['message'];
			}

			do_action( 'wpbdp_license_error', $addons['error'] );
		}

		return $errors;
	}

	/**
	 * Check if an add-on is new.
	 *
	 * @since 6.4.2
	 *
	 * @param array $addon
	 *
	 * @return bool
	 */
	protected function is_new( $addon ) {
		return strtotime( $addon['released'] ) > strtotime( '-' . $this->new_days . ' days' );
	}
}
