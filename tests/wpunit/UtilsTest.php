<?php
/**
 * @package WPBDP\Tests\Plugin
 */

/**
 * Unit tests for Utils class.
 */
class UtilsTest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;


	public function testSortByProperty() {
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
		$this->tester->wantToTest( 'Utils Sort Order' );
		$this->assertEquals( array( 'b', 'a', 'c' ), array_keys( $data ) );
	}
}
