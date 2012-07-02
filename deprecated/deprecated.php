<?php
/*
 * Deprecated functionality.
 */

function get_wpbusdirman_config_options() {
	wpbdp_log_deprecated();

	global $wpbdp;
	return $wpbdp->settings->pre_2_0_compat_get_config_options();
}