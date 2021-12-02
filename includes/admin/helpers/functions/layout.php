<?php
/**
 * Admin settings helper functions
 *
 * @since x.x
 */

/**
 * Prints out all settings sections added to a particular settings page.
 *
 * @link https://developer.wordpress.org/reference/functions/do_settings_sections/
 *
 * @param string $page
 *
 * @since x.x
 */
function wpbdp_admin_do_settings_sections( $page ) {
	global $wp_settings_sections, $wp_settings_fields;

	if ( ! isset( $wp_settings_sections[ $page ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
		if ( $section['title'] ) {
			echo '<div class="wpbdp-settings-form-title">';
			echo "<h2>{$section['title']}</h2>\n";
			echo '</div>';
		}

		if ( $section['callback'] ) {
			call_user_func( $section['callback'], $section );
		}

		if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
			continue;
		}
		echo '<div class="form-table wpbdp-settings-form wpbdp-grid">';
		wpbdp_admin_do_settings_fields( $page, $section['id'] );
		echo '</div>';
	}
}

/**
 * Print out the settings fields for a particular settings section.
 *
 * @link https://developer.wordpress.org/reference/functions/do_settings_fields/
 *
 * @param string $page
 * @param string $section
 *
 * @since x.x
 */
function wpbdp_admin_do_settings_fields( $page, $section ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
		$class = ' class="wpbdp-setting-row"';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="wpbdp-setting-row ' . esc_attr( $field['args']['class'] ) . '"';
		}

		echo "<div{$class}>";

		if ( 'checkbox' !== $field['args']['type'] && ! empty( $field['title'] ) ) {
			if ( ! empty( $field['args']['label_for'] ) ) {
				echo '<div class="wpbdp-setting-label"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></div>';
			} else {
				echo '<div class="wpbdp-setting-label">' . $field['title'] . '</div>';
			}
		}

		echo '<div class="wpbdp-setting-content">';
		call_user_func( $field['callback'], $field['args'] );
		echo '</div>';
		echo '</div>';
	}
}
