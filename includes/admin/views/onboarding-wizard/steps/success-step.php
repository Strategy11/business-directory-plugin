<?php

/**
 * Onboarding Wizard - Success (You're All Set!) Step.
 *
 * @package Business Directory Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

?>
<section id="wpbdp-onboarding-success-step" class="wpbdp-onboarding-step wpbdp-card-box wpbdp-hidden" data-step-name="<?php echo esc_attr( $step ); ?>">
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
		<h2 class="wpbdp-card-box-title"><?php esc_html_e( 'You\'re All Set!', 'business-directory-plugin' ); ?></h2>
		<p class="wpbdp-card-box-text">
			<?php esc_html_e( 'Congratulations on completing the onboarding process! We hope you enjoy using Business Directory Plugin.', 'business-directory-plugin' ); ?>
		</p>
	</div>

	<div class="wpbdp-card-box-footer">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpbdp_listing' ) ); ?>" class="button button-secondary wpbdp-button-secondary wpbdp-button-large">
			<?php esc_html_e( 'Create a Listing', 'business-directory-plugin' ); ?>
		</a>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp_settings' ) ); ?>" class="button button-primary wpbdp-button-primary wpbdp-button-large">
			<?php esc_html_e( 'Go to Settings', 'business-directory-plugin' ); ?>
		</a>
	</div>
</section>
