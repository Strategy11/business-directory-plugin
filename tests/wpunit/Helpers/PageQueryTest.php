<?php
/**
 * Includes tests for the plugin helpers
 */

namespace Helpers;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Utils;

/**
 * Test the page queries.
 */
class PageQueryTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testPageLookUp() {
		$this->tester->wantToTest( 'Page lookup' );

		$main_page = wpbdp_get_page_ids( 'main' );
		$cache     = wp_cache_get( 'wpbdp_page_idsmain', 'wpbdp_pages' );
		$this->assertEquals( $main_page, $cache );
	}
}