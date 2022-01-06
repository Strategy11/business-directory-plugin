<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="wpbdp-wrap" id="wpbdp-addons-page">
	<?php
	wpbdp_admin_header(
		array(
			'title'   => esc_html__( 'Directory Modules', 'business-directory-plugin' ),
			'sidebar' => false,
			'echo'    => true,
		)
	);

	$modules = wpbdp()->modules->get_modules();
	if ( count( $modules ) > 1 && ! isset( $modules['premium'] ) ) {
		// Has a module, but not Premium.
		WPBDP_Admin_Education::show_tip( 'install-premium' );
	}

	?>
	<div class="wrap">

	<div id="the-list" class="wpbdp-addons">
		<?php foreach ( $addons as $slug => $addon ) {
			if ( strpos( $addon['title'], 'Theme' ) ) {
				// Skip themes for now since they have another page.
				continue;
			}
		?>
			<div class="wpbdp-card plugin-card-<?php echo esc_attr( $slug ); ?> wpbdp-no-thumb wpbdp-addon-<?php echo esc_attr( $addon['status']['type'] ); ?>">
				<div class="plugin-card-top">
					<?php if ( strtotime( $addon['released'] ) > strtotime( '-90 days' ) ) : ?>
						<div class="wpbdp-ribbon">
							<span><?php esc_attr_e( 'New' ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain ?></span>
						</div>
					<?php endif; ?>
					<div class="wpbdp-grid">
						<div class="wpbdp-col-8">
							<h2 class="plugin-card-title">
								<?php echo esc_html( $addon['title'] ); ?>
							</h2>
							<span class="addon-status">
								<?php
								printf(
									/* translators: %s: Status name */
									esc_html__( 'Status: %s', 'business-directory-plugin' ),
									'<span class="addon-status-label">' . esc_html( $addon['status']['label'] ) . '</span>'
								);
								?>
							</span>
						</div>
						<div class="wpbdp-col-4">
							<?php
							$passing = array(
								'addon'         => $addon,
								'license_type'  => ! empty( $license_type ) ? $license_type : false,
								'plan_required' => 'plan_required',
								'upgrade_link'  => $pricing,
							);
							WPBDP_Show_Modules::show_conditional_action_button( $passing );
							?>
						</div>
					</div>
					<p class="plugin-card-details">
						<?php echo esc_html( $addon['excerpt'] ); ?>
						<?php $show_docs = isset( $addon['docs'] ) && ! empty( $addon['docs'] ) && $addon['installed']; ?>
						<?php if ( $show_docs ) { ?>
							<div class="plugin-card-docs">
								<a href="<?php echo esc_url( $addon['docs'] ); ?>" target="_blank" aria-label="<?php esc_attr_e( 'View Docs', 'business-directory-plugin' ); ?>">
									<?php esc_html_e( 'View Docs', 'business-directory-plugin' ); ?>
								</a>
							</div>
						<?php } ?>
					</p>
					<?php
					if ( ! $show_docs ) {
						// $plan_required = FrmFormsHelper::get_plan_required( $addon );
						// FrmFormsHelper::show_plan_required( $plan_required, $pricing . '&utm_content=' . $addon['slug'] );
					}
					?>
				</div>
			</div>
		<?php } ?>
	</div>
</div>
</div>
