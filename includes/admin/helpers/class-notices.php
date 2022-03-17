<?php

/**
 * @since x.x
 */
class WPBDP_Admin_Notices {

	public static function load_hooks() {
		add_action( 'admin_footer', array( __CLASS__, 'admin_footer' ) );
	}

	/**
	 * Show admin notification icon in footer.
	 */
	public static function admin_footer() {
		if ( ! WPBDP_App_Helper::is_bd_page() ) {
			return;
		}
		self::show_bell();
	}

	/**
	 * Admin floating notification bell.
	 *
	 * @since x.x
	 */
	public static function show_bell() {
		?>
		<div class="wpbdp-bell-notifications hidden">
			<a href="#" class="wpbdp-bell-notifications-close"><?php esc_html_e( 'Hide notifications', 'business-directory-plugin' ); ?></a>
			<ul class="wpbdp-bell-notifications-list"></ul>
		</div>
		<div class="wpbdp-bell-notification">
			<a class="wpbdp-bell-notification-icon" href="#">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 60 60"><rect width="60" height="60" fill="#fff" rx="12"/><path stroke="#3C4B5D" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M37.5 25a7.5 7.5 0 0 0-15 0c0 8.8-3.8 11.3-3.8 11.3h22.6s-3.8-2.5-3.8-11.3ZM32.2 41.3a2.5 2.5 0 0 1-4.4 0"/><circle class="wpbdp-bell-notification-icon-indicator" cx="39.4" cy="20.6" r="6.1" fill="#FF5A5A" stroke="#fff"><animate attributeName="r" from="6" to="8" dur="1.5s" begin="0s" repeatCount="indefinite"/><animate attributeName="opacity" from="1" to="0.8" dur="1.5s" begin="0s" repeatCount="indefinite"/></circle></svg>
			</a>
		</div>
		<?php
	}

	/**
	 * Show the settings notice.
	 * Renders settings notice in notification area. Adds extra wpbdp-notice to show in area.
	 *
	 * @link https://developer.wordpress.org/reference/functions/settings_errors/
	 *
	 * @since x.x
	 */
	public static function settings_errors() {
		$settings_errors = get_settings_errors();
		if ( empty( $settings_errors ) ) {
			return;
		}

		foreach ( $settings_errors as $key => $details ) {
			if ( 'updated' === $details['type'] ) {
				$details['type'] = 'success';
			}

			if ( in_array( $details['type'], array( 'error', 'success', 'warning', 'info' ), true ) ) {
				$details['type'] = 'notice-' . $details['type'];
			}

			$css_id    = sprintf(
				'setting-error-%s',
				esc_attr( $details['code'] )
			);
			$css_class = sprintf(
				'notice wpbdp-notice wpbdp-snackbar-notice %s settings-error is-dismissible',
				esc_attr( $details['type'] )
			);

			echo '<div id="' . esc_attr( $css_id ) . '" class="' . esc_attr( $css_class ) . '">';
			echo '<p><strong>' . wp_kses_post( $details['message'] ) . '</strong></p>';
			echo '</div>';
		}
	}
}
