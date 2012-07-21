<?php
	echo wpbdp_admin_header(_x('Add Form Field', 'form-fields admin', 'WPBDM'));
?>
<?php wpbdp_admin_notices(); ?>

<?php
$api = wpbdp_formfields_api();

$post_values = isset($_POST['field']) ? $_POST['field'] : array();
$field = isset($field) ? $field : null;
?>

<form id="wpbdp-formfield-form" action="" method="POST">
	<?php if (isset($field)): ?>
	<input type="hidden" name="field[id]" value="<?php echo $field->id; ?>" />
	<?php endif; ?>
	<table class="form-table">
		<tbody>
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Field Label', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<input name="field[label]"
						   type="text"
						   aria-required="true"
						   value="<?php echo wpbdp_getv($post_values, 'label', $field ? $field->label : ''); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php _ex('Field Association', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<?php if ($field && in_array($field->association, array('title', 'category', 'content', 'excerpt'))): ?>
						<input type="hidden" name="field[association]" value="<?php echo $field->association; ?>" />
						<strong><?php echo $api->getFieldAssociations($field->association); ?></strong>
					<?php else: ?>
					<select name="field[association]" id="field-association">
					<?php foreach ($api->getFieldAssociations() as $key => $name): ?>
						<?php if (!in_array($key, array('title', 'content', 'excerpt', 'category', 'tags')) || (in_array($key, array('title', 'content', 'excerpt', 'category', 'tags')) && !wpbdp_get_formfield($key) || (wpbdp_getv(wpbdp_get_formfield($key), 'id', null) == wpbdp_getv($field, 'id', -1)) ) ): ?>
						<option value="<?php echo $key; ?>" <?php echo wpbdp_getv($post_values, 'association', $field ? $field->association : '') == $key ? 'selected="true"' : ''; ?>>
							<?php echo $name; ?>
						</option>
						<?php endif; ?>
					<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</td>
			</tr>			
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php _ex('Field Type', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<select name="field[type]" id="field-type">
						<?php foreach ($api->getFieldTypes() as $key => $name) : ?>
							<option value="<?php echo $key; ?>" <?php echo wpbdp_getv($post_values, 'type', $field ? $field->type : '') == $key ? 'selected="true"' : ''; ?>>
									<?php echo $name; ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>			
			<?php
			$post_values_fielddata = (isset($_POST['field']) && isset($_POST['field']['field_data'])) ? $_POST['field']['field_data'] : array();
			?>			
			<tr>
				<th scope="row">
					<label> <?php _ex('Field Options (for select lists, radio buttons and checkboxes).', 'form-fields admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
				</th>
				<td>
					<span class="description">Comma (,) separated list of options</span> <br />
					<textarea name="field[field_data][options]" id="field-data-options" cols="50" rows="2"><?php echo wpbdp_getv($post_values_fielddata, 'options', $field && isset($field->field_data['options']) ? implode(',', $field->field_data['options']) : ''); ?></textarea>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<label> <?php _ex('Field description', 'form-fields admin', 'WPBDM'); ?> <span class="description">(optional)</span></label>
				</th>
				<td>
					<input name="field[description]"
						   type="text"
						   value="<?php echo wpbdp_getv($post_values, 'description', $field ? $field->description : ''); ?>" />
				</td>
			</tr>			
	</table>
	<h3><?php _ex('Field validation options', 'form-fields admin', 'WPBDM'); ?></h3>
	<table class="form-table">	
			<tr>
				<th scope="row">
					<label> <?php _ex('Field Validator', 'form-fields admin', 'WPBDM'); ?></label>
				</th>
				<td>
					<select name="field[validator]" id="field-validator">
						<option value=""><?php _ex('No validation', 'form-fields admin', 'WPBDM'); ?></label>
						<?php foreach ($api->getValidators() as $key => $name): ?>
						<option value="<?php echo $key; ?>" <?php echo wpbdp_getv($post_values, 'validator', $field ? $field->validator : '') == $key ? 'selected="true"' : ''; ?>>
							<?php echo $name; ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php _ex('Open link in a new window?', 'form-fields admin', 'WPBDM'); ?></label>
				</th>
				<td>
					<label>
						<input name="field[field_data][open_in_new_window]" id="field-data-open-in-new-window" value="1"
							   type="checkbox" <?php echo wpbdp_getv($post_values_fielddata, 'open_in_new_window', $field && isset($field->field_data['open_in_new_window']) && $field->field_data['open_in_new_window'] ? true : false) ? ' checked="checked"' : ''; ?>> <?php _ex('Open link in a new window.', 'form-fields admin', 'WPBDM'); ?>
					</label>
				</td>
			</tr>			
			<tr>
				<th scope="row">
					<label> <?php _ex('Is field required?', 'form-fields admin', 'WPBDM'); ?></label>
				</th>
				<td>
					<label>
						<input name="field[is_required]"
							   value="1"
							   type="checkbox" <?php echo wpbdp_getv($post_values, 'is_required', $field ? $field->is_required : false) ? 'checked="true"' : ''; ?>/> <?php _ex('This field is required.', 'form-fields admin', 'WPBDM'); ?>
					</label>
				</td>
			</tr>
	</table>
	<h3><?php _ex('Field display options', 'form-fields admin', 'WPBDM'); ?></h3>
	<?php
	$post_values_display = (isset($_POST['field']) && isset($_POST['field']['display_options'])) ? $_POST['field']['display_options'] : array();
	?>
	<table class="form-table">
			<tr>
				<th scope="row">
					<label> <?php _ex('Show this value in excerpt?', 'form-fields admin', 'WPBDM'); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_options][show_in_excerpt]"
							   value="1"
							   type="checkbox" <?php echo wpbdp_getv($post_values_display, 'show_in_excerpt', $field ? $field->display_options['show_in_excerpt'] : false) ? 'checked="true"' : ''; ?>/> <?php _ex('Display this value in post excerpt.', 'form-fields admin', 'WPBDM'); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php _ex('Hide this field from public?', 'form-fields admin', 'WPBDM'); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_options][hide_field]"
							   type="checkbox"
							   value="1" <?php echo wpbdp_getv($post_values_display, 'hide_field', $field ? $field->display_options['hide_field'] : false) ? 'checked="true"' : ''; ?>/> <?php _ex('Hide this field from public viewing.', 'form-fields admin', 'WPBDM'); ?></label>
				</td>
			</tr>			
	</table>

	<?php if ($field): ?>
		<?php echo submit_button(_x('Update Field', 'form-fields admin', 'WPBDM')); ?>
	<?php else: ?>
		<?php echo submit_button(_x('Add Field', 'form-fields admin', 'WPBDM')); ?>
	<?php endif; ?>
</form>

<?php
	echo wpbdp_admin_footer();
?>