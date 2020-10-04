<?php
/**
 * WPBDP Licensing class checks for licenses status, activates/deactivates licenses.
 *
 * @package BDP/Includes
 */

/**
 * @since 3.4.2
 */
class WPBDP_Licensing {

    const STORE_URL = 'https://businessdirectoryplugin.com/';

    private $items           = array(); // Items (modules and/or themes) registered with the Licensing API.
    private $licenses        = array(); // License information: status, last check, etc.
    private $licenses_errors = array(); // Unverified license error information.

    public function __construct() {
        $this->licenses = get_option( 'wpbdp_licenses', array() );
        $this->licenses_errors = get_option( 'wpbdp_licenses_errors' );

        add_action( 'wpbdp_register_settings', array( &$this, 'register_settings' ) );
        add_filter( 'wpbdp_setting_type_license_key', array( $this, 'license_key_setting' ), 10, 2 );
        add_filter( 'wpbdp_setting_type_no_licenses', array( $this, 'empty_license_notice' ), 10, 2 );
        
        add_action( 'wpbdp_admin_menu', array( &$this, 'admin_menu' ) );

        add_action( 'wp_ajax_wpbdp_activate_license', array( &$this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_wpbdp_deactivate_license', array( &$this, 'ajax_deactivate_license' ) );

        add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
        add_filter( 'wpbdp_settings_tab_css', array( $this, 'licenses_tab_css' ), 10, 2 );

        add_action( 'wpbdp_license_check', array( &$this, 'license_check' ) );
        
        add_action( 'admin_notices', array( &$this, 'add_modules_hooks' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update_info' ) );
        add_filter( 'plugins_api', array( $this, 'module_update_information' ), 10, 3 );

        add_action( 'wpbdp_admin_ajax_dismiss_notification_expired_licenses', array( $this, 'dismiss_notification' ) );
        add_action( 'wpbdp_admin_ajax_dismiss_notification_license_status_error', array( $this, 'dismiss_notification' ) );
        add_action( 'wpbdp_admin_ajax_dismiss_notification_categories_license', array( $this, 'dismiss_notification' ) );
        
        if ( ! wp_next_scheduled( 'wpbdp_license_check' ) ) {
            wp_schedule_event( time(), 'daily', 'wpbdp_license_check' );
        }

    }

    public function add_modules_hooks() {
        global $pagenow;
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }

        $modules = wp_list_filter( $this->items, array( 'item_type' => 'module' ) );

        if ( ! $modules || ! $this->licenses_errors ) {
            return;
        }

        foreach ( $modules as $module_id => $module ) {
            if ( ! isset( $this->licenses_errors[ $module_id ] ) ) {
                continue;
            }

            add_action( 'after_plugin_row_' . plugin_basename( $module['file'] ), array(
                $this,
                'show_validation_notice_under_plugin'
            ), 10, 3 );
        }
    }

    public function show_validation_notice_under_plugin( $plugin_file, $plugin_data ) {
        echo '<tr class="wpbdp-module-key-not-verified plugin-update-tr active">
            <td colspan="4">
                <div class="update-message notice inline notice-warning notice-alt">
                    <p>
                        <span class="wpbdp-license-warning-icon dashicons dashicons-warning"></span>';
        echo sprintf( 
            /* translators: %1%s: opening <a> tag, %2$s: closing </a> tag */
            esc_html__( 'The license key could not be verified. Please %1$scheck your license%2$s to get updates.', 'business-directory-plugin' ),
            '<strong><a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '">',
            '</a></strong>'
        );
        echo '</p>
		        </div>
            </td>
        </tr>';
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

        wpbdp_register_settings_group( 'licenses', esc_html__( 'Licenses', 'business-directory-plugin' ) );
        wpbdp_register_settings_group(
            'licenses/main',
            esc_html__( 'Licenses', 'business-directory-plugin' ),
            'licenses',
            array(
                'desc'        => $this->get_settings_section_description(),
                'custom_form' => true,
            )
        );

        if ( true ) {
            wpbdp_register_setting(
                array(
                    'id'                  => 'empty_license_notice',
                    'name'                => '',
                    'type'                => 'no_licenses',
                    'group'               => 'licenses/main',
                )
            );
        }

        if ( $modules ) {
            wpbdp_register_settings_group( 'licenses/modules', esc_html__( 'Premium Modules', 'business-directory-plugin' ), 'licenses/main' );

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
            wpbdp_register_settings_group( 'licenses/themes', esc_html__( 'Themes', 'business-directory-plugin' ), 'licenses/main' );

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
        if ( $this->items ) {
            $ip_address = $this->get_server_ip_address();

            if ( ! $ip_address ) {
                return '';
            }

            return sprintf(
                /* translators: server's IP address */
                esc_html__( 'The IP address of your server is %s. Please make sure to include that information if you need to contact support about problems trying to activate your licenses.', 'business-directory-plugin' ),
                '<strong>' . esc_html( $ip_address ) . '</strong>'
            );
        }

        $html  = '';
        $html .= '<div class="wpbdp_upgrade_to_pro">';
        $html .= '<h3>';
        $html .= '<span>';
        $html .= esc_html__( 'Build more powerful directories with category images, maps, filter by location, payment gateways, and more.', 'business-directory-plugin' );
        $html .= '<br />';
        $html .= '<br />';
        $html .= '<a href="https://businessdirectoryplugin.com/pricing/?utm_source=WordPress&utm_medium=licenses_tab&utm_campaign=liteplugin" target="_blank">' . esc_html__( 'Upgrade Now!', 'business-directory-plugin' ) . '</a>';
        $html .= '</span>';
        $html .= '<br /><br /><br />';
        $html .= esc_html__( 'Already purchased?', 'business-directory-plugin' ) . ' ';
        $html .= sprintf(
            /* translators: %1$s: open link html, %2$s: close link html */
            esc_html__( 'Please see our detailed %1$sInstallation guide%2$s', 'business-directory-plugin' ),
            '<a href="https://businessdirectoryplugin.com/knowledge-base/installation-guide/">',
            '</a>'
        );
        $html .= '</h3>';
        $html .= '</div>';

        return $html;
    }

    public function license_key_setting( $setting, $value ) {
        $item_type = $setting['licensing_item_type'];
        $item_id   = $setting['licensing_item'];

        $license_status    = ! empty( $this->licenses_errors ) && array_key_exists( $item_id, $this->licenses_errors ) ? 'not-verified' : '';
        $activate_btn_text = __( 'Verify', 'business-directory-plugin' );
        $tooltip_msg       = sprintf(
            /* translators: %s: item type. */
            __( '%s will not get updates until license is verified.', 'business-directory-plugin' ),
            esc_html( ucwords( $item_type ) )
        );
        
        if ( empty( $license_status ) ) {
            $license_status       = $this->get_license_status( $value, $item_id, $item_type );
            $activate_btn_text    = esc_attr__( 'Activate', 'business-directory-plugin' );
            $tooltip_msg          = sprintf(
                /* translators: %s: item type. */
                __( '%s will not work until a valid license is provided.', 'business-directory-plugin' ),
                esc_html( ucwords( $item_type ) )
            );
            
        }

        $licensing_info_attr = wp_json_encode(
            array(
                'setting'   => $setting['id'],
                'item_type' => $item_type,
                'item_id'   => $item_id,
                'status'    => $license_status,
                'nonce'     => wp_create_nonce( 'license activation' ),
            )
        );

        $html  = '';
        $html .= '<div class="wpbdp-license-key-activation-ui wpbdp-license-status-' . esc_attr( $license_status ) . '" data-licensing="' . esc_attr( $licensing_info_attr ) . '">';
        $html .= '<span class="wpbdp-setting-tooltip wpbdp-tooltip wpbdp-license-warning-icon dashicons dashicons-warning" title="' . esc_attr( $tooltip_msg ) . '"></span>';
        $html .= '<span class="wpbdp-license-ok-icon dashicons dashicons-yes"></span>';
        $html .= '<input type="text" id="' . esc_attr( $setting['id'] ) . '" class="wpbdp-license-key-input" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . ']" value="' . esc_attr( $value ) . '" ' . ( 'valid' == $license_status ? 'readonly="readonly"' : '' ) . ' placeholder="' . esc_attr__( 'Enter License Key here', 'business-directory-plugin' ) . '"/>';
        $html .= '<input type="button" value="' . esc_attr( $activate_btn_text ) . '" data-working-msg="' . esc_attr__( 'Please wait...', 'business-directory-plugin' ) . '" class="button button-primary wpbdp-license-key-activate-btn" />';
        $html .= '<input type="button" value="' . esc_attr__( 'Deactivate', 'business-directory-plugin' ) . '" data-working-msg="' . esc_attr__( 'Please wait...', 'business-directory-plugin' ) . '" class="button wpbdp-license-key-deactivate-btn" />';
        $html .= '<div class="wpbdp-license-key-activation-status-msg wpbdp-hidden"></div>';
        $html .= '</div>';

        return $html;
    }

    public function empty_license_notice( $setting, $value ) {
        return '';
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

    private function license_action( $item_type, $item_id, $action ) {
        if ( ! in_array( $item_id, array_keys( $this->items ), true ) ) {
            return new WP_Error( 'invalid-module', esc_html__( 'Invalid item ID', 'business-directory-plugin' ), $module );
        }

        if ( 'deactivate' === $action ) {
            unset( $this->licenses[ $item_type . '-' . $item_id ] );
            update_option( 'wpbdp_licenses', $this->licenses );
        }

        $key = wpbdp_get_option( 'license-key-' . $item_type . '-' . $item_id );

        if ( ! $key ) {
            return new WP_Error( 'no-license-provided', esc_html__( 'No license key provided', 'business-directory-plugin' ) );
        }

        $request = array(
            'edd_action' => $action . '_license',
            'license'    => $key,
            'item_name'  => urlencode( $this->items[ $item_id ]['name'] ),
            'url'        => home_url(),
        );

        // Call the licensing server.
        $response = $this->license_request( add_query_arg( $request, self::STORE_URL ) );

        if ( is_wp_error( $response ) ) {
            if ( 'check' === $action ) {
                $this->licenses_errors[ $item_id ] = $response->get_error_message();
                update_option( 'wpbdp_licenses_errors', $this->licenses_errors );
            }
            return $response;
        }

        if ( 'deactivate' !== $action ) {
            $license = $this->process_license_response( $response, $item_type, $item_id, $key );

            if( is_wp_error( $license ) ) {
                $this->licenses_errors[ $item_id ] = $license->get_error_message();
                update_option( 'wpbdp_licenses_errors', $this->licenses_errors );
                return $license;
            }

            if ( isset( $this->licenses_errors[ $item_id ] ) ) {
                unset( $this->licenses_errors[ $item_id ] );
                update_option( 'wpbdp_licenses_errors', $this->licenses_errors );
            }

            $this->licenses[$item_type . '-' . $item_id] = $license;
            update_option( 'wpbdp_licenses', $this->licenses );

            return $license;
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) ) {
            return new WP_Error( 'invalid-license', esc_html__( 'License key is invalid', 'business-directory-plugin' ) );
        }

        if ( 'deactivated' !== $license_data->license ) {
            return new WP_Error( 'deactivation-failed', esc_html__( 'Deactivation failed', 'business-directory-plugin' ) );
        }

        return true;
    }

    private function process_license_response( $response, $item_type, $item_id, $key ) {
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! is_object( $license_data ) || ! $license_data || ! isset( $license_data->license ) || 'valid' !== $license_data->license ) {
            update_option( 'wpbdp_licenses', $this->licenses );

            if ( isset( $license_data->error ) && 'revoked' === $license_data->error ) {
                $this->licenses[ $item_type . '-' . $item_id ]['status'] = 'invalid';
                $message  = '<strong>' . esc_html__( 'The license key was revoked.', 'business-directory-plugin' ) . '</strong>';
                $message .= '<br/><br/>';
                $message .= sprintf(
                    /* translators: %1%s: opening <a> tag, %2$s: closing </a> tag */
                    esc_html__( 'If you think this is a mistake, please contact %1$sBusiness Directory support%2$s and let them know your license is being reported as revoked by the licensing software.', 'business-directory-plugin' ),
                    '<a href="https://businessdirectoryplugin.com/contact">',
                    '</a>'
                );
                $message .= '<br/><br/>';
                $message .= sprintf(
                    /* translators: Module name */
                    esc_html__( 'Please include the email address you used to purchase %s with your report.', 'business-directory-plugin' ),
                    '<strong>' . $this->items[ $item_id ]['name'] . '</strong>'
                );


                // The javascript handler already adds a dot at the end.
                $message = rtrim( $message, '.' );

                return new WP_Error( 'revoked-license', $message );
            } else {
                $message = esc_html__( 'License key is invalid', 'business-directory-plugin' );

                return new WP_Error( 'invalid-license', $message );
            }
        }

        return array(
            'license_key'  => $key,
            'status'       => 'valid',
            'expires'      => $license_data->expires,
            'last_checked' => time(),
        );
    }

    private function handle_failed_license_request( $response ) {
        if ( ! function_exists( 'curl_init' ) ) {
            $message  = '<strong>' . esc_html__( 'It was not possible to establish a connection with the Business Directory server. cURL was not found in your system', 'business-directory-plugin' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= esc_html__( 'To ensure the security of our systems and adhere to industry best practices, we require that your server uses a recent version of cURL and a version of OpenSSL that supports TLSv1.2 (minimum version with support is OpenSSL 1.0.1c).', 'business-directory-plugin' );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Upgrading your system will not only allow you to communicate with Business Directory servers but also help you prepare your website to interact with services using the latest security standards.', 'business-directory-plugin' );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Please contact your hosting provider and ask them to upgrade your system. Include this message if necessary', 'business-directory-plugin' );
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
            $message  = '<strong>' . esc_html__( 'It was not possible to establish a connection with the Business Directory server. The connection failed with the following error:', 'business-directory-plugin' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= '<code>curl: (' . esc_html( $error_number ) . ') ' . esc_html( $error_message ) . '</code>';
            $message .= '<br/><br/>';
            $message .= sprintf(
                /* translators: %1%s: opening <a> tag, %2$s: closing </a> tag, %3$s site IP address. */
                esc_html__( 'It looks like your server is not authorized to make requests to Business Directory servers. Please contact %1$sBusiness Directory support%2$s and let them know about this problem. Don\'t forget to include your IP address: %3$s.', 'business-directory-plugin' ),
                '<a href="https://businessdirectoryplugin.com/contact">',
                '</a>',
                '<b>' . esc_html( $this->get_server_ip_address() ) . '</b>'
            );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Include this error message with your report.', 'business-directory-plugin' );

            // The javascript handler already adds a dot at the end.
            $message = rtrim( $message, '.' );

            return new WP_Error( 'connection-refused', $message );
        } elseif ( in_array( $error_number, array( 35 ), true ) ) {
            $message = '<strong>' . esc_html__( 'It was not possible to establish a connection with the Business Directory server. A problem occurred in the SSL/TSL handshake:', 'business-directory-plugin' ) . '</strong>';

            $message .= '<br/><br/>';
            $message .= '<code>curl: (' . esc_html( $error_number ) . ') ' . esc_html( $error_message ) . '</code>';
            $message .= '<br/><br/>';
            $message .= esc_html__( 'To ensure the security of our systems and adhere to industry best practices, we require that your server uses a recent version of cURL and a version of OpenSSL that supports TLSv1.2 (minimum version with support is OpenSSL 1.0.1c).', 'business-directory-plugin' );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Upgrading your system will not only allow you to communicate with Business Directory servers but also help you prepare your website to interact with services using the latest security standards.', 'business-directory-plugin' );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Please contact your hosting provider and ask them to upgrade your system. Include this message if necessary.', 'business-directory-plugin' );

            // The javascript handler already adds a dot at the end.
            $message = rtrim( $message, '.' );

            return new WP_Error( 'request-failed', $message );
        } else {
            return new WP_Error( 'request-failed', esc_html__( 'Could not contact licensing server', 'business-directory-plugin' ) );
        }
    }

    private function license_request( $url ) {
        // Call the licensing server.
        $response = wp_remote_get(
            $url,
            array(
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
            $message  = '<strong>' . esc_html__( 'The server returned a 403 Forbidden error.', 'business-directory-plugin' ) . '</strong>';
            $message .= '<br/><br/>';
            $message .= sprintf(
                /* translators: %1%s: opening <a> tag, %2$s: closing </a> tag, %3$s site IP address. */
                esc_html__( 'It looks like your server is not authorized to make requests to Business Directory servers. Please contact %1$sBusiness Directory support%2$s and let them know about this problem. Don\'t forget to include your IP address: %3$s.', 'business-directory-plugin' ),
                '<a href="https://businessdirectoryplugin.com/contact">',
                '</a>',
                '<b>' . esc_html( $this->get_server_ip_address() ) . '</b>'
            );
            $message .= '<br/><br/>';
            $message .= esc_html__( 'Include this error message with your report.', 'business-directory-plugin' );

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

        $page = wpbdp_get_var( array( 'param' => 'page' ) );
        $tab  = wpbdp_get_var( array( 'param' => 'tab' ) );

        if ( isset( $this->items['business-directory-categories'] ) && version_compare( $this->items['business-directory-categories']['version'], '5.0.12', 'le' ) ) {
            $this->render_categories_license_notice( $this->items['business-directory-categories'] );
        }

        if ( in_array( $pagenow, array( 'admin.php', 'edit.php' ) ) && 'wpbdp_settings' === $page && 'licenses' === $tab ) {
            return;
        }

        $expired      = array();
        $invalid      = array();

        foreach ( $this->items as $item ) {
            $license_info = array(
                'item_type'   => $item['item_type'],
                'status'      => $this->get_license_status( '', $item['id'], $item['item_type'] ),
                'name'        => $item['name'],
                'license_key' => wpbdp_get_option( 'license-key-' . $item['item_type'] . '-' . $item['id'] ),
            );

            if ( 'expired' === $license_info['status'] ) {
                $expired[] = $license_info;
            } elseif ( 'valid' !== $license_info['status'] ) {
                $invalid[] = $license_info;
            }
        }

        $this->render_invalid_license_admin_notice( $invalid );
        $this->render_expired_license_admin_notice( $expired );
        $this->render_licenses_status_update_notices();
    }

    private function render_categories_license_notice( $categories_item ) {
        if ( 'wpbdp_settings' !== wpbdp_get_var( array( 'param' => 'page' ) ) ||
            'invalid' !== $this->get_license_status( '', $categories_item['id'], $categories_item['item_type'] ) ||
            ! empty( wpbdp_get_option( 'license-key-' . $categories_item['item_type'] . '-' . $categories_item['id'] ) ) ) {
            return;
        }

        $notice_id = 'categories_license';

        $transient_key = "wpbdp-notice-dismissed-{$notice_id}-" . get_current_user_id();

        if ( get_transient( $transient_key ) || 'wpbdp_settings' !== wpbdp_get_var( array( 'param' => 'page' ) ) ) {
            return;
        }

        $content = sprintf(
            esc_html__( 'future versions will require a license key. You can get one for free %1$shere%2$s', 'business-directory-plugin' ),
            '<a href="https://businessdirectoryplugin.com/upgrade-categories/?utm_source=WordPress&utm_medium=freecategories&utm_campaign=liteplugin">',
            '</a>'
        );

        $title = esc_html__( 'Business Directory - Enhanced Categories Module', 'business-directory-plugin' );

        $this->render_notice( $content, $notice_id, true, $title, 'warning' );
    }

    private function render_invalid_license_admin_notice( $invalid ) {
        if ( empty( $invalid ) || 'wpbdp_settings' !== wpbdp_get_var( array( 'param' => 'page' ) ) ) {
            return;
        }

        $content  = esc_html( _n( 'License key missing.', 'License keys missing.', count( $invalid ), 'business-directory-plugin' ) ) . ' ';
        $content .= esc_html__( 'Premium modules and themes will not work until a valid license key is provided.', 'business-directory-plugin' );
        $content .= ' <a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '">';
        $content .= esc_html__( 'Check Licenses', 'business-directory-plugin' );
        $content .= '</a>';

        $this->render_notice( $content, '', false );
    }

    private function render_expired_license_admin_notice() {
        $notice_id = 'expired_licenses';

        $transient_key = "wpbdp-notice-dismissed-{$notice_id}-" . get_current_user_id();

        if ( empty( $expired ) || get_transient( $transient_key ) || 'wpbdp_settings' !== wpbdp_get_var( array( 'param' => 'page' ) ) ) {
            return;
        }

        $content  = esc_html( _n( 'License key expired.', 'License keys expired.', count( $expired ), 'business-directory-plugin' ) ) . ' ';
        $content .= esc_html( 'Premium modules and themes will continue to work but you will not receive any more updates until the license is renewed.', 'business-directory-plugin' );
        $content .= ' <a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '">';
        $content .= esc_html__( 'Check Licenses', 'business-directory-plugin' );
        $content .= '</a>';

        $this->render_notice( $content, $notice_id );
    }

    private function render_licenses_status_update_notices() {
        $notice_id = 'license_status_error';
        $transient_key = "wpbdp-notice-dismissed-{$notice_id}-" . get_current_user_id();

        if ( get_transient( $transient_key ) || empty( $this->licenses_errors ) || 'wpbdp_settings' !== wpbdp_get_var( array( 'param' => 'page' ) ) ) {
            return;
        }

        $content = esc_html( _n( 'Could not verify license.', 'Could not verify licenses.', count( $this->licenses_errors ), 'business-directory-plugin' ) );
        $content .= ' <a href="' . esc_url( admin_url( 'admin.php?page=wpbdp_settings&tab=licenses' ) ) . '">';
        $content .= esc_html__( 'Check Licenses', 'business-directory-plugin' );
        $content .= '</a>';

        $this->render_notice( $content, $notice_id );
        
    }

    private function render_notice( $content, $notice_id = '', $dismissible = true, $title = '', $type='error' ) {
        if ( empty( $title ) && 'error' === $type ) {
            $title = __( 'Business Directory error', 'business-directory-plugin' );
        }

        $nonce = ! empty( $dismissible ) ? wp_create_nonce( 'dismiss notice ' . $notice_id ) : '';

        echo '<div id="wpbdp-licensing-issues-warning" class="wpbdp-notice notice notice-' . esc_attr( $type );
        echo ! $dismissible ? '' : ' is-dismissible" data-dismissible-id="' . esc_attr( $notice_id ) . '" data-nonce="' . esc_attr( $nonce );
        echo '">';
        echo '<p>';
        echo '<b>' . esc_html( $title ) . ':</b> ';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<span>' . $content . '</span>';
        echo '</p>';
        echo '</div>';

    }

    public function dismiss_notification() {
        $nonce  = wpbdp_get_var( array( 'param' => 'nonce'), 'post' );
        $id     = wpbdp_get_var( array( 'param' => 'id'), 'post' );
        $time   = 'expired_licenses' == $id ? 2 * WEEK_IN_SECONDS : WEEK_IN_SECONDS;
        if ( wp_verify_nonce( $nonce, 'dismiss notice ' . $id ) ) {
            set_transient( "wpbdp-notice-dismissed-{$id}-" . get_current_user_id(), $time );
        }
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
        // This verifies all licenses, clear licenses_errors property.
        $this->licenses_errors = array();

        foreach ( $this->items as $item_id => $item ) {
            $item_key = $item['item_type'] . '-' . $item['id'];
            $key      = wpbdp_get_option( 'license-key-' . $item_key );

            if ( ! $key ) {
                $licenses[ $item_key ] = array(
                    'status'       => 'invalid',
                    'last_checked' => time(),
                );
                continue;
            }

            $response = $this->license_action( $item['item_type'], $item['id'], 'check' );

            if ( is_wp_error( $response ) ) {
                $licenses[ $item_key ] = $this->licenses[$item_key];
                $this->licenses_errors[ $item_id ]    = $response->get_error_message();
                continue;
            }

            $licenses[ $item_key ] = $response;
        }

        update_option( 'wpbdp_licenses_errors', $this->licenses_errors );

        return $licenses;
    }

    public function ajax_activate_license() {
        $nonce = wpbdp_get_var( array( 'param' => 'nonce' ), 'post' );
        if ( ! wp_verify_nonce( $nonce, 'license activation' ) ) {
            die();
        }

        $setting_id = wpbdp_get_var( array( 'param' => 'setting' ), 'post' );
        $key        = wpbdp_get_var( array( 'param' => 'license_key' ), 'post' );
        $item_type  = wpbdp_get_var( array( 'param' => 'item_type' ), 'post' );
        $item_id    = wpbdp_get_var( array( 'param' => 'item_id' ), 'post' );

        if ( ! $setting_id || ! $item_type || ! $item_id ) {
            wp_send_json_error(
                array( 
                    'error' => esc_html__( 'Missing data. Please reload this page and try again.', 'business-directory-plugin' )
                )
            );
        }

        if ( ! $key ) {
            wp_send_json_error(
                array( 
                    'error' => esc_html__( 'Please enter a license key.', 'business-directory-plugin' )
                )
            );
        }

        // Store the new license key. This clears stored information about the license.
        wpbdp_set_option( 'license-key-' . $item_type . '-' . $item_id, $key );

        if ( isset( $this->licenses[ $item_type . '-' . $item_id ]['status'] ) && 'valid' === $this->licenses[ $item_type . '-' . $item_id ]['status'] ) {
            $result = $this->license_action( $item_type, $item_id, 'check' );

            if ( is_wp_error( $result ) ) {
                wp_send_json_error(
                    array(
                        'error' => sprintf(
                            /* translators: %s: error message */
                            esc_html__( 'Could not verify license: %s.', 'business-directory-plugin' ),
                            $result->get_error_message()
                        ),
                    )
                );
            } else {
                wp_send_json_success(
                    array(
                        'message' => esc_html__( 'License verified', 'business-directory-plugin' ),
                    )
                );
            }
        }

        $result = $this->license_action( $item_type, $item_id, 'activate' );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'error' => sprintf(
                        /* translators: %s: error message */
                        esc_html__( 'Could not activate license: %s.', 'business-directory-plugin' ),
                        $result->get_error_message()
                    ),
                )
            );
        } else {
            wp_send_json_success(
                array(
                    'message' => esc_html__( 'License activated', 'business-directory-plugin' ),
                )
            );
        }
    }

    public function ajax_deactivate_license() {
        $nonce = wpbdp_get_var( array( 'param' => 'nonce' ), 'post' );
        if ( ! wp_verify_nonce( $nonce, 'license activation' ) ) {
            die();
        }

        $setting_id = wpbdp_get_var( array( 'param' => 'setting' ), 'post' );
        $key        = wpbdp_get_var( array( 'param' => 'license_key' ), 'post' );
        $item_type  = wpbdp_get_var( array( 'param' => 'item_type' ), 'post' );
        $item_id    = wpbdp_get_var( array( 'param' => 'item_id' ), 'post' );

        if ( ! $setting_id || ! $key || ! $item_type || ! $item_id ) {
            die();
        }

        $result = $this->license_action( $item_type, $item_id, 'deactivate' );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array (
                    'error' => sprintf(
                        /* translators: %s: error message */
                        esc_html__( 'Could not deactivate license: %s.', 'business-directory-plugin' ),
                        $result->get_error_message()
                    )
                )
            );
        } else {
            wp_send_json_success(
                array(
                    'message' => esc_html__( 'License deactivated', 'business-directory-plugin' ),
                )
            );
        }
    }

    public function get_version_information( $force_refresh = false ) {
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
        $needs_refresh = false === $updates || $force_refresh;

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
            self::STORE_URL,
            array(
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

        $item = isset( $this->items[ $args->slug ] ) ? $this->items[ $args->slug ] : false;
        if ( ! $item ) {
            return $data;
        }

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

    /* translators: %1$s: Module name, %2$s Module version */
    wpbdp_deprecation_warning( sprintf( esc_html__( '"%1$s" version %2$s is not compatible with Business Directory Plugin 5.0. Please update this module to the latest available version.', 'business-directory-plugin' ), '<strong>' . esc_html( $name ) . '</strong>', '<strong>' . esc_html( $version ) . '</strong>' ) );
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
