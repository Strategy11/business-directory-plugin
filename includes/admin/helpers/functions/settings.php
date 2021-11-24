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
			echo "<h2>{$section['title']}</h2>\n";
		}

		if ( $section['callback'] ) {
			call_user_func( $section['callback'], $section );
		}

		if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
			continue;
		}
		echo '<div class="form-table" role="presentation">';
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
		$class = '';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}

		echo "<div{$class}>";

		if ( ! empty( $field['title'] ) ) {
			if ( ! empty( $field['args']['label_for'] ) ) {
				echo '<div scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></div>';
			} else {
				echo '<div scope="row">' . $field['title'] . '</div>';
			}
		}

		echo '<div>';
		call_user_func( $field['callback'], $field['args'] );
		echo '</div>';
		echo '</div>';
	}
}
