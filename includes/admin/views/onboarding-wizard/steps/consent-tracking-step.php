<?php
/**
 * Onboarding Wizard - Never miss an important update step.
 *
 * @package Formidable
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<section id="wpbdp-onboarding-consent-tracking-step" class="wpbdp-onboarding-step wpbdp-card-box wpbdp-current" data-step-name="<?php echo esc_attr( $step ); ?>">
	<div class="wpbdp-card-box-header">
		<?php WPBDP_App_Helper::show_logo( 60 ); ?>
	</div>

	<div class="wpbdp-card-box-content wpbdp-mt-md">
		<h2 class="wpbdp-card-box-title wpbdp-mb-sm"><?php esc_html_e( 'Never miss an important update', 'business-directory-plugin' ); ?></h2>
		<p class="wpbdp-card-box-text">
			<?php esc_html_e( 'Get key updates, tips, and occasional offers to enhance your WordPress experience. Opt in and help us improve compatibility with your site!', 'business-directory-plugin' ); ?>
		</p>
	</div>

	<?php
	// FrmOnboardingWizardHelper::print_footer(
	//  array(
	//      'primary-button-text'      => __( 'Allow & Continue', 'business-directory-plugin' ),
	//      'primary-button-id'        => 'wpbdp-onboarding-consent-tracking',
	//      'primary-button-with-icon' => true,
	//      'secondary-button-text'    => __( 'Skip', 'business-directory-plugin' ),
	//      'footer-class'             => 'wpbdp-justify-center',
	//      'display-back-button'      => false,
	//  )
	// );
	?>

	<div class="dropdown wpbdp-mt-lg">
		<div id="wpbdp-onboarding-consent-tracking-list" class="wpbdp-dropdown-toggle wpbdp-cursor-pointer" data-toggle="dropdown">
			<span class="wpbdp_bstooltip" data-placement="right">
				<?php esc_html_e( 'Allow Formidable Forms to', 'business-directory-plugin' ); ?>
			</span>

			<?php // FrmAppHelper::icon_by_class( 'frmfont wpbdp_arrowdown6_icon wpbdp_svg13', array( 'aria-hidden' => 'true' ) ); ?>
		</div>

		<div class="wpbdp-dropdown-menu wpbdp-mt-sm" aria-labelledby="wpbdp-onboarding-consent-tracking-list">
			<div class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-py-sm">
				<span><?php // FrmAppHelper::icon_by_class( 'frmfont wpbdp_user_icon wpbdp_svg15', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-flex-col wpbdp-gap-2xs wpbdp-ml-2xs">
					<h4 class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-text-sm wpbdp-font-medium wpbdp-text-grey-700 wpbdp-m-0">
						<?php
						esc_html_e( 'View Basic Profile Info', 'business-directory-plugin' );

						// FrmAppHelper::tooltip_icon(
						//  __( 'Never miss important updates, get security warnings before they become public knowledge, and receive notifications about special offers and awesome new features.', 'business-directory-plugin' ),
						//  array(
						//      'class' => 'wpbdp-inline-flex',
						//  )
						// );
						?>
					</h4>
					<span class="wpbdp-text-xs wpbdp-text-grey-500 wpbdp-m-0"><?php esc_html_e( 'Your WordPress user’s: first & last name and email address', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-py-sm">
				<span><?php // FrmAppHelper::icon_by_class( 'frmfont wpbdp_sample_form_icon wpbdp_svg15', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-flex-col wpbdp-gap-2xs wpbdp-ml-2xs">
					<h4 class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-text-sm wpbdp-font-medium wpbdp-text-grey-700 wpbdp-m-0">
						<?php
						esc_html_e( 'View Basic Website Info', 'business-directory-plugin' );

						// FrmAppHelper::tooltip_icon(
						//  __( 'To provide additional functionality that’s relevant to your website, avoid WordPress or PHP incompatibilities that can break your website, and recognize which languages & regions the plugin should be translated and tailored to.', 'business-directory-plugin' ),
						//  array(
						//      'class' => 'wpbdp-inline-flex',
						//  )
						// );
						?>
					</h4>
					<span class="wpbdp-text-xs wpbdp-text-grey-500 wpbdp-m-0"><?php esc_html_e( 'Homepage URL & title, WP & PHP versions, site language', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-py-sm">
				<span><?php // FrmAppHelper::icon_by_class( 'frmfont wpbdp_puzzle_icon_thin wpbdp_svg15', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-flex-col wpbdp-gap-2xs wpbdp-ml-2xs">
					<h4 class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-text-sm wpbdp-font-medium wpbdp-text-grey-700 wpbdp-m-0">
						<?php esc_html_e( 'View Basic Plugin Info', 'business-directory-plugin' ); ?>
					</h4>
					<span class="wpbdp-text-xs wpbdp-text-grey-500 wpbdp-m-0"><?php esc_html_e( 'Current plugin & SDK versions, and if active or uninstalled', 'business-directory-plugin' ); ?></span>
				</div>
			</div>

			<div class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-py-sm">
				<span><?php // FrmAppHelper::icon_by_class( 'frmfont wpbdp-field-colors-style wpbdp_svg20', array( 'aria-hidden' => 'true' ) ); ?></span>

				<div class="wpbdp-flex-col wpbdp-gap-2xs wpbdp-ml-2xs">
					<h4 class="wpbdp-flex wpbdp-gap-xs wpbdp-items-center wpbdp-text-sm wpbdp-font-medium wpbdp-text-grey-700 wpbdp-m-0">
						<?php
						esc_html_e( 'View Plugins & Themes List', 'business-directory-plugin' );

						// FrmAppHelper::tooltip_icon(
						//  __( 'To ensure compatibility and avoid conflicts with your installed plugins and themes.', 'business-directory-plugin' ),
						//  array(
						//      'class' => 'wpbdp-inline-flex',
						//  )
						// );
						?>
					</h4>
					<span class="wpbdp-text-xs wpbdp-text-grey-500 wpbdp-m-0"><?php esc_html_e( 'Names, slugs, versions, and if active or not', 'business-directory-plugin' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</section>
