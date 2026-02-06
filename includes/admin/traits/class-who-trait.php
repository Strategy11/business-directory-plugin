<?php
/**
 * Class Who
 *
 * @package BDP/Includes/Admin/Class Who
 */

if ( ! trait_exists( 'WPBDP_Who' ) ) {

	/**
	 * Trait WPBDP_Who
	 *
	 * @since 6.4.18
	 */
	trait WPBDP_Who {
		/**
		 * Check if the message should be shown to the current user.
		 *
		 * @since 6.4.18
		 *
		 * @param array|string $who The target specified for a given API item (an Inbox message or Sale).
		 *
		 * @return bool
		 */
		protected function matches_who( $who ) {
			$who = (array) $who;
			if ( self::is_for_everyone( $who ) || self::is_license_type( $who ) ) {
				return true;
			}
			if ( in_array( 'free_first_30', $who, true ) && self::is_free_first_30() ) {
				return true;
			}
			if ( in_array( 'free_not_first_30', $who, true ) && self::is_free_not_first_30() ) {
				return true;
			}
			if ( self::check_free_segments( $who ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Check if the API item is for everyone.
		 *
		 * @since 6.4.18
		 *
		 * @param array $who The target specified for a given API item (an Inbox message or Sale).
		 *
		 * @return bool
		 */
		private static function is_for_everyone( $who ) {
			return in_array( 'all', $who, true );
		}

		/**
		 * Check if there is a license match.
		 *
		 * @since 6.4.18
		 *
		 * @param array $who The target specified for a given API item (an Inbox message or Sale).
		 *
		 * @return bool
		 */
		private static function is_license_type( $who ) {
			return in_array( self::get_license_type(), $who, true );
		}

		/**
		 * Get license type.
		 *
		 * @since 6.4.18
		 *
		 * @return string
		 */
		private static function get_license_type() {
			if ( is_callable( 'WPBDP_Addons::get_raw_license_type' ) ) {
				$raw_license_type = WPBDP_Addons::get_raw_license_type();
				if ( 'creator' === $raw_license_type ) {
					$raw_license_type = 'basic';
				}

				return $raw_license_type;
			}

			return self::is_free() ? 'free' : 'pro';
		}

		/**
		 * Check if user is still using the Lite version only, and within
		 * the first 30 days of activation.
		 *
		 * @since 6.4.18
		 *
		 * @return bool
		 */
		private static function is_free_first_30() {
			return self::is_free() && self::is_first_30();
		}

		/**
		 * Check if site is within the first 30 days.
		 *
		 * @since 6.4.18
		 *
		 * @return bool
		 */
		private static function is_first_30() {
			$activation_timestamp = get_option( 'wpbdp_first_activation' );
			if ( false === $activation_timestamp ) {
				// If the option does not exist, assume that it is
				// because the user was active before this option was introduced.
				return false;
			}

			$cutoff = strtotime( '-30 days' );
			return $activation_timestamp > $cutoff;
		}

		/**
		 * Check if site is using free version and not within first 30 days.
		 *
		 * @since 6.4.18
		 *
		 * @return bool
		 */
		private static function is_free_not_first_30() {
			return self::is_free() && ! self::is_first_30();
		}

		/**
		 * Check if the Pro plugin is active. If not, consider the user to be on the free version.
		 *
		 * @since 6.4.18
		 *
		 * @return bool
		 */
		private static function is_free() {
			return ! WPBDP_Admin_Education::is_installed( 'premium' );
		}

		/**
		 * Check if the site matches one of the free segments.
		 *
		 * @since 6.4.18
		 *
		 * @param array $who The target specified for a given API item (an Inbox message or Sale).
		 *
		 * @return bool
		 */
		private static function check_free_segments( $who ) {
			$segments          = array(
				'free_first_1',
				'free_first_2_3',
				'free_first_4_7',
				'free_first_8_11',
				'free_first_12_19',
				'free_first_20_30',
			);
			$intersecting_keys = array_intersect( $segments, $who );

			if ( ! $intersecting_keys || ! self::is_free() || ! self::is_first_30() ) {
				return false;
			}

			$activation_timestamp = get_option( 'wpbdp_first_activation' );

			foreach ( $intersecting_keys as $key ) {
				if ( self::matches_segment( $key, $activation_timestamp ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the site matches a free segment.
		 *
		 * @since 6.4.18
		 *
		 * @param string $key                  The key of the given segment.
		 * @param int    $activation_timestamp The activation timestamp of the current user.
		 *
		 * @return bool
		 */
		private static function matches_segment( $key, $activation_timestamp ) {
			$range_part  = str_replace( 'free_first_', '', $key );
			$range_parts = explode( '_', $range_part );
			if ( ! $range_parts ) {
				return false;
			}

			$current_day = (int) floor( ( time() - $activation_timestamp ) / DAY_IN_SECONDS ) + 1;
			$start       = (int) $range_parts[0];
			$end         = 1 === count( $range_parts ) ? $start : (int) $range_parts[1];

			return $current_day >= $start && $current_day <= $end;
		}
	}
}
