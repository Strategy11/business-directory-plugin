<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles the Sales API.
 *
 * @since x.x
 */
class WPBDP_Sales_API extends WPBDP_Modules_API {

	use WPBDP_Who;

	/**
	 * @var array|false
	 */
	private static $best_sale;

	public function __construct( $license = null ) {
		$this->set_cache_key();
	}

	/**
	 * @return void
	 */
	protected function set_cache_key() {
		$this->cache_key = 'wpbdp_sales';
	}

	/**
	 * @return string
	 */
	protected function api_url() {
		return 'https://businessdirectoryplugin.com/wp-json/s11-sales/v1/list/';
	}

	/**
	 * Check if a sale should be included.
	 *
	 * @since x.x
	 *
	 * @param array $sale The sale to check.
	 * @return bool
	 */
	public function should_include_sale( $sale ) {
		if ( empty( $sale['key'] ) || ! $this->is_in_correct_timeframe( $sale ) ) {
			return false;
		}

		return empty( $sale['who'] ) || $this->matches_who( $sale['who'] );
	}

	/**
	 * Check if a sale is in the correct timeframe.
	 *
	 * @param array $sale The sale to check.
	 *
	 * @return bool
	 */
	private function is_in_correct_timeframe( $sale ) {
		if ( ! empty( $sale['expires'] ) && $sale['expires'] + DAY_IN_SECONDS < time() ) {
			return false;
		}

		return empty( $sale['starts'] ) || $sale['starts'] < time();
	}

	/**
	 * Get the value of the best sale.
	 *
	 * @since x.x
	 *
	 * @param string $key
	 * @return string|null Null if the key is not found or the value is not truthy.
	 */
	public static function get_best_sale_value( $key ) {
		$best_sale = self::get_best_sale();
		if ( is_array( $best_sale ) && ! empty( $best_sale[ $key ] ) ) {
			return $best_sale[ $key ];
		}
		return null;
	}

	/**
	 * Get the CTA link for the best sale.
	 * This functions the same as get_best_sale_value but also
	 * adds missing UTM params if they do not already exist.
	 *
	 * @since x.x
	 *
	 * @param string $key
	 * @param string $utm_medium The utm_medium param to add if one does not already exist.
	 * @return string
	 */
	public static function get_best_sale_cta_link( $key, $utm_medium ) {
		$link = self::get_best_sale_value( $key );
		$link = self::add_missing_utm_params( $link, $utm_medium );
		return $link;
	}

	/**
	 * Add missing UTM parameters to a link.
	 *
	 * @since x.x
	 *
	 * @param string $link
	 * @param string $utm_medium
	 * @return string
	 */
	private static function add_missing_utm_params( $link, $utm_medium ) {
		$map        = array(
			'utm_source'   => 'WordPress',
			'utm_medium'   => $utm_medium,
			'utm_campaign' => 'liteplugin',
		);
		$utm_params = array();
		foreach ( $map as $key => $value ) {
			if ( strpos( $link, $key ) === false ) {
				$utm_params[ $key ] = $value;
			}
		}

		return add_query_arg( $utm_params, $link );
	}

	/**
	 * Get the best active sale that matches the current site.
	 *
	 * @since x.x
	 *
	 * @return array|false
	 */
	private static function get_best_sale() {
		if ( ! is_null( self::$best_sale ) ) {
			return self::$best_sale;
		}

		$api = new WPBDP_Sales_API();
		$api->get_api_info();

		$best_sale = false;
		foreach ( $api->get_api_info() as $sale ) {
			if ( $api->should_include_sale( $sale ) ) {
				if ( false === $best_sale || $sale['discount_percent'] > $best_sale['discount_percent'] ) {
					$best_sale = $sale;
				}
			}
		}

		self::$best_sale = $best_sale;
		return $best_sale;
	}
}
