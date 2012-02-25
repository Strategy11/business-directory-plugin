<?php
function wpbdp_debug() {
	echo '<pre>';

	foreach (func_get_args() as $arg) {
		var_dump($arg);
	}

	echo '</pre>';
}

function wpbdp_debug_e() {
	call_user_func_array('wpbdp_debug', func_get_args());
	exit;
}

function wpbdp_getv($dict, $key, $default=false) {
	$_dict = is_object($dict) ? (array) $dict : $dict;

	if (is_array($_dict) && isset($_dict[$key]))
		return $_dict[$key];

	return $default;
}

function wpbdp_render_page($template, $vars=array(), $echo_output=false) {
	if ($vars) {
		extract($vars);
	}

	ob_start();
	include($template);
	$html = ob_get_contents();
	ob_end_clean();

	if ($echo_output)
		echo $html;

	return $html;
}

