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

		$cta = ' <a href="' . esc_url( $tip['link'] ) . '" target="_blank" rel="noopener">' . esc_html( $tip['cta'] ) . '</a>';

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
	 * @since x.x
	 */
	public static function show_tip( $id ) {
		$tip = self::get_tip( $id );
		if ( empty( $tip ) || self::is_installed( $tip['requires'] ) ) {
			return;
		}

		$message = esc_html( $tip['tip'] );
		$message .= '<a href="' . esc_url( $tip['link'] ) . '" target="_blank" rel="noopener">';
		$message .= esc_html( $tip['cta'] );
		$message .= '</a>';

		self::show_tip_message( $message );
	}

	/**
	 * @since x.x
	 */
	public static function show_tip_message( $message ) {
		?>
		<p class="wpbdp-pro-tip">
			<svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 1.00003L1 13H10L9 21L19 9.00003H10L11 1.00003Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php echo $message; // already escaped. ?>
		</p>
		<?php
	}

	/**
	 * @since 5.9.1
	 */
	private static function tips() {
		return array(
			'zip'     => array(
				'requires' => 'zipcodesearch',
				'tip'      => 'Search listings by ZIP/postal code and distance.',
				'cta'      => 'Upgrade to Pro.',
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
			'maps'    => array(
				'requires' => 'googlemaps',
				'tip'      => 'Add Google Maps to your directory listings.',
				'cta'      => 'Upgrade to Pro.',
			),
			'ratings' => array(
				'requires' => 'ratings',
				'tip'      => 'Add more value to listings with visitors reviews and ratings.',
				'cta'      => 'Upgrade Now.',
			),
			'attachments' => array(
				'requires' => 'attachments',
				'tip'      => 'Want to allow file uploads with listing submissions?',
				'cta'      => 'Upgrade Now.',
			),
			'discounts' => array(
				'requires' => 'discount-codes',
				'tip'      => 'Offer discount & coupon codes to your paid listing customers.',
				'cta'      => 'Upgrade to Pro.',
			),
			'migrator'  => array(
				'requires' => 'migrate',
				'tip'      => 'Need to export, backup, or move your directory settings and listings?',
				'cta'      => 'Upgrade Now.',
			),
			'install-premium'  => array(
				'requires' => 'premium',
				'tip'      => 'Install modules with one click, get table listings, abandonment emails, and more.',
				'link'     => wpbdp_admin_upgrade_link( $id, '/account/downloads/' ),
				'cta'      => 'Download Now.',
			),
		);
		// TODO: Show maps and attachments.
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

		$has_premium = self::is_installed( 'premium' );
		if ( $has_premium && $tip['requires'] === 'premium' ) {
			// Don't show it.
			return array();
		}

		$is_any_upgrade = $tip['cta'] === 'Upgrade Now.' || $tip['cta'] === 'Upgrade to Premium';
		if ( $has_premium && $is_any_upgrade ) {
			$tip['cta']  = 'Install Now.';
			$tip['link'] = admin_url( 'admin.php?page=wpbdp-addons' );
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
