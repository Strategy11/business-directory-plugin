<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Category Selection', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-categories" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

	<?php echo $category_field->render( array_keys( $_state->categories ) ); ?>

	<input type="submit" class="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />	
</form>
