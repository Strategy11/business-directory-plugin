<?php
/**
 * Includes tests for the Fees class.
 */

namespace Fee;

use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;
use WPBDP__Fee_Plan;
use WPBDP_Fees_API;

/**
 * Tests for fee Activation and Deavtivation
 */
class FeeActivationTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testFeeActivation() {
		$this->tester->wantToTest( 'Fee Activation and Deactivation' );

		$this->create_fees();
	}

	/**
	 * Process fee plans
	 */
	private function process_plans() {
		$plans = wpbdp_get_fee_plans( array( 'admin_view' => true ) );
		$enabled_plans = WPBDP_Fees_API::get_enabled_plans();
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
			return $fee;
		} else {
			$this->fail( 'Fee creation failed : ' . $result->get_error_message() );
		}
		return false;
	}
}
