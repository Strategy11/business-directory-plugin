<?php
class WPBDP__Settings_Admin {

    /**
     * Used to lookup sections by ID during the section callback execution.
     */
    private $sections_by_id = array();


    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'wpbdp_admin_menu_items', array( $this, 'menu_item' ) );
    }

    public function enqueue_scripts( $hook ) {
        // strstr() until https://core.trac.wordpress.org/ticket/18857 is fixed.
        if ( false !== strstr( $hook, 'wpbdp_settings' ) ) {
            wp_enqueue_script( 'wpbdp-admin-settings', WPBDP_URL . 'assets/js/admin-settings.js' );
        }
    }

    public function menu_item( $menu ) {
        $menu['wpbdp_settings'] = array(
            'title'    => _x( 'Manage Options', 'admin menu', 'WPBDM' ),
            'callback' => array( $this, 'settings_page' ),
            'priority' => 2
        );
        return $menu;
    }

    public function register_settings() {
        $all_groups = wpbdp()->settings->get_registered_groups();
        $non_tabs   = wp_list_filter( $all_groups, array( 'type' => 'tab' ), 'NOT' );

        foreach ( $non_tabs as $group_id => $group ) {
            switch ( $group['type'] ) {
            case 'subtab':
                add_settings_section(
                    'wpbdp_settings_subtab_' . $group_id,
                    '',
                    '__return_false',
                    'wpbdp_settings_subtab_' . $group_id
                );
                break;
            case 'section':
                add_settings_section(
                    'wpbdp_settings_subtab_' . $group['parent'] . '_' . $group_id,
                    $group['title'],
                    array( $this, 'section_header_callback' ),
                    'wpbdp_settings_subtab_' . $group['parent']
                );
                break;
            default:
                break;
            }
        }

        foreach ( wpbdp()->settings->get_registered_settings() as $setting_id => $setting ) {
            $args = array_merge(
                array(
                    'label_for' => $setting['id'],
                    'class'     => '',
                    'desc'      => '',
                    'tooltip'   => ''
                ),
                $setting
            );

            if ( 'silent' == $setting['type'] ) {
                continue;
            }

            if ( isset( $all_groups[ $args['group'] ] ) ) {
                switch ( $all_groups[ $args['group'] ]['type'] ) {
                case 'subtab':
                    $subtab_group = 'wpbdp_settings_subtab_' . $args['group'];
                    $section_group = $subtab_group;
                    break;
                case 'section':
                    $subtab_group = 'wpbdp_settings_subtab_' . $all_groups[ $args['group'] ]['parent'];
                    $section_group = $subtab_group . '_' . $args['group'];
                    break;
                }
            } else {
                wpbdp_debug_e( 'group not found: ', $args );
            }

            add_settings_field(
                'wpbdp_settings[' . $args['id'] . ']',
                $args['name'],
                array( $this, 'setting_callback' ),
                $subtab_group,
                $section_group,
                $args
            );
        }
    }

    public function section_header_callback( $wp_section ) {
        return;

        if ( ! empty( $section['desc'] ) ) {
            echo '<p class="wpbdp-setting-description wpbdp-settings-section-description">';
            echo $section['desc'];
            echo '</p>';
        }
    }

    public function setting_callback( $setting ) {
        if ( 'callback' == $setting['type'] ) {
            if ( ! empty( $setting['callback'] ) && is_callable( $setting['callback'] ) ) {
                $callback_html = call_user_func( $setting['callback'], $setting );
            } else {
                $callback_html = 'Missing callback';
            }
        } else {
            $value = wpbdp()->settings->get_option( $setting['id'] );

            ob_start();

            if ( method_exists( $this, 'setting_' . $setting['type'] . '_callback' ) ) {
                call_user_func( array( $this, 'setting_' . $setting['type'] . '_callback' ), $setting, $value );
            } else {
                $this->setting_missing_callback( $setting, $value );
            }

            $callback_html = ob_get_clean();
        }

        if ( ! empty( $setting['attrs'] ) ) {
            $attrs = wpbdp_html_attributes( $setting['attrs'], array( 'id', 'class' ) );
        } else {
            $attrs = '';
        }

        $attrs .= ' data-setting-id="' . esc_attr( $setting['id'] ) . '" ';

        if ( ! empty( $setting['requirements'] ) ) {
            $reqs_info = array();

            foreach ( $setting['requirements'] as $r ) {
                $reqs_info[] = array( $r, (bool) wpbdp_get_option( str_replace( '!', '', $r ) ) );
            }

            $attrs .= ' data-requirements="' . esc_attr( json_encode( $reqs_info ) ) . '"';
        }

        $html  = '';
        $html .= '<div id="wpbdp-settings-' . $setting['id'] . '" class="wpbdp-settings-setting wpbdp-settings-type-' . $setting['type'] . '" ' . $attrs . '>';
        $html .= apply_filters( 'wpbdp_admin_settings_render', $callback_html, $setting );
        $html .= '<a name="' . $setting['id'] . '"></a>';
        $html .= '</div>';

        echo $html;
    }

    public function setting_tooltip( $tooltip = '' ) {
        if ( ! $tooltip ) {
            return;
        }

        return '<span class="wpbdp-setting-tooltip wpbdp-tooltip dashicons dashicons-editor-help" title="' . esc_attr( $tooltip ) . '"></span>';
    }

    public function setting_missing_callback( $setting, $value ) {
        if ( has_filter( 'wpbdp_setting_type_' . $setting['type'] ) ) {
            echo apply_filters( 'wpbdp_setting_type_' . $setting['type'], $setting, $value );
        } else {
            echo 'Callback Missing';
        }
    }

    public function setting_text_callback( $setting, $value ) {
        echo '<input type="text" id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $value ) . '" placeholder="' . ( ! empty( $setting['placeholder'] ) ? esc_attr( $setting['placeholder'] ) : '' ) . '" />';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">'  . wp_kses_post( $setting['desc'] ) . '</span>';
        }
    }

    public function setting_number_callback( $setting, $value ) {
        echo '<input type="number" id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $value ) . '"';

        if ( isset( $setting['min'] ) ) {
            echo 'min="' . $setting['min'] . '"';
        }

        if ( isset( $setting['step'] ) ) {
            echo 'step="' . $setting['step'] . '"';
        }
        echo '/>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">'  . wp_kses_post( $setting['desc'] ) . '</span>';
        }
    }

    public function setting_textarea_callback( $setting, $value ) {
        echo '<textarea id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']" placeholder="' . ( ! empty( $setting['placeholder'] ) ? esc_attr( $setting['placeholder'] ) : '' ) . '">';
        echo esc_textarea( $value );
        echo '</textarea>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }
    }

    public function setting_checkbox_callback( $setting, $value ) {
        $value = (boolean) $value;

        echo '<input type="hidden" name="wpbdp_settings[' . $setting['id'] . ']" value="-1" />';
        echo '<input type="checkbox" id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']" value="1" ' . checked( $value, 1, false ) . ' />';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<label for="' . $setting['id'] . '">' . $setting['desc'] . '</label>';
        }

        if ( ! empty( $setting['tooltip'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['tooltip'] . '</span>';
        }
    }

    public function setting_radio_callback( $setting, $value ) {
        if ( empty( $setting['options'] ) ) {
            return;
        }

        echo '<div class="wpbdp-settings-radio-options">';
        foreach ( $setting['options'] as $option_value => $option_label ) {
            echo '<div class="wpbdp-settings-radio-option">';
            echo '<input type="radio" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $option_value ) . '" ' . checked( $option_value, $value, false ) . ' id="wpbdp-settings-' . $setting['id'] . '-radio-' . $option_value . '" />';
            echo '<label>';
            echo $option_label;
            echo '</label>';
            echo '</div>';
        }
        echo '</div>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }
    }

    public function setting_multicheck_callback( $setting, $value ) {
        if ( empty( $setting['options'] ) ) {
            return;
        }

        $value = (array) $value;

        echo '<input type="hidden" name="wpbdp_settings[' . $setting['id'] . ']" value="-1" />';

        echo '<div class="wpbdp-settings-multicheck-options">';
        $n = 0;
        foreach ( $setting['options'] as $option_value => $option_label ) {
            echo '<div class="wpbdp-settings-multicheck-option wpbdp-settings-multicheck-option-no-' . $n . '">';
            echo '<input type="checkbox" name="wpbdp_settings[' . $setting['id'] . '][]" id="wpbdp-' . $setting['id'] . '-checkbox-no-' . $n . '" value="' . esc_attr( $option_value ) . '" ' . checked( in_array( $option_value, $value ), true, false ) . ' />';
            echo '<label for="wpbdp-' . $setting['id'] . '-checkbox-no-' . $n . '">';
            echo $option_label;
            echo '</label>';
            echo '</div>';

            $n++;
        }

        echo '</div>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }
    }

    public function setting_select_callback( $setting, $value ) {
        if ( empty( $setting['options'] ) ) {
            return;
        }

        $multiple = ! empty( $setting['multiple'] ) && $setting['multiple'];

        echo '<select id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']' . ( $multiple ? '[]' : '' ) . '" ' . ( $multiple ? 'multiple="multiple"' : '' ) . '>';
        foreach ( $setting['options'] as $option_value => $option_label ) {
            if ( $multiple ) {
                $selected = in_array( $option_value, $value );
            } else {
                $selected = ( $option_value == $value );
            }

            echo '<option value="' . $option_value . '" ' . selected( $selected, true, false ) . '>' . $option_label . '</option>';
        }
        echo '</select>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }
    }

    public function setting_email_template_callback( $setting, $value ) {
        if ( ! is_array( $value ) ) {
            $value = array(
                'subject' => $setting['default']['subject'],
                'body'    => $value
            );
        }

        $args = array(
            'setting_name'  => 'wpbdp_settings[' . $setting['id'] . ']',
            'email_subject' => $value['subject'],
            'email_body'    => $value['body'],
            'placeholders'  => ! empty( $setting['placeholders'] ) ? $setting['placeholders'] : array()
        );

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }

        echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-email.tpl.php', $args );
    }

    public function setting_expiration_notices_callback( $setting, $value ) {
?>
<div class="wpbdp-settings-expiration-notices">
    <button id="wpbdp-settings-expiration-notices-add-btn" class="button"><?php _ex( 'Add notice', 'expiration notices', 'WPBDM' ); ?></button>

    <div id="wpbdp-settings-expiration-notices-add">
    <?php
    $n = max( array_keys( $value ) ) + 1;
    echo wpbdp_render_page(
        WPBDP_PATH . 'templates/admin/settings-email.tpl.php',
        array(
            'setting_name' => 'new_notice[' . $n . ']',
            'uid' => '',
            'container_class' => 'wpbdp-expiration-notice-email',
            'extra_fields' => $this->setting_expiration_notices_email_extra_fields( 'new_notice[' . $n . ']', '', null ),
            'editor_only' => true
        )
    );
    ?>
    </div>

    <?php if ( ! $value ): ?>
    <p class="wpbdp-no-items"><?php _ex( 'No notices configured.', 'expiration notices', 'WPBDM' ); ?></p>
    <?php endif; ?>

<?php
        foreach ( $value as $i => $notice ) {
            $uid = uniqid( 'wpbdp-settings-email-' );
            $vars = array(
                'setting_name' => 'wpbdp_settings[' . $setting['id'] . '][' . $i . ']',
                'uid' => $uid,
                'container_class' => 'wpbdp-expiration-notice-email',
                'email_subject' => $notice['subject'],
                'email_body' => $notice['body'],
                'extra_fields' => $this->setting_expiration_notices_email_extra_fields( 'wpbdp_settings[' . $setting['id'] . '][' . $i . ']', $uid, $notice ),
                'after_container' => $this->setting_expiration_notices_email_summary( $notice ),
                'before_buttons' => '<a href="#" class="delete">' . _x( 'Delete', 'expiration notices', 'WPBDM' ) . '</a>'
            );

            echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-email.tpl.php', $vars );
        }
?>
</div>
<?php
    }

    private function setting_expiration_notices_email_summary( $notice ) {
        $event = $notice['event'];
        $listings = $notice['listings'];
        $relative_time = ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : '';

        if ( 'both' == $listings ) {
            $recurring_modifier = _x( 'recurring and non-recurring', 'expiration notices', 'WPBDM' );
        } elseif ( 'recurring' == $listings ) {
            $recurring_modifier = _x( 'recurring only', 'expiration notices', 'WPBDM' );
        } else {
            $recurring_modifier = _x( 'non-recurring only', 'expiration notices', 'WPBDM' );
        }

        if ( 'renewal' == $event ) {
            $summary = sprintf( _x( 'Sent when a listing (%s) is renewed.', 'expiration notices', 'WPBDM' ), $recurring_modifier );
        }

        if ( 'expiration' == $event ) {
            if ( '0 days' == $relative_time ) {
                $summary = sprintf( _x( 'Sent when a listing (%s) expires.', 'expiration notices', 'WPBDM' ), $recurring_modifier );
            } else {
                $relative_time_parts = explode( ' ', $relative_time );
                $relative_time_number = trim( str_replace( array( '+', '-' ), '', $relative_time_parts[0] ) );
                $relative_time_units = $relative_time_parts[1];

                switch ( $relative_time_units ) {
                case 'days':
                    $relative_time_h = sprintf( _nx( '%d day', '%d days', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                case 'weeks':
                    $relative_time_h = sprintf( _nx( '%d week', '%d weeks', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                case 'months':
                    $relative_time_h = sprintf( _nx( '%d month', '%d months', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                }

                if ( $relative_time[0] == '+' ) {
                    $summary = sprintf( _x( 'Sent %1$s before a listing (%2$s) expires.', 'expiration notices', 'WPBDM' ), $relative_time_h, $recurring_modifier );
                } else {
                    $summary = sprintf( _x( 'Sent %1$s after a listing (%2$s) expires.', 'expiration notices', 'WPBDM' ), $relative_time_h, $recurring_modifier );
                }
            }
        }

        ob_start();
?>
<div class="wpbdp-expiration-notice-email-schedule-summary">
    <?php echo $summary; ?>
</div>
<?php
        return ob_get_clean();
    }

    private function setting_expiration_notices_schedule() {
        // Notices schedule.
        $notices_schedule = array(
            array( 'expiration', '0 days', _x( 'At the time of expiration', 'expiration notices', 'WPBDM' ) ),
            array( 'renewal', '0 days', _x( 'Right after a successful renewal', 'expiration notices', 'WPBDM' ) )
        );
        foreach ( array( 'days'   => array( 1, 2, 3, 4, 5 ),
                         'weeks'  => array( 1, 2 ),
                         'months' => array( 1, 2 ) ) as $unit => $periods ) {
            foreach ( $periods as $i ) {
                foreach ( array( '+', '-' ) as $sign ) {
                    switch ( $unit ) {
                    case 'days':
                        $label = sprintf( '+' == $sign ? _nx( '%d day before expiration', '%d days before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d day after expiration', '%d days after expiration', $i, 'expiration notices', 'WPBDM' ), $i );
                        break;
                    case 'weeks':
                        $label = sprintf( '+' == $sign ? _nx( '%d week before expiration', '%d weeks before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d week after expiration', '%d weeks after expiration', $i, 'expiration notices', 'WPBDM' ), $i );
                        break;
                    case 'months':
                        $label = sprintf( '+' == $sign ? _nx( '%d month before expiration', '%d months before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d month after expiration', '%d months after expiration', $i, 'expiration notices', 'WPBDM' ), $i );
                        break;
                    }

                    $notices_schedule[] = array( 'expiration', $sign . $i . ' ' . $unit, $label );
                }
            }
        }

        return apply_filters( 'wpbdp_expiration_notices_schedule', $notices_schedule );
    }

    private function setting_expiration_notices_email_extra_fields( $name, $uid, $notice ) {
        if ( is_null( $notice ) ) {
            $notice = array( 'event' => 'expiration', 'listings' => 'both', 'relative_time' => '0 days', 'subject' => '', 'body' => '' );
        }

        ob_start();
?>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-listings"><?php _ex( 'Applies to', 'expiration notices', 'WPBDM' ); ?></label></th>
        <td>
            <select id="<?php echo $uid; ?>-listings" name="<?php echo $name; ?>[listings]">
                <option value="non-recurring" <?php selected( 'non-recurring', $notice['listings'] ); ?>><?php _ex( 'Non-recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
                <option value="recurring" <?php selected( 'recurring', $notice['listings'] ); ?>><?php _ex( 'Recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
                <option value="both" <?php selected( 'both', $notice['listings'] ); ?>><?php _ex( 'Recurring and non-recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-relative-time-and-event"><?php _ex( 'When to send?', 'expiration notices', 'WPBDM' ); ?></label></th>
        <td>
            <input type="hidden" value="<?php echo $notice['event']; ?>" class="stored-notice-event" />
            <input type="hidden" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="stored-notice-relative-time" />

            <input type="hidden" name="<?php echo $name; ?>[event]" value="<?php echo $notice['event']; ?>" class="notice-event" />
            <input type="hidden" name="<?php echo $name; ?>[relative_time]" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="notice-relative-time" />

            <select id="<?php echo $uid; ?>-relative-time-and-event" class="relative-time-and-event">
                <?php foreach ( $this->setting_expiration_notices_schedule() as $item ): ?>
                    <?php if ( 'renewal' == $item[0] ): ?>
                    <option value="<?php echo $item[0]; ?>,<?php echo $item[1]; ?>" <?php selected( $item[0], $notice['event'] ); ?>><?php echo $item[2]; ?></option>
                    <?php else: ?>
                    <option value="<?php echo $item[0]; ?>,<?php echo $item[1]; ?>" <?php selected( $item[0] == $notice['event'] && ! empty( $notice['relative_time'] ) && $item[1] == $notice['relative_time'], true ); ?>><?php echo $item[2]; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </td>
        </tr>
<?php
        return ob_get_clean();
    }


    public function settings_page() {
        $all_groups = wpbdp()->settings->get_registered_groups();

        // Filter out empty groups.
        $all_groups = wp_list_filter( $all_groups, array( 'count' => 0 ), 'NOT' );

        $tabs = wp_list_filter( $all_groups, array( 'type' => 'tab' ) );
        if ( ! empty( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ) {
            $active_tab = $_GET['tab'];
        } else {
            $active_tab = 'general';
        }

        $subtabs = wp_list_filter( $all_groups, array( 'parent' => $active_tab ) );
        if ( ! empty( $_GET['subtab'] ) && array_key_exists( $_GET['subtab'], $subtabs ) ) {
            $active_subtab = $_GET['subtab'];
        } else {
            $subtabs_ids = array_keys( $subtabs );
            $active_subtab = reset( $subtabs_ids );
        }

        $active_subtab_description = ! empty( $all_groups[ $active_subtab ]['desc'] ) ? $all_groups[ $active_subtab ]['desc'] : '';
        $custom_form = ( ! empty( $all_groups[ $active_subtab ]['custom_form'] ) ) && $all_groups[ $active_subtab ]['custom_form'];

        echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-page.tpl.php', compact( 'tabs', 'subtabs', 'active_tab', 'active_subtab', 'active_subtab_description', 'custom_form' ) );
    }

    // FIXME: before fees-revamp.
    // public function settings_reset_defaults() {
    //     $do_reset = ( ! empty ( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset defaults' ) );
    //
    //     if ( $do_reset ) {
    //         global $wpbdp;
    //         $wpbdp->settings->reset_defaults();
    //     }
    //
    //     wp_redirect( admin_url( 'admin.php?page=wpbdp_admin_settings&settings-updated=1&groupid=general' ) );
    //     exit();
    // }    

}
