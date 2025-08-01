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
				'id'    => $id,
				'desc'  => wp_kses_post( $tip['tip'] ) . $cta,
				'type'  => 'education',
				'group' => $group,
			)
		);
	}

	/**
	 * @since 5.10
	 */
	public static function show_tip( $id ) {
		$tip = self::get_tip( $id );
		if ( empty( $tip ) || self::is_installed( $tip['requires'] ) ) {
			return;
		}

		$message  = wp_kses_post( $tip['tip'] );
		$message .= self::render_cta( $tip );

		self::show_tip_message( $message );
	}

	/**
	 * Render the cta.
	 *
	 * @since 6.0
	 *
	 * @param array $tip The current tip.
	 *
	 * @return string
	 */
	public static function render_cta( $tip ) {
		$cta  = '<a href="' . esc_url( $tip['link'] ) . '" target="_blank" rel="noopener">';
		$cta .= esc_html( $tip['cta'] );
		$cta .= '</a>';
		return $cta;
	}

	/**
	 * @since 5.10
	 */
	public static function show_tip_message( $message ) {
		?>
		<div class="wpbdp-pro-tip">
			<?php // phpcs:ignore SlevomatCodingStandard.Files.LineLength ?>
			<svg width="20" height="22" viewBox="0 0 20 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 1.00003L1 13H10L9 21L19 9.00003H10L11 1.00003Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $message; // already escaped.
			?>
		</div>
		<?php
	}

	/**
	 * @since 5.9.1
	 */
	private static function tips() {
		return array(
			'zip'             => array(
				'requires' => 'zipcodesearch',
				'tip'      => 'Search listings by ZIP/postal code and distance.',
				'cta'      => 'Upgrade to Pro.',
			),
			'abc'             => array(
				'requires' => 'premium',
				'tip'      => 'Add ABC filtering to get listings by the first letter.',
			),
			'abandon'         => array(
				'requires' => 'premium',
				'tip'      => 'Want to ask users to come back for abandoned payments?',
			),
			'maps'            => array(
				'requires' => 'googlemaps',
				'tip'      => 'Add Google Maps to your directory listings.',
				'cta'      => 'Upgrade to Pro.',
			),
			'ratings'         => array(
				'requires' => 'ratings',
				'tip'      => 'Add more value to listings with visitors reviews and ratings.',
			),
			'attachments'     => array(
				'requires' => 'attachments',
				'tip'      => 'Want to allow file uploads with listing submissions?',
			),
			'discounts'       => array(
				'requires' => 'discount-codes',
				'tip'      => 'Offer discount & coupon codes to your paid listing customers.',
			),
			'migrator'        => array(
				'requires' => 'migrate',
				'tip'      => 'Need to export, backup, or move your directory settings and listings?',
			),
			'categories'      => array(
				'requires' => 'categories',
				'tip'      => 'Want to show a list of images for your categories?',
			),
			'install-premium' => array(
				'requires' => 'premium',
				'tip'      => 'Install modules with one click, get table listings, abandonment emails, and more.',
				'link'     => wpbdp_admin_upgrade_link( 'install-modules', '/account/downloads/' ),
				'cta'      => 'Download Now.',
			),
			'table'           => array(
				'requires' => 'premium',
				'tip'      => 'Show listings in a grid or table. <img src="' . esc_url( WPBDP_ASSETS_URL . 'images/premium-layout.svg' ) . '" alt="Directory listing layout setting" style="max-width:645px" />',
			),
		);
		// TODO: Show maps and attachments.
	}

	/**
	 * @since 5.9.1
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	private static function get_tip( $id ) {
		$tips = self::tips();
		$tip  = isset( $tips[ $id ] ) ? $tips[ $id ] : array();

		$should_setup_sales_api = empty( $tip['link'] ) || empty( $tip['cta'] );
		if ( $should_setup_sales_api ) {
			if ( ! class_exists( 'WPBDP_Admin' ) ) {
				require_once WPBDP_INC . 'admin/class-admin.php';
			}

			WPBDP_Admin::setup_sales_api();
		}

		if ( empty( $tip['link'] ) ) {
			$tip['link'] = WPBDP_Sales_API::get_best_sale_cta_link( 'pro_tip_cta_link', $id ) ?? wpbdp_admin_upgrade_link( $id );
		}
		if ( empty( $tip['cta'] ) ) {
			$tip['cta'] = WPBDP_Sales_API::get_best_sale_value( 'pro_tip_cta_text' ) ?? __( 'Upgrade Now', 'business-directory-plugin' );
		}

		$has_premium = self::is_installed( 'premium' );
		if ( $has_premium && $tip['requires'] === 'premium' ) {
			// Don't show it.
			return array();
		}

		$upgrade_labels = array( 
			'Upgrade Now.', 
			'Upgrade to Premium', 
			'Upgrade to Pro.',
		);

		$is_any_upgrade = in_array( $tip['cta'], $upgrade_labels, true );

		$is_premium_cta = $is_any_upgrade && $has_premium;
		$is_pro_cta     = $is_any_upgrade && self::has_access_to( $tip['requires'] );

		if ( $is_premium_cta || $is_pro_cta ) {
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
	public static function is_installed( $requires ) {
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

	/**
	 * Checks if the user has access to a module
	 *
	 * @param string $module
	 * 
	 * @return bool
	 */
	private static function has_access_to( $module ) {
		$licenses = get_option( 'wpbdp_licenses', array() );
		$license  = isset( $licenses[ 'module-business-directory-' . $module ] ) ? $licenses[ 'module-business-directory-' . $module ] : null;
	
		return is_array( $license ) && isset( $license['status'] ) && $license['status'] === 'valid';
	}
}
