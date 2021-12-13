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
	 * @param int $post_id The post id. If not passed, defaults to the current post id.
	 *
	 * @since x.x
	 *
	 * @return bool
	 */
	public static function divi_builder_is_active( $post_id = false ) {
		if ( ! class_exists( 'ET_Builder_Plugin' ) ) {
			return false;
		}
		if ( isset( $_GET['et_fb'] ) ) {
			return true;
		}
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		return ( 'on' === get_post_meta( $post_id, '_et_pb_use_builder', true ) );
	}
}
