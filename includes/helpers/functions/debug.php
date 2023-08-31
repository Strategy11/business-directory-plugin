<?php
function wpbdp_log( $msg, $type = 'info' ) {
	call_user_func( array( 'WPBDP_Debugging', 'log' ), $msg, $type );
}

function wpbdp_log_deprecated() {
	wpbdp_log( 'Deprecated function called.', 'deprecated' );
}

function wpbdp_debug() {
	$args = func_get_args();
	call_user_func_array( array( 'WPBDP_Debugging', 'debug' ), $args );
}

function wpbdp_debug_e() {
	$args = func_get_args();
	call_user_func_array( array( 'WPBDP_Debugging', 'debug_e' ), $args );
}
