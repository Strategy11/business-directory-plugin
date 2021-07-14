<?php

use \WPBDP\Tests\TestCase;

/**
 * Unit tests for WPBDP_Admin class.
 */
class BDAdminTest extends TestCase {

	public function test_pointer_callback() {
		$class = new WPBDP_Admin();

		$cases = array(
			array(
				'wpbdp-show-drip-pointer'     => false,
				'wpbdp-show-tracking-pointer' => false,
				'expected' => false,
			),
			array(
				'wpbdp-show-drip-pointer'     => 1,
				'wpbdp-show-tracking-pointer' => 1,
				'expected' => array( $class, 'drip_pointer' ),
			),
			array(
				'wpbdp-show-drip-pointer'     => 4,
				'wpbdp-show-tracking-pointer' => 1,
				'expected' => 'WPBDP_SiteTracking::request_js',
			),
			array(
				'wpbdp-show-drip-pointer'     => 2,
				'wpbdp-show-tracking-pointer' => false,
				'expected' => array( $class, 'drip_pointer' ),
			),
			array(
				'wpbdp-show-drip-pointer'     => 4,
				'wpbdp-show-tracking-pointer' => 4,
				'expected' => false,
			),
		);

		foreach ( $cases as $case ) {
			update_option( 'wpbdp-show-drip-pointer', $case['wpbdp-show-drip-pointer'] );
			update_option( 'wpbdp-show-tracking-pointer', $case['wpbdp-show-tracking-pointer'] );
			$callback = $this->run_private_method( array( $class, 'pointer_callback' ) );
			$this->assertEquals( $case['expected'], $callback);
		}
	}
}
