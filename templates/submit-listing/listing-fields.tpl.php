<h3>
	<?php _ex( '3 - Listing Information', 'templates', 'WPBDM' ); ?>
</h3>

<?php if ($validation_errors): ?>
	<ul class="validation-errors">
		<?php foreach ($validation_errors as $error_msg): ?>
		<li><?php echo $error_msg; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<form id="wpbdp-listing-form-fields" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state; ?>" />

	<legend><?php _ex( '* Indicates required fields.', 'templates', 'WPBDM' ); ?></legend>

	<?php foreach ( $fields as &$field ): ?>
		<?php echo $field->render( wpbdp_getv( $state->fields, $field->get_id(), $field->convert_input( null ) ), 'submit', $state ); ?>
	<?php endforeach; ?>

	<?php if ( $terms_and_conditions ): ?>
	<div class="wpbdp-form-field terms-and-conditions required">
		<?php echo $terms_and_conditions; ?>
	</div>
	<?php endif; ?>

	<?php if ( $recaptcha ): ?>
	<div class="wpbdp-form-field recaptcha">
		<?php echo $recaptcha; ?>
	</div>
	<?php endif; ?>

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " />	
</form>