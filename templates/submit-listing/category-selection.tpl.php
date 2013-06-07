<h3>
	<?php _ex( '1 - Category Selection', 'templates', 'WPBDM' ); ?>
</h3>

<form id="wpbdp-listing-form-categories" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state; ?>" />

	<?php echo $category_field->render( $state->categories ); ?>

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />	
</form>