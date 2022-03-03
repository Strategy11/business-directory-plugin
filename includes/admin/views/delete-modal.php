<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="wpbdp-admin-confirm-modal" class="hidden settings-lite-cta">
	<div class="metabox-holder">
		<div class="postbox">
			<a href="#" class="dismiss alignright" title="<?php esc_attr_e( 'Dismiss', 'business-directory-plugin' ); ?>">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/close.svg' ); ?>" width="24" height="24"/>
			</a>
			<div class="inside">
				<h2><?php esc_html_e( 'Are you sure you want to do this?', 'business-directory-plugin' ); ?></h2>

			</div>
		</div>
	</div>
</div>
