<?php

/**
 * Unit tests for Ajax functions in WPBDP_Admin class.
 */
class AdminAjaxTest extends AjaxTestCase {


	public function test_close_subscribe() {
		require_once WPBDP_INC . 'admin/class-admin.php';
		new WPBDP_Admin();

		$_POST = array(
			'action'    => 'wpbdp-drip_subscribe',
			'nonce'     => wp_create_nonce( 'drip pointer subscribe' ),
			'subscribe' => '0',
			'email'     => 'bad',
		);

		// Check if it will come back when dismissed.
		update_option( 'wpbdp-show-drip-pointer', 1 ); // Trigger it to show
		$this->trigger_action( 'wpbdp-drip_subscribe' );
		$after = get_option( 'wpbdp-show-drip-pointer' );
		$this->assertEquals( false, $after, 'Pointer was not disabled' );

		// Check if it will come back when a bad email is used.
		$_POST['subscribe'] = '1';
		update_option( 'wpbdp-show-drip-pointer', 1 ); // Trigger it to show
		$this->trigger_action( 'wpbdp-drip_subscribe' );
		$after = get_option( 'wpbdp-show-drip-pointer' );
		$this->assertEquals( '1', $after, 'Bad email dismissed the request' );
	}
}
