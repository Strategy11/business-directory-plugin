<?php

/**
 * Onboarding Wizard Page.
 *
 * @package Business Directory Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<div id="wpbdp-onboarding-wizard-page" class="wrap wpbdp-admin-plugin-landing wpbdp-hide-js" data-current-step="consent-tracking">
	<div id="wpbdp-onboarding-container">
		<ul id="wpbdp-onboarding-rootline" class="wpbdp-onboarding-rootline">
			<li class="wpbdp-onboarding-rootline-item" data-step="consent-tracking">
				<?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-checkmark-icon', array( 'aria-hidden' => 'true' ) ); ?>
			</li>
			<li class="wpbdp-onboarding-rootline-item" data-step="success">
				<?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-checkmark-icon', array( 'aria-hidden' => 'true' ) ); ?>
			</li>
		</ul>

		<?php
		foreach ( $step_parts as $step => $file ) {
			require $view_path . $file;
		}
		?>

		<a id="wpbdp-onboarding-return-dashboard" href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_settings' ) ); ?>">
			<?php esc_html_e( 'Exit Onboarding', 'business-directory-plugin' ); ?>
		</a>
	</div>
</div>
