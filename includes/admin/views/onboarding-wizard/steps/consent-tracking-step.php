<?php

/**
 * Onboarding Wizard - Never miss an important update step.
 *
 * @package Business Directory Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<section id="wpbdp-onboarding-consent-tracking-step" class="wpbdp-onboarding-step wpbdp-card-box wpbdp-current" data-step-name="<?php echo esc_attr( $step ); ?>">
	<div class="wpbdp-card-box-header">
		<?php
		WPBDP_App_Helper::show_logo(
			array(
				'size'  => 60,
				'round' => true,
			)
		);
		?>
	</div>

	<div class="wpbdp-card-box-content">
		<h2 class="wpbdp-card-box-title"><?php esc_html_e( 'Never miss an important update', 'business-directory-plugin' ); ?></h2>
		<p class="wpbdp-card-box-text">
			<?php esc_html_e( 'Get key updates, tips, and occasional offers to enhance your WordPress experience. Opt in and help us improve compatibility with your site!', 'business-directory-plugin' ); ?>
		</p>
	</div>

	<div class="wpbdp-card-box-footer">
		<a href="#" class="button button-secondary wpbdp-button-secondary wpbdp-button-large wpbdp-onboarding-skip-step">
			<?php esc_html_e( 'Skip', 'business-directory-plugin' ); ?>
		</a>

		<a href="#" id="wpbdp-onboarding-consent-tracking" class="button button-primary wpbdp-button-primary wpbdp-button-large">
			<?php
			esc_html_e( 'Allow & Continue', 'business-directory-plugin' );
			WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-arrowup1-icon', array( 'aria-hidden' => 'true' ) );
			?>
		</a>
	</div>

	<div class="wpbdp-card-box-permission">
		<span class="wpbdp-collapsible">
			<?php
			esc_html_e( 'Allow Business Directory Plugin to', 'business-directory-plugin' );
			WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-arrowdown1-icon', array( 'aria-hidden' => 'true' ) );
			?>
		</span>

		<div class="wpbdp-collapsible-content wpbdp-hidden">
			<div class="wpbdp-card-box-permission-item">
				<span><?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-user-icon', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-card-box-permission-item-content">
					<h4 title="' . esc_attr( __( 'Never miss important updates, get security warnings before they become public knowledge, and receive notifications about special offers and awesome new features.', 'business-directory-plugin' ) ) . '">
						<?php esc_html_e( 'View Basic Profile Info', 'business-directory-plugin' ); ?>
					</h4>

					<span><?php esc_html_e( 'Your WordPress user’s: first & last name and email address', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-card-box-permission-item">
				<span><?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-sample-form-icon', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-card-box-permission-item-content">
					<h4 title="' . esc_attr( __( 'To provide additional functionality that’s relevant to your website, avoid WordPress or PHP incompatibilities that can break your website, and recognize which languages & regions the plugin should be translated and tailored to.', 'business-directory-plugin' ) ) . '">
						<?php esc_html_e( 'View Basic Website Info', 'business-directory-plugin' ); ?>
					</h4>

					<span><?php esc_html_e( 'Homepage URL & title, WP & PHP versions, site language', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-card-box-permission-item">
				<span><?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-puzzle-icon-thin-icon', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Basic Plugin Info', 'business-directory-plugin' ); ?></h4>
					<span><?php esc_html_e( 'Current plugin & SDK versions, and if active or uninstalled', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-card-box-permission-item">
				<span><?php WPBDP_App_Helper::icon_by_class( 'wpbdpfont wpbdp-field-colors-style-icon wpbdp-svg20', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-card-box-permission-item-content">
					<h4><?php esc_html_e( 'View Plugins & Themes List', 'business-directory-plugin' ); ?></h4>
					<span><?php esc_html_e( 'Names, slugs, versions, and if active or not', 'business-directory-plugin' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</section>
