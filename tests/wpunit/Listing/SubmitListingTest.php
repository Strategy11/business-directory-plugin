<?php
/**
 * Includes tests for when a listing is submitted
 */

namespace Listing;

use Codeception\Util\Debug;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;

/**
 * Submit listing tests
 */
class SubmitListingTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	private $paid_plan = false;

	/**
	 * Test submit listing process.
	 * This is after a user has input all required information.
	 * This tests payments based on different fee plans.
	 *
	 * @since 5.17
	 */
	public function testSubmitListing() {
		$this->tester->wantToTest( 'Listing payment submission' );
		if ( ! $this->paid_plan ) {
			$this->fail( 'Paid plan was not created' );
		}
		$test_user   = get_user_by( 'email', 'test@test.com' );
		$listing_id  = wp_insert_post(
			array(
				'post_author' => $test_user->ID,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'auto-draft',
				'post_title'  => '(no title)',
			)
		);

		$listing = wpbdp_get_listing( $listing_id );
		$plan    = wpbdp_get_fee_plan( 'free' );
		$listing->set_fee_plan( $plan );
		$has_plan = $listing->has_fee_plan( $plan->id );
		$this->assertTrue( $has_plan, 'Listing has free plan' );
		
		$payment = $listing->generate_or_retrieve_payment();
		if ( ! $payment ) {
			$this->fail( 'Payment not generated for listing' );
		}
		Debug::debug( $payment );
		$this->assertTrue( ( int ) $payment->amount === 0, 'Payment is free and price is 0' );
		// Plan change
		$payment = $listing->set_fee_plan_with_payment( $this->paid_plan );
		if ( ! $payment ) {
			$this->fail( 'Payment not generated for listing' );
		}
		Debug::debug( $payment );
		$this->assertTrue( ( int ) $payment->amount === 100, 'Payment is premium at 100' );
	}

	/**
	 * Set up paid plan
	 */
	protected function after_setup() {
		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Premium Fee Plan',
				'amount'	=> 100,
				'days'		=> 365,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'enabled'   => 1,
				'supported_categories' => 'all'
			)
		);
		$result = $fee->save();
		if ( ! is_wp_error( $result ) && $result ) {
			$this->paid_plan = $fee;
		}
	}
}
