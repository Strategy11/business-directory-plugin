<?php
/**
 * Onboarding Wizard - Success (You're All Set!) Step.
 *
 * @package Formidable
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<section id="wpbdp-onboarding-success-step" class="wpbdp-onboarding-step wpbdp-card-box wpbdp-hidden" data-step-name="<?php echo esc_attr( $step ); ?>">
	<div class="wpbdp-card-box-header">
	<?php WPBDP_App_Helper::show_logo( 60 ); ?>
	</div>

	<div class="wpbdp-card-box-content wpbdp-mt-sm">
		<h2 class="wpbdp-card-box-title"><?php esc_html_e( 'You\'re All Set!', 'business-directory-plugin' ); ?></h2>
		<p class="wpbdp-card-box-text">
			<?php esc_html_e( 'Congratulations on completing the onboarding process! We hope you enjoy using Formidable Forms.', 'business-directory-plugin' ); ?>
		</p>
	</div>

	<?php
	// FrmOnboardingWizardHelper::print_footer(
	//  array(
	//      'footer-class'               => 'wpbdp-justify-center wpbdp-mt-2xl',
	//      'display-back-button'        => false,
	//      'primary-button-text'        => __( 'Go to Dashboard', 'business-directory-plugin' ),
	//      'primary-button-href'        => admin_url( 'admin.php?page=' . FrmDashboardController::PAGE_SLUG ),
	//      'primary-button-role'        => false,
	//      'secondary-button-text'      => __( 'Create a Form', 'business-directory-plugin' ),
	//      'secondary-button-href'      => admin_url( 'admin.php?page=' . FrmFormTemplatesController::PAGE_SLUG ),
	//      'secondary-button-role'      => false,
	//      'secondary-button-skip-step' => false,
	//  )
	// );
	?>
</section>
