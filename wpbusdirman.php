<?php
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$active_plugins = get_option( 'active_plugins' );

foreach ( $active_plugins as &$plugin ) {
    if (  'business-directory-plugin/wpbusdirman.php' === strtolower( $plugin ) )
        $plugin = 'business-directory-plugin/business-directory-plugin.php';
}

update_option( 'active_plugins', $active_plugins );
