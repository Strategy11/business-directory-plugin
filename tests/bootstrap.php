<?php
/**
 * Code executed before PHPUnit executes the test suite.
 *
 * @package WPBDP/Tests
 */

echo 'Welcome to the Test Suite' . PHP_EOL;
echo 'Version: 1.0' . PHP_EOL . PHP_EOL;

$GLOBALS['wp_tests_options'] = array(
    'active_plugins' => array(
        'business-directory-plugin/business-directory-plugin.php',
        //'business-directory-regions/business-directory-regions.php',
    ),
);

// Without this comment, PHPCS keeps complaining about a missing file doc comment.
require dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';
require dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WPBDP_PLUGIN_FILE', dirname( __DIR__ ) . '/business-directory-plugin.php' );

if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
    require_once getenv( 'WP_DEVELOP_DIR' ) . 'tests/phpunit/includes/bootstrap.php';
} else {
    require_once '../../../../tests/phpunit/includes/bootstrap.php';
}

require dirname( __FILE__ ) . '/includes/TestCase.php';
require dirname( __FILE__ ) . '/includes/AjaxTestCase.php';
