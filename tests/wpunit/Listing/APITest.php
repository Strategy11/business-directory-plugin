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

	public function testListingPublishedStatusAfterPayment() {
		$this->tester->wantToTest( 'Payment Listing publish status' );
		wpbdp_set_option( 'new-post-status', 'publish' ); // New post status will be set to publish.
		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'pending_payment',
				'post_title'  => '(no title)',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
			$payment = $listing->generate_or_retrieve_payment();
			// Execute
			$payment->status = 'completed';
			$payment->save();

			// // Verification.
			$this->assertEquals( 'publish', get_post_status( $listing->get_id() ) );
		} else {
			$this->assertTrue( is_wp_error( $listing ), $listing->get_error_message() );
		}
	}
}
