<?php
/**
 * WPBDP Licensing class checks for licenses status, activates/deactivates licenses.
 *
 * @package BDP/Includes
 */

// phpcs:disable

/**
 * @since 3.4.2
 * @SuppressWarnings(PHPMD)
 */
class WPBDP_Licensing {

    const STORE_URL = 'https://businessdirectoryplugin.com/';

    private $items    = array(); // Items (modules and/or themes) registered with the Licensing API.
    private $licenses = array(); // License information: status, last check, etc.

    public function __construct() {
        $this->licenses = get_option( 'wpbdp_licenses', array() );

        add_action( 'wpbdp_register_settings', array( &$this, 'register_settings' ) );
        add_filter( 'wpbdp_setting_type_license_key', array( $this, 'license_key_setting' ), 10, 2 );

        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );

        add_action( 'wp_ajax_wpbdp_activate_license', array( &$this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_wpbdp_deactivate_license', array( &$this, 'ajax_deactivate_license' ) );

        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
        add_filter( 'wpbdp_settings_tab_css', array( $this, 'licenses_tab_css' ), 10, 2 );

        add_action( 'wpbdp_license_check', array( &$this, 'license_check' ) );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update_info' ) );
        add_filter( 'plugins_api', array( $this, 'module_update_information' ), 10, 3 );

        add_action( 'wpbdp_admin_ajax_dismiss_notification_expired_licenses', array( $this, 'dismiss_expired_licenses_notification' ) );

        if ( ! wp_next_scheduled( 'wpbdp_license_check' ) ) {
            wp_schedule_event( time(), 'daily', 'wpbdp_license_check' );
        }

        // add_action( 'init', function() { do_action( 'wpbdp_license_check' );  }, 999 );
        // add_action( 'init', function() { wpbdp()->licensing->updates_check(); }, 999 );
    }

    public function add_item( $args = array() ) {
        $defaults     = array(
            'item_type' => 'module',
            'file'      => '',
            'id'        => ! empty( $args['file'] ) ? trim( str_replace( '.php', '', basename( $args['file'] ) ) ) : '',
            'name'      => '',
            'version'   => '',
        );
        $args         = wp_parse_args( $args, $defaults );
        $args['slug'] = plugin_basename( $args['file'] );

        $this->items[ $args['id'] ] = $args;

        // Keep items sorted by name.
        uasort( $this->items, array( $this, 'sort_modules_by_name' ) );

        return $this->items[ $args['id'] ];
    }

    public function add_item_and_check_license( $args = array() ) {
        $item = $this->add_item( $args );

        if ( $item ) {
            $license_status = wpbdp()->licensing->get_license_status( '', $item['id'], 'module' );

            if ( in_array( $license_status, array( 'valid', 'expired' ), true ) ) {
                return true;
            }
        }

        return false;
    }

    public function get_items() {
        return $this->items;
    }

    public function register_settings() {
        $modules = wp_list_filter( $this->items, array( 'item_type' => 'module' ) );
        $themes  = wp_list_filter( $this->items, array( 'item_type' => 'theme' ) );

        if ( ! $modules && ! $themes ) {
            return;
        }

        wpbdp_register_settings_group( 'licenses', __( 'Licenses', 'WPBDM' ) );
        wpbdp_register_settings_group(
            'licenses/main', __( 'Licenses', 'WPBDM' ), 'licenses', array(
				'desc'        => $this->get_settings_section_description(),
				'custom_form' => true,
            )
        );

        if ( $modules ) {
            wpbdp_register_settings_group( 'licenses/modules', _x( 'Premium Modules', 'settings', 'WPBDM' ), 'licenses/main' );

            foreach ( $modules as $module ) {
                wpbdp_register_setting(
                    array(
						'id'                  => 'license-key-module-' . $module['id'],
						'name'                => $module['name'],
						'licensing_item'      => $module['id'],
						'licensing_item_type' => 'module',
						'type'                => 'license_key',
						'on_update'           => array( $this, 'license_key_changed_callback' ),
						'group'               => 'licenses/modules',
                    )
                );
            }
        }

        if ( $themes ) {
            wpbdp_register_settings_group( 'licenses/themes', _x( 'Themes', 'settings', 'WPBDM' ), 'licenses/main' );

            foreach ( $themes as $theme ) {
                wpbdp_register_setting(
                    array(
						'id'                  => 'license-key-theme-' . $theme['id'],
						'name'                => $theme['name'],
						'type'                => 'license_key',
						'licensing_item'      => $theme['id'],
						'licensing_item_type' => 'theme',
						'on_update'           => array( $this, 'license_key_changed_callback' ),
						'group'               => 'licenses/themes',
                    )
                );
            }
        }
    }

    private function get_settings_section_description() {
        $ip_address = $this->get_server_ip_address();

        if ( ! $ip_address ) {
            return '';
        }

        $description = _x( 'The IP address of your server is <ip-address>. Please make sure to include that information if you need to contact support about problems trying to activate your licenses.', 'settings', 'WPBDM' );
        $description = str_replace( '<ip-address>', '<strong>' . $ip_address . '</strong>', $description );

        return $description;
    }

    public function license_key_setting( $setting, $value ) {
        $item_type = $setting['licensing_item_type'];
        $item_id   = $setting['licensing_item'];

        $license_status = $this->get_license_status( $value, $item_id, $item_type );

        $licensing_info_attr = json_encode(
            array(
				'setting'   => $setting['id'],
				'item_type' => $item_type,
				'item_id'   => $item_id,
				'status'    => $license_status,
				'nonce'     => wp_create_nonce( 'license activation' ),
            )
        );

        $html  = '';
        $html .= '<div class="wpbdp-license-key-activation-ui wpbdp-license-status-' . $license_status . '" data-licensing="' . esc_attr( $licensing_info_attr ) . '">';
        $html .= '<span class="wpbdp-license-warning-icon dashicons dashicons-warning"></span>';
        $html .= '<span class="wpbdp-license-ok-icon dashicons dashicons-yes"></span>';
        $html .= '<input type="text" id="' . $setting['id'] . '" class="wpbdp-license-key-input" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $value ) . '" ' . ( 'valid' == $license_status ? 'readonly="readonly"' : '' ) . ' placeholder="' . _x( 'Enter License Key here', 'admin settings', 'WPBDM' ) . '"/>';
        $html .= '<input type="button" value="' . _x( 'Activate', 'settings', 'WPBDM' ) . '" data-working-msg="' . esc_attr( _x( 'Please wait...', 'settings', 'WPBDM' ) ) . '" class="button button-primary wpbdp-license-key-activate-btn" />';
        $html .= '<input type="button" value="' . _x( 'Deactivate', 'settings', 'WPBDM' ) . '" data-working-msg="' . esc_attr( _x( 'Please wait...', 'settings', 'WPBDM' ) ) . '" class="button wpbdp-license-key-deactivate-btn" />';
        $html .= '<div class="wpbdp-license-key-activation-status-msg wpbdp-hidden"></div>';
        $html .= '</div>';

        return $html;
    }

    private function get_server_ip_address() {
        $ip_address = get_transient( 'wpbdp-server-ip-address' );

        if ( $ip_address ) {
            return $ip_address;
        }

        $ip_address = $this->figure_out_server_ip_address();

        if ( ! $ip_address ) {
            $ip_address = '(unknown)';
        }

        set_transient( 'wpbdp-server-ip-address', $ip_address, HOUR_IN_SECONDS );

        return $ip_address;
    }

    private function figure_out_server_ip_address() {
        $response = wp_remote_get( 'https://httpbin.org/ip' );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! isset( $body->origin ) ) {
            return null;
        }

        return $body->origin;
    }

    function admin_menu( $menu ) {
        if ( ! current_user_can( 'administrator' ) || ! $this->items ) {
            return;
        }

        add_submenu_page(
            'wpbdp_admin',
            _x( 'Licenses', 'settings', 'WPBDM' ),
            _x( 'Licenses', 'settings', 'WPBDM' ),
            'administrator',
            'wpbdp-licenses',
            '__return_false'
        );
        global $submenu;

        foreach ( $submenu as $menu_id => &$m ) {
            if ( $menu == $menu_id ) {
                foreach ( $m as &$i ) {
                    if ( 'wpbdp-licenses' == $i[2] ) {
                        $i[2] = admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' );
                        break;
                    }
                }

                break;
            }
        }
    }

    public function license_key_changed_callback( $setting, $new_value = '', $old_value = '' ) {
        if ( $new_value == $old_value ) {
            return;
        }

        $this->licenses[ $setting['licensing_item_type'] . '-' . $setting['licensing_item'] ] = array(
			'license_key' => $new_value,
			'status'      => 'unknown',
		);
        update_option( 'wpbdp_licenses', $this->licenses );

        return $new_value;
    }

    function licenses_tab_css( $css = '', $tab_id ) {
        if ( 'licenses' == $tab_id ) {
            foreach ( $this->items as $item ) {
                if ( 'valid' != $this->get_license_status( '', $item['id'], $item['item_type'] ) ) {
                    $css .= ' tab-error';
                    break;
                }
            }
        }

        return $css;
    }

    /**
     * Returns the license status from license information.
     */
    public function get_license_status( $license_key = '', $item_id = '', $item_type = 'module' ) {
        if ( ! $license_key ) {
            $license_key = wpbdp_get_option( 'license-key-' . $item_type . '-' . $item_id );
        }

        if ( $license_key ) {
            // TODO: maybe refresh license info here?
            $data_key = $item_type . '-' . $item_id;
            if ( ! empty( $this->licenses[ $data_key ] ) ) {
                $data = $this->licenses[ $data_key ];

                if ( ! empty( $data['license_key'] ) && $license_key == $data['license_key'] ) {
                    return $data['status'];
                }
            }
        }

        return 'invalid';
    }

    private function activate_license( $item_type, $item_id ) {
        if ( ! in_array( $item_id, array_keys( $this->items ), true ) ) {
            return new WP_Error( 'invalid-module', _x( 'Invalid item ID', 'licensing', 'WPBDM' ), $module );
        }

        $key = wpbdp_get_option( 'license-key-' . $item_type . '-' . $item_id );

        if ( ! $key ) {
            return new WP_Error( 'no-license-provided', _x( 'No license key provided', 'licensing', 'WPBDM' ) );
        }

        $request = array(
            'edd_action' => 'activate_license',
            'license'    => $key,
            'item_name'  => urlencode( $this->items[ $item_id ]['name'] ),
            'url'        => home_url(),
        );

        // Call the licensing server.
        $response = $this->license_request( add_query_arg( $request, self::STORE_URL ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) || 'valid' !== $license_data->license ) {
            $this->licenses[ $item_type . '-' . $item_id ]['status'] = 'invalid';
            update_option( 'wpbdp_licenses', $this->licenses );

            if ( 'revoked' === $license_data->error ) {
                $message  = '<strong>' . _x( 'The license key was revoked.', 'licensing', 'WPBDM' ) . '</strong>';
                $message .= '<br/><br/>';
                $message .= _x( 'If you think this is a mistake, please contact <support-link>Business Directory support</support-link> and let them know your license is being reported as revoked by the licensing software.', 'licensing', 'WPBDM' );
                $message .= '<br/><br/>';
                $message .= _x( 'Please include the email address you used to purchase <module-name> with your report.', 'licensing', 'WPBDM' );

                $message = str_replace( '<support-link>', '<a href="https://businessdirectoryplugin.com/contact">', $message );
                $message = str_replace( '</support-link>', '</a>', $message );
                $message = str_replace( '<module-name>', '<strong>' . $this->items[ $item_id ]['name'] . '</strong>', $message );

                // The javascript handler already adds a dot at the end.
                $message = rtrim( $message, '.' );

                return new WP_Error( 'revoked-license', $message );
            } else {
                $message = _x( 'License key is invalid', 'licensing', 'WPBDM' );

                return new WP_Error( 'invalid-license', $message );
            }
        }

        $this->licenses[ $item_type . '-' . $item_id ]['license_key']  = $key;
        $this->licenses[ $item_type . '-' . $item_id ]['status']       = 'valid';
        $this->licenses[ $item_type . '-' . $item_id ]['expires']      = $license_data->expires;
        $this->licenses[ $item_type . '-' . $item_id ]['last_checked'] = time();
        update_option( 'wpbdp_licenses', $this->licenses );

        return $this->licenses[ $item_type . '-' . $item_id ];
    }

    private function deactivate_license( $item_type, $item_id ) {
        if ( ! in_array( $item_id, array_keys( $this->items ), true ) ) {
            return new WP_Error( 'invalid-module', _x( 'Invalid module ID', 'licensing', 'WPBDM' ), $module );
        }

        // Remove licensing information.
        unset( $this->licenses[ $item_type . '-' . $item_id ] );
        update_option( 'wpbdp_licenses', $this->licenses );

        $key = wpbdp_get_option( 'license-key-' . $item_type . '-' . $item_id );

        $request = array(
            'edd_action' => 'deactivate_license',
            'license'    => $key,
            'item_name'  => urlencode( $this->items[ $item_id ]['name'] ),
            'url'        => home_url(),
        );

        // Call the licensing server.
        $response = $this->license_request( add_query_arg( $request, self::STORE_URL ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) ) {
            return new WP_Error( 'invalid-license', _x( 'License key is invalid', 'licensing', 'WPBDM' ) );
        }

        if ( 'deactivated' !== $license_data->license ) {
            return new WP_Error( 'deactivation-failed', _x( 'Deactivation failed', 'licensing', 'WPBDM' ) );
        }

        return true;
    }

    private function handle_failed_license_request( $response ) {
        if ( ! function_exists( 'curl_init' ) ) {
            $message  = '<strong>' . _x( "It was not possible to establish a connection with Business Directory's server. cURL was not found in your system", 'licensing', 'WPBDM' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= _x( 'To ensure the security of our systems and adhere to industry best practices, we require that your server uses a recent version of cURL and a version of OpenSSL that supports TLSv1.2 (minimum version with support is OpenSSL 1.0.1c).', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Upgrading your system will not only allow you to communicate with Business Directory servers but also help you prepare your website to interact with services using the latest security standards.', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Please contact your hosting provider and ask them to upgrade your system. Include this message if necessary', 'licensing', 'WPBDM' );
            return new WP_Error( 'request-failed', $message );
        }
        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, 'https://businessdirectoryplugin.com' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        $r = curl_exec( $ch );

        $error_number  = curl_errno( $ch );
        $error_message = curl_error( $ch );

        curl_close( $ch );

        if ( in_array( $error_number, array( 7 ), true ) ) {
            $message  = '<strong>' . _x( "It was not possible to establish a connection with Business Directory's server. The connection failed with the following error:", 'licensing', 'WPBDM' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= '<code>curl: (' . $error_number . ') ' . $error_message . '</code>';
            $message .= '<br/><br/>';
            $message .= _x( 'It looks like your server is not authorized to make requests to Business Directory servers. Please contact <support-link>Business Directory support</support-link> and ask them to add your IP address <ip-address> to the whitelist.', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Include this error message with your report.', 'licensing', 'WPBDM' );

            $message = str_replace( '<support-link>', '<a href="https://businessdirectoryplugin.com/contact">', $message );
            $message = str_replace( '</support-link>', '</a>', $message );
            $message = str_replace( '<ip-address>', $this->get_server_ip_address(), $message );
            // The javascript handler already adds a dot at the end.
            $message = rtrim( $message, '.' );

            return new WP_Error( 'connection-refused', $message );
        } elseif ( in_array( $error_number, array( 35 ), true ) ) {
            $message = '<strong>' . _x( "It was not possible to establish a connection with Business Directory's server. A problem occurred in the SSL/TSL handshake:", 'licensing', 'WPBDM' ) . '</strong>';

            $message .= '<br/><br/>';
            $message .= '<code>curl: (' . $error_number . ') ' . $error_message . '</code>';
            $message .= '<br/><br/>';
            $message .= _x( 'To ensure the security of our systems and adhere to industry best practices, we require that your server uses a recent version of cURL and a version of OpenSSL that supports TLSv1.2 (minimum version with support is OpenSSL 1.0.1c).', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Upgrading your system will not only allow you to communicate with Business Directory servers but also help you prepare your website to interact with services using the latest security standards.', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Please contact your hosting provider and ask them to upgrade your system. Include this message if necessary.', 'licensing', 'WPBDM' );

            // The javascript handler already adds a dot at the end.
            $message = rtrim( $message, '.' );

            return new WP_Error( 'request-failed', $message );
        } else {
            return new WP_Error( 'request-failed', _x( 'Could not contact licensing server', 'licensing', 'WPBDM' ) );
        }
    }

    private function license_request( $url ) {
        // Call the licensing server.
        $response = wp_remote_get(
            $url, array(
				'timeout'    => 15,
				'user-agent' => $this->user_agent_header(),
				'sslverify'  => false,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $this->handle_failed_license_request( $response );
        }

        $response_code    = wp_remote_retrieve_response_code( $response );
        $response_message = wp_remote_retrieve_response_message( $response );

        if ( 403 == $response_code ) {
            $message  = '<strong>' . _x( 'The server returned a 403 Forbidden error.', 'licensing', 'WPBDM' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= _x( 'It looks like your server is not authorized to make requests to Business Directory servers. Please contact <support-link>Business Directory support</support-link> and ask them to add your IP address <ip-address> to the whitelist.', 'licensing', 'WPBDM' );
            $message .= '<br/><br/>';
            $message .= _x( 'Include this error message with your report.', 'licensing', 'WPBDM' );

            $message = str_replace( '<support-link>', '<a href="https://businessdirectoryplugin.com/contact">', $message );
            $message = str_replace( '</support-link>', '</a>', $message );
            $message = str_replace( '<ip-address>', $this->get_server_ip_address(), $message );

            // The javascript handler already adds a dot at the end.
            $message = rtrim( $message, '.' );

            return new WP_Error( 'request-not-authorized', $message );
        }

        return $response;
    }

    function sort_modules_by_name( $x, $y ) {
        return strncasecmp( $x['name'], $y['name'], 4 );
    }

    public function admin_notices() {
        global $pagenow;

        if ( 'admin.php' == $pagenow && ! empty( $_GET['page'] ) && 'wpbdp_settings' == $_GET['page'] && ! empty( $_GET['tab'] ) && 'licenses' == $_GET['tab'] ) {
            return;
        }

        $expired = array();
        $invalid = array();

        foreach ( $this->items as $item ) {
            $status = $this->get_license_status( '', $item['id'], $item['item_type'] );

            if ( 'valid' == $status ) {
                // All good!
            } elseif ( 'expired' == $status ) {
                $expired[] = array(
                    'item_type'   => $item['item_type'],
                    'status'      => $status,
                    'name'        => $item['name'],
                    'license_key' => wpbdp_get_option( 'license-key-' . $item['item_type'] . '-' . $item['id'] ),
                );
            } else {
                $invalid[] = array(
                    'item_type'   => $item['item_type'],
                    'status'      => $status,
                    'name'        => $item['name'],
                    'license_key' => wpbdp_get_option( 'license-key-' . $item['item_type'] . '-' . $item['id'] ),
                );
            }
        }

        $this->render_invalid_license_admin_notice( $invalid );
        $this->render_expired_license_admin_notice( $expired );
    }

    private function render_invalid_license_admin_notice( $invalid ) {
        $modules = wp_list_filter( $invalid, array( 'item_type' => 'module' ) );
        $themes  = wp_list_filter( $invalid, array( 'item_type' => 'theme' ) );

        if ( ! $modules && ! $themes ) {
            return;
        }

        echo '<div id="wpbdp-licensing-issues-warning" class="error"><p>';
        echo '<b>' . _x( 'Business Directory - Please verify your license keys', 'licensing', 'WPBDM' ) . '</b><br />';

        echo '<ul>';
        if ( $modules ) {
            $modules_str = '';
            foreach ( $modules as $m ) {
                $modules_str .= '<span class="item-name">' . $m['name'] . '</span>';
            }

            echo '<li>';
            printf( _x( 'The following premium modules will not work until a valid license key is provided: %s.', 'licensing', 'WPBDM' ), $modules_str );
            echo '</li>';
        }

        if ( $themes ) {
            $themes_str = '';
            foreach ( $themes as $t ) {
                $themes_str .= '<span class="item-name">' . $t['name'] . '</span>';
            }

            echo '<li>';
            printf( _x( 'You need to activate the license keys for the following themes before they can be used: %s.', 'licensing', 'WPBDM' ), $themes_str );
            echo '</li>';
        }

        echo '</ul>';

        echo '<p>';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '" class="button button-primary">';
        echo _x( 'Review my license keys', 'licensing', 'WPBDM' );
        echo '</a>';
        echo '</p>';

        echo '</div>';
    }

    private function render_expired_license_admin_notice( $expired ) {
        $notice_id = 'expired_licenses';

        $transient_key = 'wpbdp-expired-licenses-notice-dismissed-' . get_current_user_id();

        if ( get_transient( $transient_key ) ) {
            return;
        }

        $modules = wp_list_filter( $expired, array( 'item_type' => 'module' ) );
        $themes  = wp_list_filter( $expired, array( 'item_type' => 'theme' ) );

        if ( ! $modules && ! $themes ) {
            return;
        }

        $nonce = wp_create_nonce( 'dismiss notice ' . $notice_id );

        echo '<div id="wpbdp-licensing-issues-warning" class="wpbdp-notice notice notice-error is-dismissible" data-dismissible-id="' . esc_attr( $notice_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
        echo '<p>';
        echo '<b>' . _x( 'Business Directory - License key expired', 'licensing', 'WPBDM' ) . '</b><br />';

        echo '<ul>';
        if ( $modules ) {
            $modules_str = '';
            foreach ( $modules as $m ) {
                $modules_str .= '<span class="item-name">' . $m['name'] . '</span>';
            }

            echo '<li>';
            printf( _x( 'The license key for the following modules has expired: %s. The modules will continue to work but you will not receive any more updates until the license is renewed.', 'licensing', 'WPBDM' ), $modules_str );
            echo '</li>';
        }

        if ( $themes ) {
            $themes_str = '';
            foreach ( $themes as $t ) {
                $themes_str .= '<span class="item-name">' . $t['name'] . '</span>';
            }

            echo '<li>';
            printf( _x( 'The license key for the following themes has expired: %s. The themes will continue to work but you will not receive any more updates until the license is renewed.', 'licensing', 'WPBDM' ), $themes_str );
            echo '</li>';
        }

        echo '</ul>';

        echo '<p>';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '" class="button button-primary">';
        echo _x( 'Review my license keys', 'licensing', 'WPBDM' );
        echo '</a>';
        echo '</p>';

        echo '</div>';
    }

    public function dismiss_expired_licenses_notification() {
        set_transient( 'wpbdp-expired-licenses-notice-dismissed-' . get_current_user_id(), true, 2 * WEEK_IN_SECONDS );
    }

    public function license_check() {
        $last_license_check = get_site_transient( 'wpbdp-license-check-time' );

        if ( ! empty( $last_license_check ) ) {
            return;
        }

        $this->licenses = $this->get_licenses_status();
        update_option( 'wpbdp_licenses', $this->licenses );

        set_site_transient( 'wpbdp-license-check-time', current_time( 'timestamp' ), 1 * WEEK_IN_SECONDS );
    }

    public function get_licenses_status() {
        if ( ! $this->items ) {
            return array();
        }

        $licenses = array();

        foreach ( $this->items as $item ) {
            $item_key = $item['item_type'] . '-' . $item['id'];
            $key      = wpbdp_get_option( 'license-key-' . $item_key );

            if ( ! $key ) {
                $licenses[ $item_key ] = array(
                    'status'       => 'invalid',
                    'last_checked' => time(),
                );
                continue;
            }

            $request_args = array(
                'edd_action' => 'check_license',
                'license'    => $key,
                'item_name'  => $item['name'],
                'url'        => home_url(),
            );
            $response     = wp_remote_get(
                add_query_arg( $request_args, self::STORE_URL ), array(
					'timeout'    => 15,
					'user-agent' => $this->user_agent_header(),
					'sslverify'  => false,
                )
            );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $response_obj = json_decode( wp_remote_retrieve_body( $response ) );

            if ( ! is_object( $response_obj ) || ! $response_obj || ! isset( $response_obj->license ) ) {
                continue;
            }

            $licenses[ $item_key ] = array(
                'status'       => $response_obj->license,
                'license_key'  => $key,
                'expires'      => isset( $response_obj->expires ) ? $response_obj->expires : '',
                'last_checked' => time(),
            );
        }

        return $licenses;
    }

    public function ajax_activate_license() {
        $setting_id = $_POST['setting'];
        $key        = $_POST['license_key'];
        $item_type  = $_POST['item_type'];
        $item_id    = $_POST['item_id'];
        $nonce      = $_POST['nonce'];

        $response = new WPBDP_Ajax_Response();

        if ( ! $setting_id || ! $item_type || ! $item_id || ! wp_verify_nonce( $nonce, 'license activation' ) ) {
            $response->send_error();
        }

        if ( ! $key ) {
            $response->send_error( _x( 'Please enter a license key.', 'licensing', 'WPBDM' ) );
        }

        // Store the new license key. This clears stored information about the license.
        wpbdp_set_option( 'license-key-' . $item_type . '-' . $item_id, $key );

        $result = $this->activate_license( $item_type, $item_id );

        if ( is_wp_error( $result ) ) {
             $response->send_error( sprintf( _x( 'Could not activate license: %s.', 'licensing', 'WPBDM' ), $result->get_error_message() ) );
        } else {
            $response->set_message( _x( 'License activated', 'licensing', 'WPBDM' ) );
            $response->send();
        }
    }

    public function ajax_deactivate_license() {
        $setting_id = $_POST['setting'];
        $key        = $_POST['license_key'];
        $item_type  = $_POST['item_type'];
        $item_id    = $_POST['item_id'];
        $nonce      = $_POST['nonce'];

        if ( ! $setting_id || ! $key || ! $item_type || ! $item_id || ! wp_verify_nonce( $nonce, 'license activation' ) ) {
            die();
        }

        $result   = $this->deactivate_license( $item_type, $item_id );
        $response = new WPBDP_Ajax_Response();

        if ( is_wp_error( $result ) ) {
            $response->send_error( sprintf( _x( 'Could not deactivate license: %s.', 'licensing', 'WPBDM' ), $result->get_error_message() ) );
        } else {
            $response->set_message( _x( 'License deactivated', 'licensing', 'WPBDM' ) );
            $response->send();
        }
    }

    public function get_version_information() {
        if ( ! $this->items ) {
            return array();
        }

        $store_url = set_url_scheme( trim( self::STORE_URL, '/' ), 'https' );
        $home_url  = set_url_scheme( trim( home_url(), '/' ), 'https' );

        // Don't allow a plugin to ping itself.
        if ( $store_url == $home_url ) {
            return array();
        }

        $updates       = get_transient( 'wpbdp_updates' );
        $needs_refresh = false;

        foreach ( $this->items as $item ) {
            if ( ! isset( $updates[ $item['item_type'] . '-' . $item['id'] ] ) ) {
                $needs_refresh = true;
                break;
            }
        }

        if ( ! $needs_refresh ) {
            return $updates;
        }

        $args = array(
            'edd_action' => 'batch_get_version',
            'licenses'   => array(),
            'items'      => array(),
            'url'        => home_url(),
        );

        foreach ( $this->items as $item ) {
            $args['licenses'][] = wpbdp_get_option( 'license-key-' . $item['item_type'] . '-' . $item['id'] );
            $args['items'][]    = $item['name'];
        }

        $request = wp_remote_get(
            self::STORE_URL, array(
				'timeout'    => 15,
				'user-agent' => $this->user_agent_header(),
				'sslverify'  => false,
				'body'       => $args,
            )
        );

        if ( is_wp_error( $request ) ) {
            return array();
        }

        $body = wp_remote_retrieve_body( $request );
        $body = json_decode( $body );

        if ( ! is_array( $body ) ) {
            return array();
        }

        foreach ( $body as $i => $item_information ) {
            if ( isset( $item_information->sections ) ) {
                $body[ $i ]->sections = maybe_unserialize( $item_information->sections );
            }
        }

        // Some processing.
        $updates = array();

        foreach ( $this->items as $item ) {
            $item_key = $item['item_type'] . '-' . $item['id'];

            foreach ( $body as $item_information ) {
                if ( $item_information->name == $item['name'] ) {
                    $updates[ $item_key ]       = $item_information;
                    $updates[ $item_key ]->slug = $item['id'];
                }
            }
        }

        set_transient( 'wpbdp_updates', $updates, 1 * DAY_IN_SECONDS );

        return $updates;
    }

    /**
     * Inject BD modules update info into update array (`update_plugins` transient).
     */
    public function inject_update_info( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        global $pagenow;

        if ( 'plugins.php' == $pagenow && is_multisite() ) {
            return $transient;
        }

        $updates = $this->get_version_information();

        if ( ! $updates ) {
            return $transient;
        }

        $modules = wp_list_filter( $this->items, array( 'item_type' => 'module' ) );

        foreach ( $modules as $module ) {
            $license_status = $this->get_license_status( '', $module['id'], $module['item_type'] );

            if ( 'valid' != $license_status ) {
                continue;
            }

            $item_key = $module['item_type'] . '-' . $module['id'];

            if ( ! isset( $updates[ $item_key ] ) ) {
                continue;
            }

            $wp_name = plugin_basename( $module['file'] );

            if ( ! empty( $transient->response ) && ! empty( $transient->response[ $wp_name ] ) ) {
                continue;
            }

            if ( ! isset( $updates[ $item_key ]->new_version ) ) {
                continue;
            }

            if ( version_compare( $module['version'], $updates[ $item_key ]->new_version, '<' ) ) {
                $transient->response[ $wp_name ] = $updates[ $item_key ];
            }

            $transient->last_checked        = current_time( 'timestamp' );
            $transient->checked[ $wp_name ] = $module['version'];
        }

        return $transient;
    }

    public function module_update_information( $data, $action = '', $args = null ) {
        if ( 'plugin_information' != $action || ! isset( $args->slug ) ) {
            return $data;
        }

        $matches = wp_list_filter( $this->items, array( 'file' => $args->slug ) );
        if ( ! $matches ) {
            return $data;
        }

        $item = array_pop( $matches );

        $http_args = array(
            'timeout'   => 15,
            'sslverify' => false,
            'body'      => array(
                'edd_action' => 'get_version',
                'item_name'  => $item['name'],
                'license'    => wpbdp_get_option( 'license-key-' . $item['item_type'] . '-' . $item['id'] ),
                'url'        => home_url(),
            ),
        );
        $request   = wp_remote_post( self::STORE_URL, $http_args );

        if ( ! is_wp_error( $request ) ) {
            $request = json_decode( wp_remote_retrieve_body( $request ) );

            if ( $request && is_object( $request ) && isset( $request->sections ) ) {
                $request->sections = maybe_unserialize( $request->sections );
                $data              = $request;
            }
        }

        return $data;
    }

    function user_agent_header() {
        $user_agent = 'WordPress %s / Business Directory Plugin %s';
        $user_agent = sprintf( $user_agent, get_bloginfo( 'version' ), WPBDP_VERSION );
        return $user_agent;
    }

    // }
}

/**
 * @since 3.4.2
 * @deprecated since 5.0.
 */
function wpbdp_licensing_register_module( $name, $file_, $version ) {
    global $wpbdp_compat_modules_registry;

    if ( ! isset( $wpbdp_compat_modules_registry ) ) {
        $wpbdp_compat_modules_registry = array();
    }

    // TODO: Use numbered placeholders with sprintf or named placeholders with str_replace.
    /* translators: "<module-name>" version <version-number> is not... */
    wpbdp_deprecation_warning( sprintf( _x( '"%1$s" version %2$s is not compatible with Business Directory Plugin 5.0. Please update this module to the latest available version.', 'deprecation', 'WPBDM' ), '<strong>' . esc_html( $name ) . '</strong>', '<strong>' . $version . '</strong>' ) );
    $wpbdp_compat_modules_registry[] = array( $name, $file_, $version );

    return false;
}

/**
 * Added for compatibility with < 5.x modules.
 *
 * @since 5.0.1
 */
function wpbdp_compat_register_old_modules() {
    global $wpbdp_compat_modules_registry;

    if ( ! isset( $wpbdp_compat_modules_registry ) || empty( $wpbdp_compat_modules_registry ) ) {
        $wpbdp_compat_modules_registry = array();
    }

    // Gateways are a special case since they are registered in 'wpbdp_register_gateways'.
    if ( has_filter( 'wpbdp_register_gateways' ) ) {
        if ( function_exists( 'wp_get_active_and_valid_plugins' ) ) {
            $plugins = wp_get_active_and_valid_plugins();

            foreach ( $plugins as $plugin_file ) {
                $plugin_file_basename = basename( $plugin_file );

                if ( 'business-directory-paypal.php' == $plugin_file_basename ) {
                    $wpbdp_compat_modules_registry[] = array( 'PayPal Gateway Module', $plugin_file, '3.5.6' );
                } elseif ( 'business-directory-twocheckout.php' == $plugin_file_basename ) {
                    $wpbdp_compat_modules_registry[] = array( '2Checkout Gateway Module', $plugin_file, '3.6.2' );
                }
            }
        }
    }

    foreach ( $wpbdp_compat_modules_registry as $m ) {
        wpbdp()->licensing->add_item(
            array(
				'item_type' => 'module',
				'name'      => $m[0],
				'file'      => $m[1],
				'version'   => $m[2],
            )
        );
    }
}
add_action( 'wpbdp_licensing_before_updates_check', 'wpbdp_compat_register_old_modules' );
