<?php
/**
 * Class WPBDP__Settings_Admin
 *
 * @package BDP/Settings Admin
 */

class WPBDP__Settings_Admin {

    /**
     * Used to lookup sections by ID during the section callback execution.
     */
    private $sections_by_id = array();


    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'wpbdp_admin_menu_items', array( $this, 'menu_item' ) );

        // Reset settings action.
        add_action( 'wpbdp_action_reset-default-settings', array( &$this, 'settings_reset_defaults' ) );

        add_action( 'wp_ajax_wpbdp-file-upload', array( $this, '_ajax_file_upload' ) );
        add_action( 'wp_ajax_nopriv_wpbdp-file-upload', array( $this, '_ajax_file_upload' ) );

		add_filter( 'wpbdp_setting_type_pro_license', array( &$this, 'no_license' ), 20, 2 );
    }

    public function enqueue_scripts( $hook ) {
        // strstr() until https://core.trac.wordpress.org/ticket/18857 is fixed.
        if ( false !== strstr( $hook, 'wpbdp_settings' ) ) {
            wp_enqueue_script(
                'wpbdp-admin-settings',
                WPBDP_ASSETS_URL . 'js/admin-settings.js',
                array(),
                WPBDP_VERSION,
				true
            );
        }
    }

    public function menu_item( $menu ) {
        $menu['wpbdp_settings'] = array(
            'title'    => _x( 'Settings', 'admin menu', 'business-directory-plugin' ),
            'callback' => array( $this, 'settings_page' ),
            'priority' => 0,
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
                    'tooltip'   => '',
                ),
                $setting
            );

            if ( 'silent' == $setting['type'] ) {
                continue;
            }

            if ( isset( $all_groups[ $args['group'] ] ) ) {
                switch ( $all_groups[ $args['group'] ]['type'] ) {
					case 'subtab':
						$subtab_group  = 'wpbdp_settings_subtab_' . $args['group'];
						$section_group = $subtab_group;
                        break;
					case 'section':
						$subtab_group  = 'wpbdp_settings_subtab_' . $all_groups[ $args['group'] ]['parent'];
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

	/**
	 * @since 5.9.1
	 */
	private function add_requirement( $setting ) {
		if ( ! empty( $setting['requirements'] ) ) {
			$reqs_info = array();

			foreach ( $setting['requirements'] as $r ) {
				$reqs_info[] = array( $r, (bool) wpbdp_get_option( str_replace( '!', '', $r ) ) );
			}

			echo ' data-requirements="' . esc_attr( wp_json_encode( $reqs_info ) ) . '"';
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

		echo '<div id="wpbdp-settings-' . esc_attr( $setting['id'] ) . '" class="wpbdp-settings-setting wpbdp-settings-type-' . esc_attr( $setting['type'] ) . '" ';
        if ( ! empty( $setting['attrs'] ) ) {
            wpbdp_html_attributes( $setting['attrs'], array( 'id', 'class' ), true );
        }

        echo ' data-setting-id="' . esc_attr( $setting['id'] ) . '" ';
		$this->add_requirement( $setting );
		echo '>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo apply_filters( 'wpbdp_admin_settings_render', $callback_html, $setting );
        echo '<span id="' . esc_attr( $setting['id'] ) . '"></span>';
        echo '</div>';
    }

    public function setting_tooltip( $tooltip = '' ) {
        if ( ! $tooltip ) {
            return;
        }

		return '<span class="wpbdp-setting-tooltip wpbdp-tooltip dashicons dashicons-editor-help" title="' . esc_attr( wp_strip_all_tags( $tooltip ) ) . '"></span>';
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
            echo '<span class="wpbdp-setting-description">' . wp_kses_post( $setting['desc'] ) . '</span>';
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

        if ( isset( $setting['max'] ) ) {
            echo 'max="' . $setting['max'] . '"';
        }
        echo '/>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . wp_kses_post( $setting['desc'] ) . '</span>';
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
        $save = $this->checkbox_saved_value( $setting );
		if ( 1 === $save ) {
			$value = (bool) $value;
		}

        echo '<input type="hidden" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . ']" value="0" />';
		echo '<label>';
        echo '<input type="checkbox" id="' . esc_attr( $setting['id'] ) . '" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . ']" value="' . esc_attr( $save ) . '" ' . checked( $value, $save, false ) . ' />';

        if ( ! empty( $setting['desc'] ) ) {
            echo wp_kses_post( $setting['desc'] );
        }
		echo '</label>';

		echo $this->setting_tooltip( $setting['tooltip'] );
    }

	/**
	 * Allow a check box to have a value other than 1.
	 */
	private function checkbox_saved_value( $setting ) {
		if ( empty( $setting['option'] ) ) {
			return 1;
		}

		return $setting['option'];
	}

    public function setting_radio_callback( $setting, $value ) {
        if ( empty( $setting['options'] ) ) {
            return;
        }

        echo '<div class="wpbdp-settings-radio-options">';
        foreach ( $setting['options'] as $option_value => $option_label ) {
            echo '<div class="wpbdp-settings-radio-option">';
            echo '<input type="radio" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $option_value ) . '" ' . checked( $option_value, $value, false ) . ' id="wpbdp-settings-' . $setting['id'] . '-radio-' . $option_value . '" />';
            echo '<label for="wpbdp-settings-' . $setting['id'] . '-radio-' . $option_value . '">';
            echo $option_label;
            echo '</label>';
            echo '</div>';
        }
        echo '</div>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }
    }

	/**
	 * Hide a field setting that is no longer used.
	 *
	 * @since 5.9.1
	 */
	public function setting_hidden_callback( $setting, $value ) {
		?>
		<input type="hidden" value="<?php echo esc_attr( $value ); ?>" name="wpbdp_settings[<?php echo esc_attr( $setting['id'] ); ?>]"/>
		<?php
	}

    public function setting_multicheck_callback( $setting, $value ) {
        if ( empty( $setting['options'] ) ) {
            return;
        }

        $value = (array) $value;

		echo '<input type="hidden" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . '][]" value="" />';

        echo '<div class="wpbdp-settings-multicheck-options wpbdp-grid">';
        $n = 0;
        foreach ( $setting['options'] as $option_value => $option_label ) {
			echo '<div class="wpbdp-settings-multicheck-option wpbdp-half wpbdp-settings-multicheck-option-no-' . esc_attr( $n ) . '">';
			echo '<input type="checkbox" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . '][]" id="wpbdp-' . esc_attr( $setting['id'] . '-checkbox-no-' . $n ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( in_array( $option_value, $value ), true, false ) . ' />';
			echo '<label for="wpbdp-' . esc_attr( $setting['id'] . '-checkbox-no-' . $n ) . '">';
			echo esc_html( $option_label );
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

    public function setting_file_callback( $setting, $value ) {
        $html  = '';
        $html .= sprintf(
            '<input id="%s" type="hidden" name="wpbdp_settings[%s]" value="%s" />',
            $setting['id'],
            $setting['id'],
            $value
        );

        $html .= '<div class="preview">';
        if ( $value ) {
            $html .= wp_get_attachment_image( $value, 'wpbdp-thumb', false );
        }

        $html .= sprintf(
            '<a href="http://google.com" class="delete" onclick="return WPBDP.fileUpload.deleteUpload(\'%s\', \'%s\');" style="%s">%s</a>',
            $setting['id'],
            'wpbdp_settings[' . $setting['id'] . ']',
            empty( $value ) ? 'display: none;' : '',
            _x( 'Remove', 'admin settings', 'business-directory-plugin' )
        );

        $html .= '</div>';

        $nonce    = wp_create_nonce( 'wpbdp-file-upload-' . $setting['id'] );
        $ajax_url = add_query_arg(
            array(
                'action'     => 'wpbdp-file-upload',
                'setting_id' => $setting['id'],
                'element'    => 'wpbdp_settings[' . $setting['id'] . ']',
                'nonce'      => $nonce,
            ),
            admin_url( 'admin-ajax.php' )
        );

        $html .= '<div class="wpbdp-upload-widget">';
        $html .= sprintf(
            '<iframe class="wpbdp-upload-iframe" name="upload-iframe-%s" id="wpbdp-upload-iframe-%s" src="%s" scrolling="no" seamless="seamless" border="0" frameborder="0"></iframe>',
            $setting['id'],
            $setting['id'],
            $ajax_url
        );
        $html .= '</div>';

        echo $html;
    }

    public function setting_url_callback( $setting, $value ) {
        echo '<input type="url" id="' . $setting['id'] . '" name="wpbdp_settings[' . $setting['id'] . ']" value="' . esc_attr( $value ) . '" placeholder="' . ( ! empty( $setting['placeholder'] ) ? esc_attr( $setting['placeholder'] ) : '' ) . '" />';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . wp_kses_post( $setting['desc'] ) . '</span>';
        }
    }

    public function setting_color_callback( $setting, $value ) {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

        echo '<input type="text" class="cpa-color-picker" id="' . esc_attr( $setting['id'] ) . '" name="wpbdp_settings[' . esc_attr( $setting['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . wp_kses_post( $setting['desc'] ) . '</span>';
        }
    }

    public function setting_text_template_callback( $setting, $value ) {
        $original_description = $setting['desc'];
        $placeholders         = isset( $setting['placeholders'] ) ? $setting['placeholders'] : array();

        if ( $placeholders ) {
            foreach ( $placeholders as $pholder => $desc ) {
                $placeholders[ $pholder ] = sprintf( '%s - %s', '[' . $pholder . ']', $desc );
            }

            $placeholders_text = implode( ', ', $placeholders ) . '.';
        } else {
            $placeholders_text = '';
        }

        if ( $setting['desc'] && $placeholders_text ) {
            $setting['desc'] = $setting['desc'] . '<br/><br/>' . sprintf( _x( 'Valid placeholders: %s', 'admin settings', 'business-directory-plugin' ), $placeholders_text );
        } elseif ( $placeholders_text ) {
            $settings['desc'] = $placeholders_text;
        }

        // TODO: this is a proxy for _setting_text (for now).
        ob_start();
        $this->setting_text_callback( $setting, $value );
        $html = ob_get_contents();
        ob_end_clean();

        $setting['desc'] = $original_description;

        echo $html;
    }

    public function setting_email_template_callback( $setting, $value ) {
        if ( ! is_array( $value ) ) {
            $value = array(
                'subject' => $setting['default']['subject'],
                'body'    => $value,
            );
        }

        $args = array(
            'setting_name'  => 'wpbdp_settings[' . $setting['id'] . ']',
            'email_subject' => $value['subject'],
            'email_body'    => $value['body'],
            'placeholders'  => ! empty( $setting['placeholders'] ) ? $setting['placeholders'] : array(),
        );

        if ( ! empty( $setting['desc'] ) ) {
            echo '<span class="wpbdp-setting-description">' . $setting['desc'] . '</span>';
        }

        echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-email.tpl.php', $args );
    }

	/**
	 * @since 5.9.1
	 */
	public function no_license( $setting, $value ) {
		$html = '<p class="howto">';
		$html .= esc_html__( 'Your license key provides access to new features and updates.', 'business-directory-plugin' );
		$html .= '</p>';
		$html .= '<p>' . esc_html__( 'You\'re using Business Directory Plugin Lite. Enjoy!', 'business-directory-plugin' );
		$html .= ' 🙂</p>';

		return $html;
	}

	/**
	 * Use for non-settings.
	 *
	 * @since 5.9
	 */
	public function setting_none_callback() {
		return;
	}

	/**
	 * @since 5.9.1
	 */
	private function setting_education_callback( $setting ) {
		WPBDP_Admin_Education::show_tip_message( $setting['desc'] ); // already escaped
	}

    public function setting_expiration_notices_callback( $setting, $value ) {
?>
<div class="wpbdp-settings-expiration-notices">
    <button id="wpbdp-settings-expiration-notices-add-btn" class="button"><?php _ex( 'Add notice', 'expiration notices', 'business-directory-plugin' ); ?></button>

    <div id="wpbdp-settings-expiration-notices-add">
    <?php
    $n = ! empty( $value ) ? max( array_keys( $value ) ) + 1 : 0;
    echo wpbdp_render_page(
        WPBDP_PATH . 'templates/admin/settings-email.tpl.php',
        array(
            'setting_name'    => 'new_notice[' . $n . ']',
            'uid'             => '',
            'container_class' => 'wpbdp-expiration-notice-email',
            'extra_fields'    => $this->setting_expiration_notices_email_extra_fields( 'new_notice[' . $n . ']', '', null ),
            'editor_only'     => true,
        )
    );
    ?>
    </div>

    <?php if ( ! $value ) : ?>
    <p class="wpbdp-no-items"><?php _ex( 'No notices configured.', 'expiration notices', 'business-directory-plugin' ); ?></p>
    <?php endif; ?>

<?php
foreach ( $value as $i => $notice ) {
	$uid  = uniqid( 'wpbdp-settings-email-' );
	$vars = array(
		'setting_name'    => 'wpbdp_settings[' . $setting['id'] . '][' . $i . ']',
		'uid'             => $uid,
		'container_class' => 'wpbdp-expiration-notice-email',
		'email_subject'   => $notice['subject'],
		'email_body'      => $notice['body'],
		'extra_fields'    => $this->setting_expiration_notices_email_extra_fields( 'wpbdp_settings[' . $setting['id'] . '][' . $i . ']', $uid, $notice ),
		'after_container' => $this->setting_expiration_notices_email_summary( $notice ),
		'before_buttons'  => '<a href="#" class="delete">' . esc_html__( 'Delete', 'business-directory-plugin' ) . '</a>',
		'placeholders'    =>
			array(
				'site'         => _x( 'Site title (with link)', 'settings', 'business-directory-plugin' ),
				'author'       => _x( 'Author\'s name', 'settings', 'business-directory-plugin' ),
				'listing'      => _x( 'Listing\'s name (with link)', 'settings', 'business-directory-plugin' ),
				'expiration'   => _x( 'Listing\'s expiration date', 'settings', 'business-directory-plugin' ),
                'link'         => _x( 'Listing\'s renewal link, formatted with an anchor tag', 'settings', 'business-directory-plugin' ),
                'link-raw'     => _x( 'Listing\'s renewal URL, unformatted by any tags', 'settings', 'business-directory-plugin' ),
				'category'     => _x( 'Listing\'s categories', 'settings', 'business-directory-plugin' ),
				'payment_date' => _x( 'Listing\'s last payment date', 'settings', 'business-directory-plugin' ),
				'access_key'   => _x( 'Listing\'s access key', 'settings', 'business-directory-plugin' ),
			),
	);

	echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-email.tpl.php', $vars );
}
?>
</div>
<?php
    }

    private function setting_expiration_notices_email_summary( $notice ) {
        $event         = $notice['event'];
        $listings      = $notice['listings'];
        $relative_time = ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : '';

        if ( 'both' == $listings ) {
            $recurring_modifier = _x( 'recurring and non-recurring', 'expiration notices', 'business-directory-plugin' );
        } elseif ( 'recurring' == $listings ) {
            $recurring_modifier = _x( 'recurring only', 'expiration notices', 'business-directory-plugin' );
        } else {
            $recurring_modifier = _x( 'non-recurring only', 'expiration notices', 'business-directory-plugin' );
        }

        if ( 'renewal' == $event ) {
            $summary = sprintf( _x( 'Sent when a listing (%s) is renewed.', 'expiration notices', 'business-directory-plugin' ), $recurring_modifier );
        }

        if ( 'expiration' == $event ) {
            if ( '0 days' == $relative_time ) {
                $summary = sprintf( _x( 'Sent when a listing (%s) expires.', 'expiration notices', 'business-directory-plugin' ), $recurring_modifier );
            } else {
                $relative_time_parts  = explode( ' ', $relative_time );
                $relative_time_number = trim( str_replace( array( '+', '-' ), '', $relative_time_parts[0] ) );
                $relative_time_units  = $relative_time_parts[1];

                switch ( $relative_time_units ) {
					case 'days':
						$relative_time_h = sprintf( _nx( '%d day', '%d days', $relative_time_number, 'expiration notices', 'business-directory-plugin' ), $relative_time_number );
                        break;
					case 'weeks':
						$relative_time_h = sprintf( _nx( '%d week', '%d weeks', $relative_time_number, 'expiration notices', 'business-directory-plugin' ), $relative_time_number );
                        break;
					case 'months':
						$relative_time_h = sprintf( _nx( '%d month', '%d months', $relative_time_number, 'expiration notices', 'business-directory-plugin' ), $relative_time_number );
                        break;
                }

                if ( $relative_time[0] == '+' ) {
                    /* translators: 1: relative time (e.g. 3 days), 2: recurring modifier (e.g. non-recuring only) */
                    $summary = sprintf( _x( 'Sent %1$s before a listing (%2$s) expires.', 'expiration notices', 'business-directory-plugin' ), $relative_time_h, $recurring_modifier );
                } else {
                    /* translators: 1: relative time (e.g. 3 days), 2: recurring modifier (e.g. non-recuring only) */
                    $summary = sprintf( _x( 'Sent %1$s after a listing (%2$s) expires.', 'expiration notices', 'business-directory-plugin' ), $relative_time_h, $recurring_modifier );
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
            array( 'expiration', '0 days', _x( 'At the time of expiration', 'expiration notices', 'business-directory-plugin' ) ),
            array( 'renewal', '0 days', _x( 'Right after a successful renewal', 'expiration notices', 'business-directory-plugin' ) ),
        );
        foreach ( array(
			'days'   => array( 1, 2, 3, 4, 5 ),
			'weeks'  => array( 1, 2 ),
			'months' => array( 1, 2 ),
		) as $unit => $periods ) {
            foreach ( $periods as $i ) {
                foreach ( array( '+', '-' ) as $sign ) {
                    switch ( $unit ) {
						case 'days':
							$label = sprintf( '+' == $sign ? _nx( '%d day before expiration', '%d days before expiration', $i, 'expiration notices', 'business-directory-plugin' ) : _nx( '%d day after expiration', '%d days after expiration', $i, 'expiration notices', 'business-directory-plugin' ), $i );
                            break;
						case 'weeks':
							$label = sprintf( '+' == $sign ? _nx( '%d week before expiration', '%d weeks before expiration', $i, 'expiration notices', 'business-directory-plugin' ) : _nx( '%d week after expiration', '%d weeks after expiration', $i, 'expiration notices', 'business-directory-plugin' ), $i );
                            break;
						case 'months':
							$label = sprintf( '+' == $sign ? _nx( '%d month before expiration', '%d months before expiration', $i, 'expiration notices', 'business-directory-plugin' ) : _nx( '%d month after expiration', '%d months after expiration', $i, 'expiration notices', 'business-directory-plugin' ), $i );
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
            $notice = array(
				'event'         => 'expiration',
				'listings'      => 'both',
				'relative_time' => '0 days',
				'subject'       => '',
				'body'          => '',
				'placeholders'  => array(),
			);
        }

        ob_start();
?>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-listings"><?php _ex( 'Applies to', 'expiration notices', 'business-directory-plugin' ); ?></label></th>
        <td>
            <select id="<?php echo $uid; ?>-listings" name="<?php echo $name; ?>[listings]">
                <option value="non-recurring" <?php selected( 'non-recurring', $notice['listings'] ); ?>><?php _ex( 'Non-recurring listings', 'expiration notices', 'business-directory-plugin' ); ?></option>
                <option value="recurring" <?php selected( 'recurring', $notice['listings'] ); ?>><?php _ex( 'Recurring listings', 'expiration notices', 'business-directory-plugin' ); ?></option>
                <option value="both" <?php selected( 'both', $notice['listings'] ); ?>><?php _ex( 'Recurring and non-recurring listings', 'expiration notices', 'business-directory-plugin' ); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-relative-time-and-event"><?php _ex( 'When to send?', 'expiration notices', 'business-directory-plugin' ); ?></label></th>
        <td>
            <input type="hidden" value="<?php echo $notice['event']; ?>" class="stored-notice-event" />
            <input type="hidden" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="stored-notice-relative-time" />

            <input type="hidden" name="<?php echo $name; ?>[event]" value="<?php echo $notice['event']; ?>" class="notice-event" />
            <input type="hidden" name="<?php echo $name; ?>[relative_time]" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="notice-relative-time" />

            <select id="<?php echo $uid; ?>-relative-time-and-event" class="relative-time-and-event">
                <?php foreach ( $this->setting_expiration_notices_schedule() as $item ) : ?>
                    <?php if ( 'renewal' == $item[0] ) : ?>
                    <option value="<?php echo $item[0]; ?>,<?php echo $item[1]; ?>" <?php selected( $item[0], $notice['event'] ); ?>><?php echo $item[2]; ?></option>
                    <?php else : ?>
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
        if ( isset( $_REQUEST['reset_defaults'] ) && $_REQUEST['reset_defaults'] == 1 ) {
            echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-reset.tpl.php' );
            return;
        }

        if ( isset( $_REQUEST['message'] ) && $_REQUEST['message'] == 'reset' ) {
            $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message', 'settings-updated' ) );
            wpbdp_admin_message( _x( 'Settings reset to default.', 'settings', 'business-directory-plugin' ) );
            wpbdp()->admin->admin_notices();
        }

        $all_groups = wpbdp()->settings->get_registered_groups();

        // Filter out empty groups.
        $all_groups = wp_list_filter( $all_groups, array( 'count' => 0 ), 'NOT' );

        $tabs = wp_list_filter( $all_groups, array( 'type' => 'tab' ) );
		$active_tab = wpbdp_get_var( array( 'param' => 'tab' ) );
		if ( ! isset( $tabs[ $active_tab ] ) ) {
            $active_tab = 'general';
        }

        $subtabs = wp_list_filter( $all_groups, array( 'parent' => $active_tab ) );
		$active_subtab = wpbdp_get_var( array( 'param' => 'subtab' ) );
		if ( ! isset( $subtabs[ $active_subtab ] ) ) {
            $subtabs_ids   = array_keys( $subtabs );
            $active_subtab = reset( $subtabs_ids );
        }

        $active_subtab_description = ! empty( $all_groups[ $active_subtab ]['desc'] ) ? $all_groups[ $active_subtab ]['desc'] : '';
        $custom_form               = ( ! empty( $all_groups[ $active_subtab ]['custom_form'] ) ) && $all_groups[ $active_subtab ]['custom_form'];

        echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-page.tpl.php', compact( 'tabs', 'subtabs', 'active_tab', 'active_subtab', 'active_subtab_description', 'custom_form' ) );
    }


    public function settings_reset_defaults() {
		if ( wp_verify_nonce( wpbdp_get_var( array( 'param' => '_wpnonce' ), 'post' ), 'reset defaults' ) ) {
            global $wpbdp;
            $wpbdp->settings->reset_defaults();

            $url = remove_query_arg( 'reset_defaults' );
            $url = add_query_arg(
                array(
					'settings-updated' => 1,
					'message'          => 'reset',
                ), $url
            );
            wp_redirect( $url );
            exit();
        }
    }

    public function _ajax_file_upload() {
		$setting_id = wpbdp_get_var( array( 'param' => 'setting_id' ), 'request' );
		$nonce      = wpbdp_get_var( array( 'param' => 'nonce' ), 'request' );

        if ( ! $setting_id || ! $nonce ) {
            die;
        }

        if ( ! wp_verify_nonce( $nonce, 'wpbdp-file-upload-' . $setting_id ) ) {
            die;
        }

		$element = wpbdp_get_var( array( 'param' => 'element', 'default' => 'wpbdp_settings[' . $setting_id . ']' ), 'request' );

        echo '<form action="" method="POST" enctype="multipart/form-data">';
        echo '<input type="file" name="file" class="file-upload" onchange="return window.parent.WPBDP.fileUpload.handleUpload(this);"/>';
        echo '</form>';

        if ( isset( $_FILES['file']['error'] ) && $_FILES['file']['error'] == 0 ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$file = wp_unslash( $_FILES['file'] );
            // TODO: we support only images for now but we could use this for anything later
			$media_id = wpbdp_media_upload(
                $file,
                true,
                true,
                array(
                    'image'      => true,
                    'min-size'   => intval( wpbdp_get_option( 'image-min-filesize' ) ) * 1024,
                    'max-size'   => intval( wpbdp_get_option( 'image-max-filesize' ) ) * 1024,
                    'min-width'  => wpbdp_get_option( 'image-min-width' ),
                    'min-height' => wpbdp_get_option( 'image-min-height' ),
                ),
                $errors
            );
			if ( $media_id ) {
                echo '<div class="preview" style="display: none;">';
                echo wp_get_attachment_image( $media_id, 'thumb', false );
                echo '</div>';

                echo '<script>';
                echo sprintf( 'window.parent.WPBDP.fileUpload.finishUpload("%s", %d, "%s");', $setting_id, $media_id, $element );
                echo '</script>';
            } else {
                print $errors;
            }
        }

        echo sprintf( '<script>window.parent.WPBDP.fileUpload.resizeIFrame("%s", %d);</script>', $setting_id, 30 );

        exit;
    }
}
