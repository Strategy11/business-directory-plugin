<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="wpbdp-admin-delete-modal" class="hidden settings-lite-cta">
	<div class="metabox-holder">
		<div class="postbox">
			<a href="#" class="dismiss alignright" title="<?php esc_attr_e( 'Dismiss', 'business-directory-plugin' ); ?>">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/close.svg' ); ?>" width="24" height="24"/>
			</a>
			<div class="inside">
				<div class="form-wrap">
					<h2><?php esc_html_e( 'Are you sure you want to do this?', 'business-directory-plugin' ); ?></h2>
					<form action="<?php echo esc_url( $modal_action_url ); ?>" method="POST">
						<div class="inner-message">
							<p><?php echo sprintf( __( 'You\'re going to delete %1$s. This action cannot be undone.', 'business-directory-plugin' ), '<b><span class="wpbdp-item-name"></span></b>' ) ?></p>
						</div>
						<input type="hidden" name="id" value="" />
						<input type="hidden" name="action" value="" />
						<?php wp_nonce_field( 'wpbdp_admin_delete_nonce', 'wpbdp_admin_delete_nonce' ); ?>
						<div class="clear"></div>
						<p class="close">
							<a class="dismiss-button" href="#"><?php esc_html_e( 'Cancel', 'business-directory-plugin' ); ?></a>
						</p>
						<p class="submit">
							<?php submit_button( esc_html__( 'Delete', 'business-directory-plugin' ), 'delete wpbdp-button-primary' ); ?>
						</p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
