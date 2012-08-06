<?php
/*
 * Form fields generic API
 */

if (!class_exists('WPBDP_FormFieldsAPI')) {

class WPBDP_FormFieldValidators {

	/* required */
	public static function required($value) {
		if (is_array($value))
			return !empty($value);

		$value = trim($value);

		if (!$value || empty($value))
			return false;

		return true;
	}

	public static function required_msg($label, $value=null) {
		return sprintf(_x('%s is required.', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

	/* URLValidator */
	public static function URLValidator($value) {
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $value);
	}

	public static function URLValidator_msg($label, $value=null) {
		return sprintf(_x('%s is badly formatted. Valid URL format required. Include http://', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

	/* EmailValidator */
	public static function EmailValidator($value) {
		return wpbusdirman_isValidEmailAddress($value);
	}

	public static function EmailValidator_msg($label, $value=null) {
		return sprintf(_x('%s is badly formatted. Valid Email format required.', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

	/* IntegerNumberValidator */
	public static function IntegerNumberValidator($value) {
		return ctype_digit($value);
	}

	public static function IntegerNumberValidator_msg($label, $value=null) {
		return sprintf(_x('%s must be a number. Decimal values are not allowed.', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

	/* DecimalNumberValidator */
	public static function DecimalNumberValidator($value) {
		return is_numeric($value);
	}

	public static function DecimalNumberValidator_msg($label, $value=null) {
		return sprintf(_x('%s must be a number.', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

	/* DateValidator */
	public static function DateValidator($value) {
		return wpbusdirman_is_ValidDate($value);
	}

	public static function DateValidator_msg($label, $value=null) {
		return sprintf(_x('%s must be in the format 00/00/0000.', 'form-fields-api validation', 'WPBDM'), esc_attr($label));
	}

}


class WPBDP_FormFieldsAPI {

	public function __construct() {	}

	public static function getDefaultFields() {
		return array(
			'title' => array(
				'label' => __("Business Name","WPBDM"),
				'type' => 'textfield',
				'association' => 'title',
				'weight' => 9,
				'is_required' => true,
				'display_options' => array('show_in_excerpt' => true)
			),
			'category' => array(
						'label' => __("Business Genre","WPBDM"),
						'type' => 'select',
						'association' => 'category',
						'weight' => 8,
						'is_required' => true,
						'display_options' => array('show_in_excerpt' => true)
					),
			'excerpt' => array(
					'label' => __("Short Business Description","WPBDM"),
					'type' => 'textarea',
					'association' => 'excerpt',
					'weight' => 7
				),
			'content' => array(
					'label' => __("Long Business Description","WPBDM"),
					'type' => 'textarea',
					'association' => 'content',
					'weight' => 6,
					'is_required' => true
				),
			'meta0' => array(
					'label' => __("Business Website Address","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 5,
					'validator' => 'URLValidator',
					'display_options' => array('show_in_excerpt' => true)
				),
			'meta1' => array(
					'label' => __("Business Phone Number","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 4,
					'display_options' => array('show_in_excerpt' => true)
				),
			'meta2' => array(
					'label' => __("Business Fax","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 3
				),
			'meta3' => array(
					'label' => __("Business Contact Email","WPBDM"),
					'type' => 'textfield',
					'association' => 'meta',
					'weight' => 2,
					'validator' => 'EmailValidator',
					'is_required' => true
				),
			'meta4' => array(
					'label' => __("Business Tags","WPBDM"),
					'type' => 'textfield',
					'association' => 'tags',
					'weight' => 1
				)	
		);		
	}

	public function getShortNames() {
		if ($names = get_option('wpbdp-field-short-names', false)) {
			return $names;
		}

		return $this->calculateShortNames();
	}

	private function calculateShortNames() {
		$fields = $this->getFields();
		$names = array();

		foreach ($fields as $field) {
			$name = strtolower($field->label);
			$name = str_replace(array(',', ';'), '', $name);
			$name = str_replace(array(' ', '/'), '-', $name);

			if ($name == 'images' || $name == 'image' || $name == 'username' || in_array($name, $names)) {
				$name = $field->id . '/' . $name;
			}
			
			$names[$field->id] = $name;
		}

		update_option('wpbdp-field-short-names', $names);

		return $names;
	}

	private function normalizeField(&$field) {
		$field->display_options = array_merge(array('hide_field' => false, 'show_in_excerpt' => false), $field->display_options ? (array) unserialize($field->display_options) : array());
		$field->field_data = $field->field_data ? unserialize($field->field_data) : null;

		if (isset($field->field_data['options']) && !is_array($field->field_data['options']))
			$field->field_data['options'] = null;
	}

	public function getField($id) {
		global $wpdb;

		if ($field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $id))) {
			$this->normalizeField($field);
			return $field;
		}

		return null;
	}

	public function getFields() {
		return $this->getFormFields();
	}

	public function getFormFields() {
		global $wpdb;

		$fields = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC");
		
		foreach ($fields as &$field)
			$this->normalizeField($field);

		return $fields;
	}

	public function getFieldsByAssociation($association, $single=false) {
		global $wpdb;

		$fields = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE association = %s ORDER BY weight DESC", $association) );
		
		foreach ($fields as &$field)
			$this->normalizeField($field);

		if ($single) {
			if ($fields)
				return $fields[0];

			return null;
		}

		return $fields;
	}

	public function getFieldsByValidator($validator, $single=false) {
		global $wpdb;

		$fields = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE validator = %s ORDER BY weight DESC", $validator) );
		
		foreach ($fields as &$field)
			$this->normalizeField($field);

		if ($single) {
			if ($fields)
				return $fields[0];

			return null;
		}

		return $fields;
	}	

	public function getFieldTypes($key=null) {
		$types = array('textfield' => _x('Textfield', 'form-fields api', 'WPBDM'),
					   'select' => _x('Select List', 'form-fields api', 'WPBDM'),
		 			   'textarea' => _x('Textarea', 'form-fields api', 'WPBDM'),
					   'radio' => _x('Radio button', 'form-fields api', 'WPBDM'),
					   'multiselect' => _x('Multiple select list', 'form-fields api', 'WPBDM'),
					   'checkbox' => _x('Checkbox', 'form-fields api', 'WPBDM'));

		if ($key)
			return $types[$key];

		return $types;
	}

	public function getFieldAssociations($key=null) {
		$associations = array('title' => _x('Post Title', 'form-fields api', 'WPBDM'),
							  'content' => _x('Post Content', 'form-fields api', 'WPBDM'),
							  'category' => _x('Post Category', 'form-fields api', 'WPBDM'),
							  'excerpt' => _x('Post Excerpt', 'form-fields api', 'WPBDM'),
							  'meta' => _x('Post Metadata', 'form-fields api', 'WPBDM'),
							  'tags' => _x('Post Tags', 'form-fields api', 'WPBDM'));

		if ($key)
			return $associations[$key];

		return $associations;
	}

	public function getValidators($key=null) {
		$validators = array(
			'EmailValidator' => _x('Email Validator', 'form-fields-api', 'WPBDM'),
			'URLValidator' => _x('URL Validator', 'form-fields-api', 'WPBDM'),
			'IntegerNumberValidator' => _x('Whole Number Validator', 'form-fields-api', 'WPBDM'),
			'DecimalNumberValidator' => _x('Decimal Number Validator', 'form-fields-api', 'WPBDM'),
			'DateValidator' => _x('Date Validator', 'form-fields-api', 'WPBDM')
		);

		if ($key)
			return $validators[$key];

		return $validators;
	}

	public function validate($field, $value, &$errors=null) {
		$errors = array();

		if ($field->is_required && !WPBDP_FormFieldValidators::required($value))
			$errors[] = WPBDP_FormFieldValidators::required_msg($field->label, $value);

		if (!empty($value)) {
			if ($field->association == 'category') {
				$categories = is_array($value) ? $value : array($value);

				foreach ($categories as $catid) {
					if (get_term_by('id', $catid, wpbdp_categories_taxonomy()) == false) {
						$errors[] = _x('Please select a valid category.', 'form-fields-api', 'WPBDM');
						return false;
					}
				}
			}

			if (is_array($value))
				return true; // TODO: check selected options in select/multiselect/radio/checkbox are valid

			if ($field->validator && !call_user_func('WPBDP_FormFieldValidators::' . $field->validator, $value))
				$errors[] = call_user_func('WPBDP_FormFieldValidators::' . $field->validator . '_msg', $field->label, $value);
		}

		if ($errors)
			return false;

		return true;
	}

	public function validate_value($validatorname, $value, &$errors=null) {
		$errors = array();

		if (!call_user_func('WPBDP_FormFieldValidators::' . $validatorname, $value)) {
			$errors[] = call_user_func('WPBDP_FormFieldValidators::' . $validatorname . '_msg', $value);
		}

		if ($errors)
			return false;

		return true;
	}

	public function extract($listing, $field) {
		if (is_object($field))
			return $this->extract($listing, $field->id);

		if (is_string($field)) {
			if ($fieldobj = $this->getFieldsByAssociation($field, true))
				return $this->extract($listing, $fieldobj);
		}

		if ($field) {
			return wpbdp_getv($listing, $field, null);
		}

		return null;
	}

	/* Field handling */
	public function reorderField($id, $delta) {
		global $wpdb;

		$field = $this->getField($id);

		if ($delta > 0) {
			$fields = $wpdb->get_results($wpdb->prepare("SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight >= %d ORDER BY weight ASC", $field->weight));

			if ($fields[count($fields) - 1]->id == $field->id)
				return;

			for ($i = 0; $i < count($fields); $i++) {
				$fields[$i]->weight = intval($field->weight) + $i;

				if ($fields[$i]->id == $field->id) {
					$fields[$i]->weight += 1;
					$fields[$i+1]->weight -= 1;
					$i += 1;
				} 
			}

			foreach ($fields as &$f) {
				$wpdb->update("{$wpdb->prefix}wpbdp_form_fields", array('weight' => $f->weight), array('id' => $f->id));
			}
		} else {
			$fields = $wpdb->get_results($wpdb->prepare("SELECT id, weight FROM {$wpdb->prefix}wpbdp_form_fields WHERE weight <= %d ORDER BY weight ASC", $field->weight));

			if ($fields[0]->id == $field->id)
				return;

			foreach ($fields as $i => $f) {
				if ($f->id == $field->id) {
					$this->reorderField($fields[$i-1]->id, 1);
					return;
				}
			}
		}
	}

	public function isValidField($field=array(), &$errors=null) {
		global $wpdb;

		if (!is_array($errors)) $errors = array();

		if (!isset($field['label']) || trim($field['label']) == '')
			$errors[] = _x('Field label is required.', 'form-fields-api', 'WPBDM');

		if (!isset($field['type']) || !in_array($field['type'], array_keys($this->getFieldTypes())))
			$errors[] = _x('Invalid field type.', 'form-fields-api', 'WPBDM');

		if (!isset($field['association']) || !in_array($field['association'], array_keys($this->getFieldAssociations()))) {
			$errors[] = _x('Invalid field association.', 'form-fields-api', 'WPBDM');
		} else {
			// no more than 1 field associated with title, content, excerpt, category or tags
			$association = $field['association'];

			if (in_array($association, array('title', 'content', 'category', 'excerpt', 'tags'))) {
				if ($field_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpbdp_form_fields WHERE association = %s", $association))) {
					if (!isset($field['id']) || $field['id'] != $field_id) {
						$errors[] = sprintf(_x('There can only be one field with association "%s". Please select another association.', 'form-fields-api', 'WPBDM'), $this->getFieldAssociations($association));
					}
				}
			}

			// TODO: title, category and all of the 'required' fields must have is_required = 1

			// title must be textfield or textarea
			if ($field['association'] == 'title' && !in_array($field['type'], array('textfield', 'textarea')))
				$errors[] = _x('Post title field must be a text field or text area.', 'form-fields-api', 'WPBDM');

			// content must be a textarea
			if ($field['association'] == 'content' && $field['type'] != 'textarea')
				$errors[] = _x('Post content field must be a text area.', 'form-fields-api', 'WPBDM');

			// category can't be a textfield or textarea
			if ($field['association'] == 'category' && !in_array($field['type'], array('radio', 'select', 'multiselect', 'checkbox')))
				$errors[] = _x('Post category field can\'t be a text field or text area.', 'form-fields-api', 'WPBDM');
		}

		if (isset($field['validator']) && !empty($field['validator'])) {
			if (!in_array($field['validator'], array_keys($this->getValidators())))
				$errors[] = _x('Invalid validator selected.', 'form-fields-api', 'WPBDM');

			if ($field['validator'] == 'EmailValidator') {
				if ($email_field = $this->getFieldsByValidator('EmailValidator', true)) {
					if (!isset($field['id']) || $field['id'] != $email_field->id)
						$errors[] = _x('You already have a field using the email validation. At this time the system will allow only 1 valid email field. Change the validation for that field to something else then try again.', 'form-fields-api', 'WPBDM');
				}
			}
		}

		if ($errors)
			return false;

		return true;
	}

	public function addorUpdateField($field_=array(), &$errors=null) {
		global $wpdb;

		$errors = array();

		$field = $field_;
		if (isset($field['field_data'])) {
			if (isset($field['field_data']['options']) && $field['field_data']['options']) {
				$field['field_data']['options'] = explode(',', $field['field_data']['options']);

				// sanitize options
				$field['field_data']['options'] = array_map('trim', $field['field_data']['options']);
				$field['field_data']['options'] = array_map('stripslashes', $field['field_data']['options']);
			}

			if (isset($field['field_data']['open_in_new_window']))
				$field['field_data']['open_in_new_window'] = intval($field['field_data']['open_in_new_window']) > 0 ? true : false;

			$field['field_data'] = serialize($field['field_data']);
		} else {
			$field['field_data'] = null;
		}


		if (isset($field['is_required'])) {
			$field['is_required'] = intval($field['is_required']);
		} else {
			$field['is_required'] = 0;
		}

		if (in_array($field['association'], array('title', 'category', 'content')))
			$field['is_required'] = 1;

		if (isset($field['display_options'])) {
			if (isset($field['display_options']['show_in_excerpt']))
				$field['display_options']['show_in_excerpt'] = intval($field['display_options']['show_in_excerpt']);

			if (isset($field['display_options']['hide_field']))
				$field['display_options']['hide_field'] = intval($field['display_options']['hide_field']);			

			$field['display_options'] = serialize($field['display_options']);
		} else {
			$field['display_options'] = null;
		}

		$success = false;

		if ($this->isValidField($field, $errors)) {
			if (isset($field['id'])) {
				$success = $wpdb->update("{$wpdb->prefix}wpbdp_form_fields", $field, array('id' => $field['id'])) !== false;
			} else {
				$success = $wpdb->insert("{$wpdb->prefix}wpbdp_form_fields", $field);
			}
		}

		$this->calculateShortNames();

		return $success;
	}

	public function deleteField($id, &$errors=null) {
		if (is_object($id)) return $this->deleteField((array) $id);
		if (is_array($id)) return $this->deleteField($id['id']);

		global $wpdb;

		$errors = array();

		$field = $this->getField($id);

		if (!in_array($field->association, array('title', 'category', 'content'))) {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $field->id));
			return true;
		} else {
			$errors[] = _x("This form field can't be deleted because it is required for the plugin to work.", 'form-fields api', 'WPBDM');
		}

		if ($errors)
			return false;

		return true;
	}

	/* Field rendering */
	public function render(&$field, $value=null, $output=false) {
		if ($output) {
			echo $this->render($field, $value, false);
			return;
		}

		$args = func_get_args();
		
		$html  = '';
		$html .= sprintf('<div class="wpbdp-form-field %s %s">', $field->type, $field->is_required ? 'required' : '');
		$html .= call_user_func(array($this, 'render_' . $field->type), $field, $value); 
		$html .= '</div>';
		return $html;
	}

	public function render_textfield(&$field, $value=null) {
		$html = '';
		$html .= sprintf('<p class="wpbdmp"><label for="%s">%s</label>',
						 'wpbdp-field-' . $field->id,
						 esc_attr($field->label));

		if ($field->validator == 'DateValidator')
			$html .= _x('Format 01/31/1969', 'form-fields api', 'WPBDM');

		$html .= '</p>';
		$html .= sprintf( '<input type="text" id="%s" name="%s" class="intextbox %s" value="%s" />',
						'wpbdp-field-' . $field->id,
						'listingfields[' . $field->id . ']',
						$field->is_required ? 'inselect required' : 'inselect',
						esc_attr($value) );

		return $html;
	}
	
	public function render_select(&$field, $value=null, $multiselect=false) {
		if (!is_array($value))	
			return $this->render_select($field, explode("\t", $value), $multiselect);

		$html = '';
		$html .= sprintf('<p class="wpbdmp"><label for="%s">%s</label></p>',
						 'wpbdp-field-' . $field->id,
						 esc_attr($field->label));

		if ($value) {
			if (!$multiselect) $value = array($value[0]);
			$value = array_map('trim', $value);	
		} else {
			$value = array();
		}

		if ($field->association == 'category') {
				$html .= wp_dropdown_categories( array(
						'taxonomy' => wpbdp()->get_post_type_category(),
						'show_option_none' => _x('Choose One', 'form-fields-api category-select', 'WPBDM'),
						'orderby' => 'name',
						'selected' => $multiselect ? null : $value[0],
						'order' => 'ASC',
						'hide_empty' => 0,
						'hierarchical' => 1,
						'echo' => 0,
						'id' => 'wpbdp-field-' . $field->id,
						'name' => 'listingfields[' . $field->id . ']',
						'class' => $field->is_required ? 'inselect required' : 'inselect'
					) );
			
			if ($multiselect) {
				$html = preg_replace("/\\<select(.*)name=('|\")(.*)('|\")(.*)\\>/uiUs",
									 "<select name=\"$3[]\" multiple=\"multiple\" $1 $5>",
									 $html);

				if ($value) {
					foreach ($value as $catid) {
						$html = preg_replace("/\\<option(.*)value=('|\"){$catid}('|\")(.*)\\>/uiU",
											 "<option value=\"{$catid}\" selected=\"selected\" $1 $4>",
											 $html);
					}
				}
			}
		} else {
			$html .= sprintf('<select id="%s" name="%s" %s class="%s %s">',
							'wpbdp-field-' . $field->id,
							'listingfields[' . $field->id . ']' . ($multiselect ? '[]' : ''),
							$multiselect ? 'multiple="multiple"' : '',
							$multiselect ? 'inselectmultiple' : 'inselect',
							$field->is_required ? 'required' : '');

			if (isset($field->field_data['options'])) {
				foreach ($field->field_data['options'] as $option) {
					$html .= sprintf('<option value="%s" %s>%s</option>', esc_attr($option), in_array($option, $value) ? 'selected="selected"' : '', esc_attr($option));
				}
			}
		
			$html .= '</select>';
		}

		return $html;
	}

	public function render_textarea(&$field, $value=null) {
		$html = '';

		$html .= sprintf('<p class="wpbdmp"><label for="%s">%s</label></p>',
						 'wpbdp-field-' . $field->id,
						 esc_attr($field->label));
		$html .= sprintf('<textarea id="%s" name="%s" class="intextarea %s">%s</textarea>',
						 'wpbdp-field-' . $field->id,
						 'listingfields[' . $field->id . ']',
						 $field->is_required ? 'required' : '',
						 $value ? esc_attr($value) : '' );

		return $html;
	}

	public function render_radio(&$field, $value=null) {
		$html = '';

		$html .= sprintf('<p class="wpbdmp"><label>%s</label></p>', esc_attr($field->label));

		if ($field->association == 'category') {
			$terms = get_terms(wpbdp()->get_post_type_category(), 'hide_empty=0');

			foreach ($terms as $term) {
				$html .= sprintf('<span style="padding-right: 10px;"><input type="radio" name="%s" class="%s" value="%s" %s />%s</span>',
								  'listingfields[' . $field->id . ']',
								  $field->is_required ? 'inradio required' : 'inradio',
								  $term->term_id,
								  $value == $term->term_id ? 'checked="checked"' : '',
								  esc_attr($term->name)
								 );				
			}
		} else {
			if (isset($field->field_data['options'])) {
					foreach ($field->field_data['options'] as $option) {
						$html .= sprintf('<span style="padding-right: 10px;"><input type="radio" name="%s" class="%s" value="%s" %s />%s</span>',
										  'listingfields[' . $field->id . ']',
										  $field->is_required ? 'inradio required' : 'inradio',
										  $option,
										  $value == $option ? 'checked="checked"' : '',
										  esc_attr($option)
										 );
					}
				}
		}

		return $html;
	}

	public function render_multiselect(&$field, $value=null) {
		if (is_string($value))
			return $this->render_multiselect($field, explode("\t", $value));

		return $this->render_select($field, $value, true);
	}

	public function render_checkbox(&$field, $value=null) {
		if (is_string($value))
			return $this->render_checkbox($field, explode("\t", $value));

		$html = '';
		$html .= sprintf('<p class="wpbdmp"><label for="%s">%s</label></p>',
						 'wpbdp-field-' . $field->id,
						 esc_attr($field->label)
						);

		$value = is_array($value) ? $value : array();
		$value = array_map('trim', $value);

		$options = array();
		if ($field->association == 'category') {
			$terms = get_terms(wpbdp()->get_post_type_category(), 'hide_empty=0');

			foreach ($terms as $term)
				$options[] = array($term->term_id, $term->name);
		} else {
			$options = isset($field->field_data['options']) ? $field->field_data['options'] : array();
		}

		if ($options) {
			foreach ($options as $option) {
				$html .= sprintf('<div class="wpbdmcheckboxclass"><input type="checkbox" class="%s" name="%s" value="%s" %s/> %s</div>',
								 $field->is_required ? 'required' : '',
								 'listingfields[' . $field->id . '][]',
								 is_array($option) ? $option[0] : $option,
								 in_array(is_array($option) ? $option[0] : $option, $value) ? 'checked="checked"' : '',
								 esc_attr(is_array($option) ? $option[1] : $option));
			}
		}

		$html .= '<div style="clear:both;"></div>';

		return $html;
	}

	/*
	 * Upgrade & compat
	 */

	public function _update_to_2_1() {
		global $wpdb;

		static $pre_2_1_types = array(null, 'textfield', 'select', 'textarea', 'radio', 'multiselect', 'checkbox');
		static $pre_2_1_validators = array(
			'email' => 'EmailValidator',
			'url' => 'URLValidator',
			'missing' => null, /* not really used */
			'numericwhole' => 'IntegerNumberValidator',
			'numericdeci' => 'DecimalNumberValidator',
			'date' => 'DateValidator'
		);
		static $pre_2_1_associations = array(
			'title' => 'title',
			'description' => 'content',
			'category' => 'category',
			'excerpt' => 'excerpt',
			'meta' => 'meta',
			'tags' => 'tags'
		);

		$field_count = $wpdb->get_var(
			sprintf("SELECT COUNT(*) FROM {$wpdb->prefix}options WHERE option_name LIKE '%%%s%%'", 'wpbusdirman_postform_field_label'));

		for ($i = 1; $i <= $field_count; $i++) {
			$label = get_option('wpbusdirman_postform_field_label_' . $i);
			$type = get_option('wpbusdirman_postform_field_type_'. $i);
			$validation = get_option('wpbusdirman_postform_field_validation_'. $i);
			$association = get_option('wpbusdirman_postform_field_association_'. $i);
			$required = strtolower(get_option('wpbusdirman_postform_field_required_'. $i));
			$show_in_excerpt = strtolower(get_option('wpbusdirman_postform_field_showinexcerpt_'. $i));
			$hide_field = strtolower(get_option('wpbusdirman_postform_field_hide_'. $i));
			$options = get_option('wpbusdirman_postform_field_options_'. $i);

			$newfield = array();
			$newfield['label'] = $label;
			$newfield['type'] = wpbdp_getv($pre_2_1_types, intval($type), 'textfield');
			$newfield['validator'] = wpbdp_getv($pre_2_1_validators, $validation, null);
			$newfield['association'] = wpbdp_getv($pre_2_1_associations, $association, 'meta');
			$newfield['is_required'] = $required == 'yes' ? true : false;
			$newfield['display_options'] = serialize(
				array('show_in_excerpt' => $show_in_excerpt == 'yes' ? true : false,
					  'hide_field' => $hide_field == 'yes' ? true : false)
			);
			$newfield['field_data'] = $options ? serialize(array('options' => explode(',', $options))) : null;

			if ($wpdb->insert($wpdb->prefix . 'wpbdp_form_fields', $newfield)) {
				delete_option('wpbusdirman_postform_field_label_' . $i);
				delete_option('wpbusdirman_postform_field_type_' . $i);
				delete_option('wpbusdirman_postform_field_validation_' . $i);
				delete_option('wpbusdirman_postform_field_association_' . $i);
				delete_option('wpbusdirman_postform_field_required_' . $i);
				delete_option('wpbusdirman_postform_field_showinexcerpt_' . $i);
				delete_option('wpbusdirman_postform_field_hide_' . $i);
				delete_option('wpbusdirman_postform_field_options_' . $i);
				delete_option('wpbusdirman_postform_field_order_' . $i);
			}
		}
	}

	public function _update_to_2_4() {
		global $wpdb;

		$fields = $this->getFields();

		foreach ($fields as &$field) {
			$query = $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s AND {$wpdb->postmeta}.post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s)",
									'_wpbdp[fields][' . $field->id . ']', $field->label, wpbdp_post_type());
			$wpdb->query($query);
		}
	}

	public function check_for_required_fields() {
		static $required_associations = array('title', 'category');

		$errors = array();

		foreach ($required_associations as $field_assoc) {
			if (!($field = $this->getFieldsByAssociation($field_assoc, true))) {
				$errors[] = $field_assoc;
			}
		}

		return $errors;
	}

}

}