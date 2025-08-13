<?php
/**
 * @package WPBDP/Templates/Admin/Form Fields Add or Edit.
 */

wpbdp_admin_header(
	array(
		'title' => esc_html__( 'Add Form Field', 'business-directory-plugin' ),
		'id'    => 'field-form',
		'echo'  => true,
	)
);

wpbdp_admin_notices();

?>

<form id="wpbdp-formfield-form" action="" method="post">
	<input type="hidden" name="field[id]" value="<?php echo esc_attr( $field->get_id() ); ?>" />
	<input type="hidden" name="field[tag]" value="<?php echo esc_attr( $field->get_tag() ); ?>" />
	<input type="hidden" name="field[weight]" value="<?php echo esc_attr( $field->get_weight() ); ?>" />
	<?php wp_nonce_field( 'editfield' ); ?>

	<table class="form-table">
		<tbody>
			<!-- association -->
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Field Association', 'business-directory-plugin' ); ?> <span class="description">(<?php esc_html_e( 'required', 'business-directory-plugin' ); ?>)</span></label>
				</th>
				<td>
					<?php $field_association_info = isset( $field_associations[ $field->get_association() ] ) ? $field_associations[ $field->get_association() ] : null; ?>
					<?php if ( $field_association_info && in_array( 'private', $field_association_info->flags, true ) ) : ?>
					<select name="field[association]" id="field-association">
						<option value="<?php echo esc_attr( $field_association_info->id ); ?>">
							<?php echo esc_html( $field_association_info->label ); ?>
						</option>
					</select>
					<?php else : ?>
					<select name="field[association]" id="field-association">
						<?php foreach ( $field_associations as &$association ) : ?>
							<?php
							if ( in_array( 'private', $association->flags, true ) ) {
								continue;}
							?>
						<option value="<?php echo esc_attr( $association->id ); ?>" <?php echo $field->get_association() == $association->id ? ' selected="selected"' : ''; ?> >
							<?php echo esc_html( $association->label ); ?>
						</option>
					<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</td>
			</tr>

			<!-- field type -->
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php esc_html_e( 'Field Type', 'business-directory-plugin' ); ?> <span class="description">(<?php esc_html_e( 'required', 'business-directory-plugin' ); ?>)</span></label>
				</th>
				<td>
					<select name="field[field_type]" id="field-type">
						<?php if ( 'custom' === $field->get_association() ) : ?>
						<option value="<?php echo esc_attr( $field->get_field_type_id() ); ?>">
							<?php echo esc_html( $field->get_field_type()->get_name() ); ?>
						</option>
						<?php else : ?>
							<?php foreach ( $field_types as $key => &$field_type ) : ?>
								<?php if ( ! in_array( $field->get_association(), $field_type->get_supported_associations() ) ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" disabled="disabled">
								<?php echo esc_html( $field_type->get_name() ); ?>
							</option>
							<?php else : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php echo $field->get_field_type() == $field_type ? 'selected="true"' : ''; ?>>
								<?php echo esc_html( $field_type->get_name() ); ?>
							</option>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<?php WPBDP_Admin_Education::show_tip( 'ratings' ); ?>
				</td>
			</tr>

			<!-- label -->
			<tr class="form-field form-required">
				<th scope="row">
					<label> <?php esc_html_e( 'Field Label', 'business-directory-plugin' ); ?> <span class="description">(<?php esc_html_e( 'required', 'business-directory-plugin' ); ?>)</span></label>
				</th>
				<td>
					<input name="field[label]" type="text" aria-required="true" value="<?php echo esc_attr( $field->get_label() ); ?>" />
				</td>
			</tr>

			<!-- description -->
			<tr class="form-field">
				<th scope="row">
					<label> <?php esc_html_e( 'Field description', 'business-directory-plugin' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'business-directory-plugin' ); ?>)</span></label>
				</th>
				<td>
					<input name="field[description]" type="text" value="<?php echo esc_attr( $field->get_description() ); ?> " />
				</td>
			</tr>
	</table>

	<!-- field-specific settings -->
	<?php
	$field_settings = $field->get_field_type()->render_field_settings( $field, $field->get_association() );
	ob_start();
	do_action_ref_array( 'wpbdp_form_field_settings', array( &$field, $field->get_association() ) );
	$field_settings .= ob_get_contents();
	ob_end_clean();
	?>
	<div id="wpbdp-fieldsettings" style="<?php echo $field_settings ? '' : 'display: none;'; ?>">
	<h2><?php esc_html_e( 'Field-specific settings', 'business-directory-plugin' ); ?></h2>
	<div id="wpbdp-fieldsettings-html">
		<?php echo $field_settings; ?>
	</div>
	</div>
	<!-- /field-specific settings -->

	<!-- validation -->
	<?php if ( ! $field->has_behavior_flag( 'no-validation' ) ) : ?>
	<h2><?php esc_html_e( 'Field validation options', 'business-directory-plugin' ); ?></h2>
	<table class="form-table">
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Field Validator', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<select name="field[validators][]" id="field-validator" <?php echo ( 'social-twitter' == $field->get_field_type_id() ? 'disabled="disabled"' : '' ); ?> ?>>
						<option value=""><?php esc_html_e( 'No validation', 'business-directory-plugin' ); ?></option>
						<?php foreach ( $validators as $key => $name ) : ?>
							<?php
							$disable_validator = 'url' == $field->get_field_type_id() && 'url' != $key;
							?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php echo in_array( $key, $field->get_validators(), true ) ? 'selected="selected"' : ''; ?> <?php echo $disable_validator ? 'disabled="disabled"' : ''; ?> ><?php echo $name; ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr id="wpbdp_word_count" style="<?php echo in_array( 'word_number', $field->get_validators() ) && in_array( $field->get_field_type()->get_id(), array( 'textfield', 'textarea' ) ) ? '' : 'display: none'; ?>">
				<th scope="row">
					<label><?php esc_html_e( 'Maximum number of words', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[word_count]" value="<?php echo esc_attr( $field->data( 'word_count' ) ? $field->data( 'word_count' ) : 0 ); ?>" type="number">
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Is field required?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[validators][]"
								value="required"
								type="checkbox" <?php echo $field->is_required() ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'This field is required.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
	</table>
	<?php endif; ?>

	<!-- display options -->
	<h2><?php esc_html_e( 'Field display options', 'business-directory-plugin' ); ?></h2>
	<table class="form-table">
		<tr class="form-field limit-categories <?php echo in_array( 'limit_categories', $hidden_fields ) ? 'wpbdp-hidden' : ''; ?>">
				<th scope="row">
					<label for="wpbdp-field-category-policy"><?php esc_html_e( 'Field Category Policy:', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<select id="wpbdp-field-category-policy"
							name="limit_categories">
						<option value="0"><?php esc_html_e( 'Field applies to all categories', 'business-directory-plugin' ); ?></option>
						<option value="1" <?php selected( is_array( $field->data( 'supported_categories' ) ), true ); ?> ><?php esc_html_e( 'Field applies to only certain categories', 'business-directory-plugin' ); ?></option>
					</select>

					<div id="limit-categories-list" class="<?php echo is_array( $field->data( 'supported_categories' ) ) ? '' : 'hidden'; ?>">
						<p><span class="description"><?php esc_html_e( 'Limit field to the following categories:', 'business-directory-plugin' ); ?></span></p>
						<?php
						$all_categories       = get_terms(
							array(
								'taxonomy'     => WPBDP_CATEGORY_TAX,
								'hide_empty'   => false,
								'hierarchical' => true,
							)
						);
						$supported_categories = is_array( $field->data( 'supported_categories' ) ) ? array_map( 'absint', $field->data( 'supported_categories' ) ) : array();

						if ( count( $all_categories ) <= 30 ) :
							foreach ( $all_categories as $category ) :
								?>
								<div class="wpbdp-category-item">
									<label>
										<input type="checkbox" name="field[supported_categories][]" value="<?php echo absint( $category->term_id ); ?>" <?php checked( in_array( (int) $category->term_id, $supported_categories ) ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</label>
								</div>
								<?php
							endforeach;
						else :
							?>
							<select name="field[supported_categories][]" multiple="multiple" placeholder="<?php esc_attr_e( 'Click to add categories to the selection.', 'business-directory-plugin' ); ?>">
								<?php foreach ( $all_categories as $category ) : ?>
									<option value="<?php echo absint( $category->term_id ); ?>" <?php selected( in_array( (int) $category->term_id, $supported_categories ) ); ?>>
										<?php echo esc_html( $category->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<?php
						endif;
						?>
					</div>
				</td>
			</tr>
			<tr id="wpbdp_private_field"
				class="<?php echo in_array( 'private_field', $hidden_fields, true ) && ! $field->display_in( 'private' ) ? 'wpbdp-hidden' : ''; ?>"
				>
				<th scope="row">
					<label> <?php esc_html_e( 'Show this field to admin users only?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_flags][]"
								value="private"
								type="checkbox" <?php echo $field->display_in( 'private' ) ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'Display this field to admin users only in the edit listing view.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Show this value in excerpt view?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_flags][]"
								value="excerpt"
								type="checkbox" <?php echo $field->display_in( 'excerpt' ) ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'Display this value in post excerpt view.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Show this value in listing view?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_flags][]"
								value="listing"
								type="checkbox" <?php echo $field->display_in( 'listing' ) ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'Display this value in the listing view.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label> <?php esc_html_e( 'Include this field in the search form?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[display_flags][]"
								value="search"
								type="checkbox" <?php echo $field->display_in( 'search' ) ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'Include this field in the search form.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
			<tr class="if-display-in-search
				<?php echo in_array( 'search', $hidden_fields, true ) && ! in_array( 'required-in-search', $field->get_validators(), true ) ? ' wpbdp-hidden' : ''; ?>
				">
				<th scope="row">
					<label> <?php esc_html_e( 'Is this field required for searching?', 'business-directory-plugin' ); ?></label>
				</th>
				<td>
					<label>
						<input name="field[validators][]"
								value="required-in-search"
								type="checkbox" <?php echo in_array( 'required-in-search', $field->get_validators() ) ? 'checked="checked"' : ''; ?>/>
						<?php esc_html_e( 'Require this field on the Advanced Search screen.', 'business-directory-plugin' ); ?>
					</label>
				</td>
			</tr>
			<?php
			if ( has_action( 'wpbdp_admin_listing_field_section_visibility' ) ) {
				do_action( 'wpbdp_admin_listing_field_section_visibility', $field, $hidden_fields );
			} else {
				?>
				<tr class="<?php echo in_array( 'nolabel', $hidden_fields, true ) && ! $field->has_display_flag( 'nolabel' ) ? 'wpbdp-hidden' : ''; ?>">
					<th scope="row">
						<label> <?php esc_html_e( 'Hide this field\'s label?', 'business-directory-plugin' ); ?></label>
					</th>
					<td>
						<label>
							<input name="field[display_flags][]"
								value="nolabel"
								type="checkbox" <?php echo $field->has_display_flag( 'nolabel' ) ? 'checked="checked"' : ''; ?>/> <?php esc_html_e( 'Hide this field\'s label when displaying it.', 'business-directory-plugin' ); ?>
						</label>
					</td>
				</tr>
			<?php } ?>
	</table>

	<!-- display options -->
	<h2><?php esc_html_e( 'Field privacy options', 'business-directory-plugin' ); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row">
				<label> <?php esc_html_e( 'This field contains sensitive or private information?', 'business-directory-plugin' ); ?></label>
			</th>
			<td>
				<label>
					<input name="field[display_flags][]"
							value="privacy"
							type="checkbox" <?php echo $field->is_privacy_field() || $field->has_display_flag( 'privacy' ) ? 'checked="checked"' : ''; ?>
							<?php echo $field->is_privacy_field() ? 'disabled' : ''; ?>
					/> <?php esc_html_e( 'Add this field when exporting or deleting user\'s personal data.', 'business-directory-plugin' ); ?>
				</label>
			</td>
		</tr>
	</table>

	<?php do_action( 'wpbdp_admin_listing_field_after_settings', $field, $hidden_fields ); ?>

	<?php if ( $field->get_id() ) : ?>
		<?php submit_button( _x( 'Update Field', 'form-fields admin', 'business-directory-plugin' ) ); ?>
	<?php else : ?>
		<?php submit_button( _x( 'Add Field', 'form-fields admin', 'business-directory-plugin' ) ); ?>
	<?php endif; ?>
</form>

<script>
document.addEventListener( 'DOMContentLoaded', function () {
	WPBDP_associations_fieldtypes = <?php echo json_encode( $association_field_types ); ?>
}, false );
</script>

<?php echo wpbdp_admin_footer(); ?>
