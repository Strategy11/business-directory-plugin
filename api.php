<?php
/*
 * Plugin API
 */

function wpbdp() {
	global $wpbdp;
	return $wpbdp;
}

function wpbdp_get_version() {
	return wpbdp()->get_version();
}

function wpbdp_get_db_version() {
	return wpbdp()->get_db_version();
}

function wpbdp_get_page_id($name='main') {
	global $wpdb;

	static $shortcodes = array(
		'main' => 'WPBUSDIRMANUI',
		'add-listing' => 'WPBUSDIRMANADDLISTING',
		'manage-listings' => 'WPBUSDIRMANMANAGELISTING',
		'view-listings' => 'WPBUSDIRMANMVIEWLISTINGS',
		'paypal' => 'WPBUSDIRMANPAYPAL',
		'2checkout' => 'WPBUSDIRMANTWOCHECKOUT',
		'googlecheckout' => 'WPBUSDIRMANGOOGLECHECKOUT'
	);

	return $wpdb->get_var(sprintf("SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%%[%s]%%' AND post_status = 'publish' AND post_type = 'page'", $shortcodes[$name]));
}

function wpbusdirman_gpid() {
	return wpbdp_get_page_id('main');
}

function wpbdp_get_page_link($name='main') {
	$main_page_id = wpbdp_get_page_id('main');
	$page_id = wpbdp_get_page_id($name);

	if ($page_id)
		return get_permalink($page_id);

	if ($name == 'view-listings')
		return add_query_arg('action', 'viewlistings', get_permalink($main_page_id));

	if ($name == 'add-listing')
		return add_query_arg('action', 'submitlisting', get_permalink($main_page_id));
}

/* Admin API */
function wpbdp_admin() {
	return wpbdp()->admin;
}

function wpbdp_admin_notices() {
	wpbdp_admin()->admin_notices();
}

/* Settings API */
function wpbdp_settings_api() {
	global $wpbdp;
	return $wpbdp->settings;
}

function wpbdp_get_option($key, $def=null) {
	global $wpbdp;
	return $wpbdp->settings->get($key, $def);
}

function wpbdp_set_option($key, $value) {
	global $wpbdp;
	return $wpbdp->settings->set($key, $value);
}

/* Form Fields API */
function wpbdp_formfields_api() {
	global $wpbdp;
	return $wpbdp->formfields;
}

function wpbdp_get_formfields() {
	return wpbdp_formfields_api()->getFields();
}

function wpbdp_get_formfield($id) {
	if (is_string($id))
		return wpbdp_formfields_api()->getFieldsByAssociation($id, true);

	return wpbdp_formfields_api()->getField($id);
}

/* Listings */
function wpbdp_get_listing_field_value($listing, $field) {
	$listing = !is_object($listing) ? get_post($listing) : $listing;
	$field = !is_object($field) ? wpbdp_get_formfield($field) : $field;

	if ($listing && $field) {
		switch ($field->association) {
			case 'title':
				return $listing->post_title;
				break;
			case 'excerpt':
				return $listing->post_excerpt;
				break;
			case 'content':
				return $listing->post_content;
				break;
			case 'category':
				return get_the_terms($listing->ID, wpbdp()->get_post_type_category());
				break;
			case 'tags':
				return get_the_terms($listing->ID, wpbdp()->get_post_type_tags());
				break;
			case 'meta':
			default:
				return get_post_meta($listing->ID, $field->label, true);
				break;
		}
	}

	return null;
}

function wpbdp_get_listing_field_html_value($listing, $field) {
	$listing = !is_object($listing) ? get_post($listing) : $listing;
	$field = !is_object($field) ? wpbdp_get_formfield($field) : $field;

	if ($listing && $field) {
		switch ($field->association) {
			case 'title':
				return sprintf('<a href="%s">%s</a>', get_permalink($listing->ID), get_the_title($listing->ID));
				break;
			case 'excerpt':
				return apply_filters('get_the_excerpt', $listing->post_excerpt);
				break;
			case 'content':
				return apply_filters('the_content', $listing->post_content);
				break;
			case 'category':
				return get_the_term_list($listing->ID, wpbdp()->get_post_type_category(), '', ', ', '' );
				break;
			case 'tags':
				return get_the_term_list($listing->ID, wpbdp()->get_post_type_tags(), '', ', ', '' );
				break;
			case 'meta':
			default:
				$value = wpbdp_get_listing_field_value($listing, $field);

				if ($value) {
					if (in_array($field->type, array('multiselect', 'checkbox'))) {
						return esc_attr(str_replace("\t", ', ', $value));
					} else {
						if ($field->validator == 'URLValidator')
							return sprintf('<a href="%s" rel="no follow">%s</a>', esc_url($value), esc_url($value));

						return esc_attr(wpbdp_get_listing_field_value($listing, $field));
					}
				}

				break;
		}
	}

	return  null;
}

function wpbdp_format_field_output($field, $value='', $listing=null) {
	$field = !is_object($field) ? wpbdp_get_formfield($field) : $field;
	$value = $listing ? wpbdp_get_listing_field_html_value($listing, $field) : $value;

	if ($field->validator == 'EmailValidator' && !wpbdp_get_option('override-email-blocking'))
		return '';

	if ($field && !$field->display_options['hide_field'] && $value)
		return sprintf('<p class="field-value %s %s"><label>%s</label>: %s',
					   strtolower(str_replace(' ', '', $field->label)), /* normalized field label */
					   $field->association,
					   esc_attr($field->label),
					   $value);
}