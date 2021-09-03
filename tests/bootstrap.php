<?php
/**
 * Code executed before PHPUnit executes the test suite.
 *
 * @package WPBDP/Tests
 */

echo 'Welcome to the Test Suite' . PHP_EOL;
echo 'Version: 1.0' . PHP_EOL . PHP_EOL;


if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require_once getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/functions.php';
} else {
	require_once '../../../../tests/phpunit/includes/functions.php';
}

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/business-directory-plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );


define( 'WPBDP_PLUGIN_FILE', dirname( __DIR__ ) . '/business-directory-plugin.php' );

if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require_once getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
} else {
	require_once '../../../../tests/phpunit/includes/bootstrap.php';
}