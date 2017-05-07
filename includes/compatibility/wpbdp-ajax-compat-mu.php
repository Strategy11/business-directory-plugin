<?php
/*
 * Plugin Name: Business Directory Plugin - AJAX Compatibility Module
 * Plugin URI: http://www.businessdirectoryplugin.com
 * Version: 1.0
 * Author: D. Rodenbaugh
 * Author URI: http://businessdirectoryplugin.com
 * License: GPLv2 or any later version
 */

global $wpbdp_ajax_compat;
$wpbdp_ajax_compat = true;

// Only activate BD plugins during BD-related AJAX requests.
function wpbdp_ajax_compat_exclude_plugins( $plugins ) {
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX || ! isset( $_REQUEST['action'] ) || false === strpos( $_REQUEST['action'], 'wpbdp' ) )
        return $plugins;

    foreach ( $plugins as $key => $plugin ) {
        if ( false !== strpos( $plugin, 'business-directory-' ) )
            continue;

        unset( $plugins[ $key ] );
    }

    return $plugins;
}
add_filter( 'option_active_plugins', 'wpbdp_ajax_compat_exclude_plugins' );
