<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Listing Information', 'templates', 'WPBDM' ); ?></h3>

<?php if ($validation_errors): ?>
	<ul class="validation-errors">
		<?php foreach ($validation_errors as $error_msg): ?>
		<li><?php echo $error_msg; ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<form id="wpbdp-listing-form-fields" class="wpbdp-listing-form" method="POST" action="">
	<input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />
	<input type="hidden" name="step" value="listing_fields" />

	<legend><?php _ex( '* Indicates required fields.', 'templates', 'WPBDM' ); ?></legend>

	<?php foreach ( $fields as &$field ): ?>
		<?php echo $field->render( isset( $_state->fields[ $field->get_id() ] ) ? $_state->fields[ $field->get_id() ] : $field->convert_input( null ) , 'submit', $_state ); ?>
	<?php endforeach; ?>
    <?php do_action( 'wpbdp_view_submit_listing-after_fields', $_state ); ?>

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

	<input type="submit" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?> " class="submit" />
</form>
