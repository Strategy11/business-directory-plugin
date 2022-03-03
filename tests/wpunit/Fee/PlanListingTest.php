<?php
/**
 * Includes tests for the Fees class.
 */

namespace Fee;

use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;
use WPBDP__Fee_Plan;

/**
 * Tests for fee Listing
 */
class PlanListingTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testFeePlanVisibility() {
		$this->tester->wantToTest( 'Test Fee Plan Visibility' );
		$this->create_fees();
		
		$this->init_gateway();
		
		$this->test_with_gateway_disabled();

		$this->test_with_gateway_enabled();
		
		$this->test_with_default_plan_disabled();
	}

	/**
	 * Initialize the gateway.
	 * Set up Auth net gateway but disable it.
	 *
	 */
	private function init_gateway() {
		wpbdp_set_option( 'payments-test-mode', true );
		wpbdp_set_option( 'authorize-net', 0 );
		$this->assertFalse( 1 === wpbdp_get_option( 'authorize-net' ), 'Gateway Disabled' );
		// Random details for testing purposes. We won't attempt a charge, we just need to enable payments.
		wpbdp_set_option( 'authorize-net-login-id', '7h6MbLNyn9qb' );
		wpbdp_set_option( 'authorize-net-transaction-key', '98GHnS594xy32V7d' );
	}

	/**
	 * Test fee plans with disabled gateways.
	 * This should return a total of 0
	 */
	private function test_with_gateway_disabled() {
		Debug::debug( 'Test disabled payments' );
		$plans = wpbdp_get_fee_plans();
		$total = 0;
		$free_plan = null;
		foreach ( $plans as $plan ) {
			$total += absint( $plan->amount );
			if ( 'free' === $plan->tag ) {
				$free_plan = $plan;
			}
		}
		$this->assertTrue( ( $total === 0 ), 'Plan total amount is 0' );
		$this->assertTrue( count( $plans ) === 2, 'Plan count is 2' );
		$this->assertTrue( ! is_null( $free_plan ), 'Free plan included in all free plans' );
	}

	/**
	 * Payment enabled plan list test.
	 * Test the payment enabled plan total . Total should be more than 0
	 */
	private function test_with_gateway_enabled() {
		wpbdp_set_option( 'authorize-net', 1 );
		$this->assertTrue( 1 === wpbdp_get_option( 'authorize-net' ), 'Gateway Enabled' );
		$payments_on = wpbdp_payments_possible();

		$this->assertTrue( $payments_on, 'Payments Enabled' );

		$plans = wpbdp_get_fee_plans();
		$free_plan = null;
		$total = 0;
		foreach ( $plans as $plan ) {
			Debug::debug( 'Plan amount :: ' . $plan->amount );
			$total += absint( $plan->amount );
			if ( 'free' === $plan->tag ) {
				$free_plan = $plan;
			}
		}
		$this->assertTrue( ( $total > 0 ), 'Plan total amount is more than 0' );
		$this->assertTrue( count( $plans ) === 5, 'Plan count is 5' );
		$this->assertTrue( ! is_null( $free_plan ), 'Free plan included in all paid plans' );

		// Disable default plan for next test phase.
		$free_plan->enabled = false;
		$free_plan->save();

		$this->assertFalse( $free_plan->enabled, 'Default plan disabled' );
	}

	/**
	 * Default plan should be null as it was disabled in `paymentEnabledPlanTest()`
	 */
	private function test_with_default_plan_disabled() {
		$plans = wpbdp_get_fee_plans();
		$free_plan = null;
		foreach ( $plans as $plan ) {
			if ( 'free' === $plan->tag ) {
				$free_plan = $plan;
			}
		}
		$this->assertTrue( is_null( $free_plan ), 'Free plan is not included in all paid plans' );
		$this->assertTrue( count( $plans ) === 4, 'Plan count is 4. Free plan is disabled' );
	}

	/**
	 * Test create fees
	 */
	private function create_fees() {
		Debug::debug( 'Creating fees' );

		$this->create_fee(
			array(
				'label'                => 'First Premium Fee',
				'description'          => '',
				'amount'               => 100.0,
				'days'                 => 100,
				'sticky'               => 0,
				'recurring'            => 0,
				'images'               => 5,
				'tag'                  => 'premium',
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => true,
			)
		);

		$this->create_fee(
			array(
				'label'                => 'Second Premium Fee',
				'description'          => '',
				'amount'               => 200.0,
				'days'                 => 100,
				'sticky'               => 0,
				'recurring'            => 0,
				'images'               => 5,
				'tag'                  => 'second_premium',
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => true,
			)
		);

		$this->create_fee(
			array(
				'label'                => 'Third Free Fee',
				'description'          => '',
				'amount'               => 0,
				'days'                 => 100,
				'sticky'               => 0,
				'recurring'            => 0,
				'images'               => 15,
				'tag'                  => 'third_free',
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => true,
			)
		);
	}

	/**
	 * Create the fee plan
	 */
	private function create_fee( $data ) {
		$fee = new WPBDP__Fee_Plan( $data );
		$result = $fee->save();
		if ( ! is_wp_error( $result ) ) {
			$this->assertTrue( is_int( $fee->id ), 'Fee Created' );
			$this->assertTrue( $fee->enabled, 'Fee Enabled' );
		} else {
			$this->fail( 'Fee creation failed : ' . $result->get_error_message() );
		}
	}
}
