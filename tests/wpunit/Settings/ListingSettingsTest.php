<?php
/**
 * Includes tests for the Settings
 */

namespace Settings;

use WPBDP\Tests\WPUnitTestCase;
use WP_Query;

/**
 * Tests for the Listings settings
 */
class ListingSettingsTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testListingSettings() {
		$this->tester->wantToTest( 'Listing Settings' );
		$per_page = wpbdp_get_option( 'listings-per-page' );
		$this->assertEquals( $per_page, 10 );
		wpbdp_set_option( 'listings-per-page', 5 );
		$per_page = wpbdp_get_option( 'listings-per-page' );
		$this->assertEquals( $per_page, 5 );
		$this->count_listings();
	}

	/**
	 * Count listings
	 */
	private function count_listings() {
		$per_page = wpbdp_get_option( 'listings-per-page' );
		$args     = array(
			'post_type'       => WPBDP_POST_TYPE,
			'posts_per_page'  => $per_page,
			'post_status'     => 'publish',
			'paged'           => 1,
			'orderby'         => wpbdp_get_option( 'listings-order-by', 'title' ),
			'order'           => wpbdp_get_option( 'listings-sort', 'ASC' ),
		);

		$q     = new WP_Query( $args );
		$count = count( $q->posts );
		$this->assertEquals( $count, $per_page );
	}
}