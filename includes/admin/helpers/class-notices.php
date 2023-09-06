<?php

/**
 * @since 6.0
 */
class WPBDP_Admin_Notices {

	public static function load_hooks() {
		add_action( 'admin_footer', __CLASS__ . '::admin_footer' );
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
	 * @since 6.0
	 */
	public static function show_bell() {
		?>
		<div class="wpbdp-bell-notifications hidden">
			<a href="#" class="wpbdp-bell-notifications-close"><?php esc_html_e( 'Hide notifications', 'business-directory-plugin' ); ?></a>
			<ul class="wpbdp-bell-notifications-list"></ul>
		</div>
		<div id="wpbdp-snackbar-notices"></div>
		<?php
	}

	/**
	 * Show the settings notice.
	 * Renders settings notice in notification area.
	 *
	 * @since 6.0
	 */
	public static function settings_errors() {
		$settings_errors = get_settings_errors();

		foreach ( $settings_errors as $details ) {
			// The WP docs on this are incorrect as of 2022-04-28.
			/** @phpstan-ignore-next-line */
			wpbdp_admin_message( $details['message'], $details['type'] );
		}

		wpbdp_admin_notices();
	}
}
