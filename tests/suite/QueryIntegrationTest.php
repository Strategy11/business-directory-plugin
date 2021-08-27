<?php
/**
 * Tests for Query Integration class.
 *
 * @package WPBDP\Tests\Plugin
 */

namespace WPBDP\Tests\Plugin;

use Brain\Monkey\Functions;

use WPBDP\Tests\TestCase;

use WPBDP__Query_Integration;

/**
 * TestCase for Query Integration class.
 */
class QueryIntegrationTest extends TestCase {

	public function test_sortbar_sort_options_handles_non_array_setting_value() {
		Functions\when( 'wpbdp_sortbar_get_field_options' )
			->justReturn( array() );

		Functions\expect( 'wpbdp_get_option' )
			->once()
			->with( 'listings-sortbar-fields' )
			->andReturn( false );

		$query_integration = new WPBDP__Query_Integration();

		// Execution
		$query_integration->sortbar_sort_options( array() );
	}
}

