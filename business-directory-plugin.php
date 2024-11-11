<?php
/**
 * Plugin Name: Business Directory Plugin
 * Plugin URI: https://businessdirectoryplugin.com
 * Description: Provides the ability to maintain a free or paid business directory on your WordPress powered site.
 * Version: 6.4.7
 * Author: Business Directory Team
 * Author URI: https://businessdirectoryplugin.com
 * Text Domain: business-directory-plugin
 * Domain Path: /languages/
 * License: GPLv2 or any later version
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or later, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package WPBDP
 */

// Do not allow direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPBDP_PLUGIN_FILE' ) ) {
	define( 'WPBDP_PLUGIN_FILE', __FILE__ );
}

// Add the autoloader.
spl_autoload_register( 'wpbdp_dir_autoloader' );

/**
 * @since 6.3.11
 *
 * @return void
 */
function wpbdp_dir_autoloader( $class_name ) {
	// Only load Wpbdp classes here
	if ( ! preg_match( '/^wpbdp.+$/', strtolower( $class_name ) ) ) {
		return;
	}

	wpbdp_class_autoloader( $class_name, __DIR__ );
}

/**
 * Autoload the BD classes
 *
 * @since 6.3.11
 *
 * @return void
 */
function wpbdp_class_autoloader( $class_name, $filepath ) {
	$deprecated        = array( 'WPBDP_DB_Model2', 'WPBDP_DB_Entity_Error_List' );
	$is_deprecated     = in_array( $class_name, $deprecated, true ) || preg_match( '/^.+Deprecate/', $class_name );
	$original_filepath = $filepath;
	$class_name        = str_replace(
		array( '___', '__', '_', 'WPBDP' ),
		array( '-', '-', '-', 'class' ),
		$class_name
	);

	$filepath .= '/includes/';

	if ( strpos( 'Admin', $class_name ) ) {
		$filepath .= 'admin/';
	}

	if ( $is_deprecated ) {
		$filepath .= 'compatibility/deprecated/';
	} elseif ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$filepath .= 'helpers/';
	} elseif ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$filepath .= 'controllers/';
		if ( ! file_exists( $filepath . $class_name . '.php' ) && strpos( $class_name, 'Views' ) ) {
			$filepath .= 'pages/';
		}
	} elseif ( strpos( $class_name, 'Field' ) && ! file_exists( $filepath . $class_name . '.php' ) ) {
		$filepath .= 'fields/';
	} else {
		$filepath .= 'models/';
	}

	if ( file_exists( $filepath . strtolower( $class_name ) . '.php' ) ) {
		require $filepath . strtolower( $class_name ) . '.php';
		return;
	}

	// Fallback to camelcase.
	$filepath .= $class_name . '.php';
	if ( file_exists( $filepath ) ) {
		require $filepath;
	}
}

if ( ! class_exists( 'WPBDP' ) ) {
	require_once dirname( WPBDP_PLUGIN_FILE ) . '/includes/class-wpbdp.php';
}

/**
 * Returns the main instance of Business Directory.
 *
 * @return WPBDP
 */
function wpbdp() {
	static $instance = null;

	if ( is_null( $instance ) ) {
		$instance = new WPBDP();
	}

	return $instance;
}

// Increase the priority value for Social Share Buttons widgets.
add_filter(
    'ssb_the_content_priority', function () {
        return 1100;
}, 100);


// For backwards compatibility.
$GLOBALS['wpbdp'] = wpbdp();
