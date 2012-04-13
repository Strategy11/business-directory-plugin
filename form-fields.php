<?php
/*
 * Form fields generic API
 */

if (!class_exists('WPBDP_FormFieldsAPI')) {

class WPBDP_Validator {

}

class WPBDP_FormFieldsAPI {

	public function __construct() {	}

	private function normalizeField(&$field) {
		$field->display_options = array_merge(array('hide_field' => false, 'show_in_excerpt' => false), $field->display_options ? unserialize($field->display_options) : array());
		$field->field_data = $field->field_data ? unserialize($field->field_data) : null;
	}

	public function getField($id) {
		global $wpdb;

		$field = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields WHERE id = %d", $id));
		$this->normalizeField($field);

		return $field;
	}

	public function getFormFields() {
		global $wpdb;

		$fields = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpbdp_form_fields ORDER BY weight DESC");
		
		foreach ($fields as &$field)
			$this->normalizeField($field);

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
			// no more than 1 field associated with title, content, category or tags
			$association = $field['association'];

			if (in_array($association, array('title', 'content', 'category', 'tags'))) {
				if ($field_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wpbdp_form_fields WHERE association = %s", $association))) {
					if (!isset($field['id']) || $field['id'] != $field_id) {
						$errors[] = sprintf(_x('There can only be one field with association "%s". Please select another association.', 'form-fields-api', 'WPBDM'), $this->getFieldAssociations($association));
					}
				}
			}

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

		if (isset($field['validator']) && !empty($field['validator']) && !in_array($field['validator'], array_keys($this->getValidators())))
			$errors[] = _x('Invalid validator selected.', 'form-fields-api', 'WPBDM');			

		if ($errors)
			return false;

		return true;
	}

	public function addorUpdateField($field=array(), &$errors=null) {
		$errors = array();

		if ($this->isValidField($field, &$errors)) {
			return true;
		}

		return false;
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

}

}