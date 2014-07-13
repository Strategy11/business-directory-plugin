<?php

class WPBDP_Admin_Debug_Page {

    function __construct() {
        add_action( 'admin_init', array($this, 'handle_download' ) );
    }

    function dispatch( $plain = false ) {
        global $wpdb;

        $debug_info = array();

        // basic BD setup info & tests
        $debug_info['basic']['_title'] = _x( 'BD Info', 'debug-info', 'WPBDM' );
        $debug_info['basic']['BD version'] = WPBDP_VERSION;
        $debug_info['basic']['BD database revision (current)'] = WPBDP_Installer::DB_VERSION;
        $debug_info['basic']['BD database revision (installed)'] = get_option( 'wpbdp-db-version' );

        $tables = apply_filters( 'wpbdp_debug_info_tables_check', array( 'wpbdp_form_fields', 'wpbdp_fees', 'wpbdp_payments', 'wpbdp_listing_fees' ) );
        $missing_tables = array();
        foreach ( $tables as &$t ) {
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $t) ) == '' )
                $missing_tables[] = $t;
        }
        $debug_info['basic']['Table check'] = $missing_tables
                                              ? sprintf( _( 'Missing tables: %s', 'debug-info', 'WPBDM' ), implode(',', $missing_tables) )
                                              : _x( 'OK', 'debug-info', 'WPBDM' );

        $debug_info['basic']['Main Page'] = sprintf( '%d (%s)', wpbdp_get_page_id( 'main' ), get_post_status( wpbdp_get_page_id( 'main' ) ) );
        $debug_info['basic'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['basic'], 'basic' );


        // BD options
        $blacklisted = array( 'googlecheckout-merchant', 'paypal-business-email', 'wpbdp-2checkout-seller', 'recaptcha-public-key', 'recaptcha-private-key' );
        $debug_info['options']['_title'] = _x( 'BD Options', 'debug-info', 'WPBDM' );

        $settings_api = wpbdp_settings_api();
        foreach ( $settings_api->settings as &$s  ) {
            if ( $s->type == 'core' || in_array( $s->name, $blacklisted ) )
                continue;

            $value = wpbdp_get_option( $s->name );
            $debug_info['options'][ $s->name ] = is_array( $value ) ? implode( ',', $value ) : $value;
        }
        $debug_info['options'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['options'], 'options' );

        // environment info
        $debug_info['environment']['_title'] = _x( 'Environment', 'debug-info', 'WPBDM' );
        $debug_info['environment']['WordPress version'] = get_bloginfo( 'version', 'raw' );
        $debug_info['environment']['OS'] = php_uname( 's' ) . ' ' . php_uname( 'r' ) . ' ' . php_uname( 'm' );

        if ( function_exists( 'apache_get_version' ) ) {
            $apache_version = apache_get_version();
            $debug_info['environment']['Apache version'] = $apache_version;
        }

        $debug_info['environment']['PHP version'] = phpversion();

        $mysql_version = $wpdb->get_var( 'SELECT @@version' );
        if ( $sql_mode = $wpdb->get_var( 'SELECT @@sql_mode' ) )
            $mysql_version .= ' ( ' . $sql_mode . ' )';
        $debug_info['environment']['MySQL version'] = $mysql_version ? $mysql_version : 'N/A';

        $sqlite_version = class_exists('SQLite3') ? wpbdp_getv( SQLite3::version(), 'versionString', '' ): ( function_exists( 'sqlite_libversion' ) ? sqlite_libversion() : null );
        $debug_info['environment']['SQLite version'] = $sqlite_version ? $sqlite_version : 'N/A';

        $debug_info['environment']['cURL version'] = function_exists( 'curl_init' ) ? wpbdp_getv( curl_version(), 'version' ) : 'N/A';

        $debug_info['environment'] = apply_filters( 'wpbdp_debug_info_section', $debug_info['environment'], 'environment' );

        $debug_info = apply_filters( 'wpbdp_debug_info', $debug_info );

        if ( $plain ) {
            foreach ( $debug_info as &$section ) {
                foreach ( $section as $k => $v ) {
                    if ( $k == '_title' ) {
                        printf( '== %s ==', $v );
                        print PHP_EOL;
                        continue;
                    }

                    printf( "%-33s = %s", $k, $v );
                    print PHP_EOL;
                }

                print str_repeat( PHP_EOL, 2 );
            }
            return;
        }

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/debug-info.tpl.php', array( 'debug_info' => $debug_info ) );
    }

    function handle_download() {
        global $pagenow;

        if ( ! current_user_can( 'administrator' ) || 'admin.php' != $pagenow
             || ! isset( $_GET['page'] ) || 'wpbdp-debug-info' != $_GET['page'] )
            return;

        if ( isset( $_GET['download'] ) && 1 == $_GET['download'] ) {
                    header( 'Content-Description: File Transfer' );
                    header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ), true );
                    header( 'Content-Disposition: attachment; filename=' . 'wpbdp-debug-info.txt' );
                    header( 'Pragma: no-cache' );
                    $this->dispatch( true );
                    exit;
        }
    }
}
