<?php
/**
 * Includes tests for the Listings API class.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Listings_API;

/**
 * Tests for the Listings API class.
 */
class APITest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;


	protected static function before_tear_down() {
		global $wpdb;

		// Remove any existing payment in database
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE 1;" );
	}

	public function testListingPublishedStatusAfterPayment() {
		$this->tester->wantToTest( 'Payment Listing publish status' );
		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'pending_payment',
				'post_title'  => '(no title)',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
			$listing->set_fee_plan( 1 );

			$payment = $listing->generate_or_retrieve_payment();

			// Execute
			$payment->status = 'completed';
			$payment->save();

			// // Verification.
			$this->assertEquals( 'publish', get_post_status( $listing_id ) );
		} else {
			$this->assertTrue( is_wp_error( $listing ), $listing->get_error_message() );
		}
	}
}
