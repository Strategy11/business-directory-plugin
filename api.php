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

function wpbdp_get_option($key) {
	global $wpbdp;
	return $wpbdp->settings->get($key);
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
	return wpbdp_formfields_api()->getField($id);
}
