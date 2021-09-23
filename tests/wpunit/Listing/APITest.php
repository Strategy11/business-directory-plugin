<?php
/**
 * Includes tests for the Listings API class.
 */

namespace Listing;

use WPBDP_Listings_API;

/**
 * Tests for the Listings API class.
 */
class APITest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;


	protected function _before() {

	}

	protected function _after() {
		global $wpdb;

		// Remove any existing payment in database
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE 1;" );
	}

	public function testListingPublishedStatusAfterPayment() {
		$this->tester->wantToTest( 'Payment Listing publish status' );
		$this->markTestSkipped(
			'Cannot get mysqli'
		);
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
	}
}
