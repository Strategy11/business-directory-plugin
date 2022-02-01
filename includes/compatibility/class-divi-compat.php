<?php
/**
 * Divi compatibility
 *
 * @package WPBDP/Includes/Compatibility/Divi
 */

/**
 * Class WPBDP_Divi_Compat
 */
class WPBDP_Divi_Compat {

	/**
	 * Check if Divi builder is being used.
	 *
	 * @since 5.16.1
	 *
	 * @return bool
	 */
	public static function divi_builder_is_active() {
		if ( ! class_exists( 'ET_Builder_Plugin' ) ) {
			return false;
		}
		return isset( $_GET['et_fb'] );
	}
}
