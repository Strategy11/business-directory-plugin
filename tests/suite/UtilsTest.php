<?php
/**
 * @package WPBDP\Tests\Plugin
 */

namespace WPBPD\Tests\Plugin;

use WPBDP\Tests\TestCase;

use WPBDP_Utils;

/**
 * Unit tests for Utils class.
 */
class UtilsTest extends TestCase {

	/**
	 * @since 5.2.1
	 */
	public function test_sort_by_property() {
		$data = array(
			'a' => array(
				'order' => 5,
			),
			'b' => array(
				'order' => 2,
			),
			'c' => array(
				'order' => 10,
			),
		);

		WPBDP_Utils::sort_by_property( $data, 'order' );

		$this->assertEquals( array( 'b', 'a', 'c' ), array_keys( $data ) );
	}
}
