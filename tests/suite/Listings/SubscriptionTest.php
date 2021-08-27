<?php
/**
 * @package WPBDP\Tests\Plugin\Listings
 */

namespace WPBDP\Tests\Plugin\Listings;

use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP__Listing_Subscription;

/**
 * Unit tests for Subscription class.
 */
class SubscriptionTest extends TestCase {

	public function test_fill_data_from_database_using_subscription_id() {
		$subscription_data = (object) array(
			'listing_id'        => rand() + 1,
			'subscription_id'   => 'sub_CFJwoIZWQrYz1s',
			'subscription_data' => '',
		);

		$wpdb = $GLOBALS['wpdb'] = Mockery::mock( 'wpdb' );

		$wpdb->prefix = 'wp_';

		$wpdb->shouldReceive( 'get_row' )->andReturn( $subscription_data );
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'a prepared SQL query' );

		// Execution.
		$subscription = new WPBDP__Listing_Subscription( 0, $subscription_data->subscription_id );

		// Verification.
		$this->assertNotNull( $subscription );
	}
}
