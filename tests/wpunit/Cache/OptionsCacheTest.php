<?php

namespace Cache;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Utils;

class OptionsCacheTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testOptionsCache() {
		$this->tester->wantToTest( 'Test Utils Options Cache' );
		$data = array(
			'id'   => 1,
			'name' => 'Test Data',
		);
		WPBDP_Utils::set_transient_cache(
			array(
				'cache_key' => 'wpbdp_test_cache',
				'group'     => 'wpbdp_test_cache',
				'results'   => $data,
			)
		);
		$cached_data = WPBDP_Utils::check_transient_cache(
			array(
				'cache_key' => 'wpbdp_test_cache',
				'group'     => 'wpbdp_test_cache',
			)
		);

		$this->assertEquals( $cached_data['id'], $data['id'], 'Cached data and original data match' );

		// Update Cache
		$new_data = array(
			'id'   => 2,
			'name' => 'Test Data',
		);
		WPBDP_Utils::set_transient_cache(
			array(
				'cache_key' => 'wpbdp_test_cache_two',
				'group'     => 'wpbdp_test_cache',
				'results'   => $new_data,
			)
		);

		$new_cached_data = WPBDP_Utils::check_transient_cache(
			array(
				'cache_key' => 'wpbdp_test_cache_two',
				'group'     => 'wpbdp_test_cache',
			)
		);

		$this->assertNotEquals( $new_cached_data['id'], $data['id'], 'Cached data and original do not match match' );
		$this->assertEquals( $new_cached_data['id'], $new_data['id'], 'New cached data and original data match' );

		// Data cleared
		delete_option( 'wpbdp_test_cache' );

		$cached_data = WPBDP_Utils::check_transient_cache(
			array(
				'cache_key' => 'wpbdp_test_cache',
				'group'     => 'wpbdp_test_cache',
			)
		);
		$this->assertTrue( empty( $cached_data ), 'Cache Data is empty' );
	}
}
