<h3><?php echo esc_html( $_state->step_number ) . ' - '; ?><?php esc_html_e( 'Additional Information', 'business-directory-plugin' ); ?></h3>

<form id="wpbdp-listing-form-extra" class="wpbdp-listing-form" method="POST" action="" enctype="multipart/form-data">
	<input type="hidden" name="_state" value="<?php echo esc_attr( $_state->id ); ?>" />
	<?php echo $output; ?>
	<input type="submit" name="continue-with-save" value="<?php esc_attr_e( 'Continue', 'business-directory-plugin' ); ?> " class="submit" />
</form>
