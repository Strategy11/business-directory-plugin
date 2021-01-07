<?php
/**
 * Class WPBDP_Admin_Education
 *
 * @package BDP/Includes/Admin
 */

/**
 * Class WPBDP_Admin_Education
 *
 * @since 5.9.1
 */
class WPBDP_Admin_Education {

	/**
	 * @since 5.9.1
	 */
	public static function add_tip_in_settings( $id, $group ) {
		$tip = self::get_tip( $id );
		if ( empty( $tip ) || self::is_installed( $tip['requires'] ) ) {
			return;
		}

		$cta = ' <a href="' . esc_url( $tip['link'] ) . '">' . esc_html( $tip['cta'] ) . '</a>';

        wpbdp_register_setting(
            array(
                'id'      => $id,
				'desc'    => esc_html( $tip['tip'] ) . $cta,
                'type'    => 'education',
                'group'   => $group,
            )
        );
	}

	/**
	 * @since 5.9.1
	 */
	private static function tips() {
		return array(
			'zip'     => array(
				'requires' => 'zipcodesearch',
				'tip'      => 'Search listings by ZIP/postal code and distance.',
				'cta'      => 'Get Zip Code Search',
			),
			'abc'     => array(
				'requires' => 'premium',
				'tip'      => 'Add ABC filtering to get listings by the first letter.',
				'cta'      => 'Upgrade Now.',
			),
			'abandon' => array(
				'requires' => 'premium',
				'tip'      => 'Want to ask users to come back for abandoned payments?',
				'cta'      => 'Upgrade Now.',
			),
		);
	}

	/**
	 * @param string $id
	 *
	 * @since 5.9.1
	 *
	 * @return array
	 */
	private static function get_tip( $id ) {
		$tips = self::tips();
		$tip  = isset( $tips[ $id ] ) ? $tips[ $id ] : array();
		if ( empty( $tip['link'] ) ) {
			$tip['link'] = wpbdp_admin_upgrade_link( $id );
		}
		if ( empty( $tip['cta'] ) ) {
			$tip['cta'] = 'Upgrade to Premium';
		}
		return $tip;
	}

	/**
	 * @since 5.9.1
	 *
	 * @return bool
	 */
	private static function is_installed( $requires ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();
		$installed = array_keys( $installed );
		foreach ( $installed as $module ) {
			$name = explode( '/', $module )[0];
			$name = str_replace( 'business-directory-', '', $name );
			if ( $name === $requires ) {
				return is_plugin_active( $module );
			}
		}

		return false;
	}
}
