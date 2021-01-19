<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since x.x
 */
class WPBDP_App_Helper {

	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	public static function plugin_path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Prevously WPBDP_PLUGIN_FILE constant.
	 */
	public static function plugin_file() {
		return self::plugin_path() . '/business-directory-plugin.php';
	}

	/**
	 * Prevously WPBDP_URL constant.
	 */
	public static function plugin_url() {
		return trailingslashit( plugins_url( '', self::plugin_file() ) );
	}

	public static function relative_plugin_url() {
		return str_replace( array( 'https:', 'http:' ), '', self::plugin_url() );
	}

	/**
	 * @return string Site URL
	 */
	public static function site_url() {
		return site_url();
	}

	/**
	 * Check for certain page in settings
	 *
	 * @since x.x
	 *
	 * @param string $page The name of the page to check
	 *
	 * @return boolean
	 */
	public static function is_admin_page( $page = 'wpbdp_settings' ) {
		global $pagenow;
		$get_page = wpbdp_get_var( array( 'param' => 'page' ) );
		if ( $pagenow ) {
			// allow this to be true during ajax load
			$is_page = ( $pagenow == 'admin.php' || $pagenow == 'admin-ajax.php' ) && $get_page == $page;
			if ( $is_page ) {
				return true;
			}
		}

		return is_admin() && $get_page == $page;
	}

	/**
	 * Try to show the SVG if possible. Otherwise, use the font icon.
	 *
	 * @since x.x
	 *
	 * @param string $class
	 * @param array  $atts
	 */
	public static function icon_by_class( $class, $atts = array() ) {
		$echo = ! isset( $atts['echo'] ) || $atts['echo'];
		if ( isset( $atts['echo'] ) ) {
			unset( $atts['echo'] );
		}

		$html_atts = self::array_to_html_params( $atts );

		$icon = trim( str_replace( array( 'wpbdpfont ' ), '', $class ) );
		if ( $icon === $class ) {
			$icon = '<i class="' . esc_attr( $class ) . '"' . $html_atts . '></i>';
		} else {
			$class = strpos( $icon, ' ' ) === false ? '' : ' ' . $icon;
			if ( strpos( $icon, ' ' ) ) {
				$icon = explode( ' ', $icon );
				$icon = reset( $icon );
			}
			$icon  = '<svg class="wpbdpsvg' . esc_attr( $class ) . '"' . $html_atts . '>
				<use xlink:href="#' . esc_attr( $icon ) . '" />
			</svg>';
		}

		if ( $echo ) {
			echo $icon; // WPCS: XSS ok.
		} else {
			return $icon;
		}
	}

	/**
	 * Include svg images.
	 *
	 * @since x.x
	 */
	public static function include_svg() {
		include_once self::plugin_path() . '/assets/images/icons.svg';
	}

	/**
	 * Convert an associative array to HTML values.
	 *
	 * @since x.x
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function array_to_html_params( $atts ) {
		$html = '';
		if ( ! empty( $atts ) ) {
			foreach ( $atts as $key => $value ) {
				$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
		return $html;
	}

	/**
	 * @since x.x
	 */
	public static function show_logo( $size ) {
		echo self::kses( self::svg_logo( $size ), 'all' ); // WPCS: XSS ok.
	}

	/**
	 * @since x.x
	 */
	public static function svg_logo( $size = 18 ) {
		$atts = array(
			'height' => $size,
			'width'  => $size,
		);

		return '<img src="' . esc_url( self::plugin_url() . '/assets/images/percie.svg' ). '" width="' . esc_attr( $atts['width'] ) . '" height="' . esc_attr( $atts['height'] ) . '" />';
	}

	/**
	 * Sanitize the value, and allow some HTML
	 *
	 * @since x.x
	 *
	 * @param string $value
	 * @param array|string $allowed 'all' for everything included as defaults
	 *
	 * @return string
	 */
	public static function kses( $value, $allowed = array() ) {
		$allowed_html = self::allowed_html( $allowed );

		return wp_kses( $value, $allowed_html );
	}

	/**
	 * @since x.x
	 */
	private static function allowed_html( $allowed ) {
		$html         = self::safe_html();
		$allowed_html = array();
		if ( $allowed == 'all' ) {
			$allowed_html = $html;
		} elseif ( ! empty( $allowed ) ) {
			foreach ( (array) $allowed as $a ) {
				$allowed_html[ $a ] = isset( $html[ $a ] ) ? $html[ $a ] : array();
			}
		}

		return apply_filters( 'wpbdp_striphtml_allowed_tags', $allowed_html );
	}

	/**
	 * @since x.x
	 */
	private static function safe_html() {
		$allow_class = array(
			'class' => true,
			'id'    => true,
		);

		return array(
			'a'          => array(
				'class'  => true,
				'href'   => true,
				'id'     => true,
				'rel'    => true,
				'target' => true,
				'title'  => true,
			),
			'abbr'       => array(
				'title' => true,
			),
			'aside'      => $allow_class,
			'b'          => array(),
			'blockquote' => array(
				'cite' => true,
			),
			'br'         => array(),
			'cite'       => array(
				'title' => true,
			),
			'code'       => array(),
			'defs'       => array(),
			'del'        => array(
				'datetime' => true,
				'title'    => true,
			),
			'dd'         => array(),
			'div'        => array(
				'class' => true,
				'id'    => true,
				'title' => true,
				'style' => true,
			),
			'dl'         => array(),
			'dt'         => array(),
			'em'         => array(),
			'h1'         => $allow_class,
			'h2'         => $allow_class,
			'h3'         => $allow_class,
			'h4'         => $allow_class,
			'h5'         => $allow_class,
			'h6'         => $allow_class,
			'i'          => array(
				'class' => true,
				'id'    => true,
				'icon'  => true,
				'style' => true,
			),
			'img'        => array(
				'alt'    => true,
				'class'  => true,
				'height' => true,
				'id'     => true,
				'src'    => true,
				'width'  => true,
			),
			'li'         => $allow_class,
			'ol'         => $allow_class,
			'p'          => $allow_class,
			'path'       => array(
				'd'    => true,
				'fill' => true,
			),
			'pre'        => array(),
			'q'          => array(
				'cite'  => true,
				'title' => true,
			),
			'rect'       => array(
				'class'  => true,
				'fill'   => true,
				'height' => true,
				'width'  => true,
				'x'      => true,
				'y'      => true,
				'rx'     => true,
				'stroke' => true,
				'stroke-opacity' => true,
				'stroke-width'   => true,
			),
			'section'    => $allow_class,
			'span'       => array(
				'class' => true,
				'id'    => true,
				'title' => true,
				'style' => true,
			),
			'strike'     => array(),
			'strong'     => array(),
			'symbol'     => array(
				'class'   => true,
				'id'      => true,
				'viewbox' => true,
			),
			'svg'        => array(
				'class'   => true,
				'id'      => true,
				'xmlns'   => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
				'style'   => true,
				'fill'    => true,
			),
			'use'        => array(
				'href'   => true,
				'xlink:href' => true,
			),
			'ul'         => $allow_class,
		);
	}
}
