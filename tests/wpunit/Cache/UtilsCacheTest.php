<?php

namespace Cache;

use WPBDP_Utils;

class UtilsCacheTest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testCacheCrude() {
		$this->tester->wantToTest( 'Test Utils Cache' );
		$data = array(
			'id' => 1,
			'name' => 'Test Data'
		);
		WPBDP_Utils::set_cache( 'test_data_1', $data, 'wpbdp_test_data' );

		$cached_data = wp_cache_get( 'test_data_1', 'wpbdp_test_data' );

		$this->assertEquals( $cached_data['id'], $data['id'], 'Cached data and original data match' );

		// Update Cache
		$new_data = array(
			'id' => 2,
			'name' => 'Test Data'
		);
		WPBDP_Utils::set_cache( 'test_data_1', $new_data, 'wpbdp_test_data' );

		$cached_data = wp_cache_get( 'test_data_1', 'wpbdp_test_data' );

		$this->assertNotEquals( $cached_data['id'], $data['id'], 'Cached data and original do not match match' );
		$this->assertEquals( $cached_data['id'], $new_data['id'], 'New cached data and original data match' );

		// Data cleared
		WPBDP_Utils::cache_delete_group( 'wpbdp_test_data' );

		$cached_data = wp_cache_get( 'test_data_1', 'wpbdp_test_data' );
		$this->assertIsEmpty( $cached_data, 'Cache Data is empty' );
	}
}
