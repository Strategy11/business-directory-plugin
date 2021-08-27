<?php
/**
 * Includes tests for the Listings API class.
 */

namespace WPBDP\Tests\Plugin\Listings;

use Brain\Monkey\Functions;
use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP_Listings_API;

/**
 * Tests for the Listings API class.
 */
class APITest extends TestCase {


	public function setup() {
		parent::setup();
		global $wpdb;

		// Remove any existing payment in database
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE 1;" );
	}

	public function test_listing_published_status_after_payment() {
		// Set option for testing.
		wpbdp_set_option( 'new-post-status', 'publish' );

		$listing_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'pending_payment',
				'post_title'  => '(no title)',
			)
		);

		$listing = wpbdp_get_listing( $listing_id );

		$listing->set_fee_plan( 1 );

		$payment = $listing->generate_or_retrieve_payment();

		// Execute
		$payment->status = 'completed';
		$payment->save();

		// // Verification.
		$this->assertEquals( 'publish', get_post_status( $listing_id ) );

		// Restore option to default value
		wpbdp_set_option( 'new-post-status', 'pending' );

	}
}
