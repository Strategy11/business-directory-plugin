<?php
/**
 * Includes tests for the Fees class.
 */

namespace Fee;

use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;
use WPBDP__Fee_Plan;

/**
 * Tests for fee CRUDE
 */
class FeeCrudeTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	private $fee = null;

	public function testFeeCrude() {
		$this->tester->wantToTest( 'Fee Plan CRUDE' );
		$this->createFee();
		$this->editFee();
		$this->toggleFee();
		$this->deleteFee();
	}

	/**
	 * Test create fee
	 */
	private function createFee() {
		Debug::debug( 'Creating the fee' );
		$this->fee = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Premium Fee',
				'amount'               => 100.0,
				'days'                 => 100,
				'sticky'               => 0,
				'recurring'            => 0,
				'images'               => 5,
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => 1,
			)
		);
		$result    = $this->fee->save();
		if ( ! is_wp_error( $result ) ) {
			$this->assertTrue( is_int( $this->fee->id ), 'Fee Created' );
		} else {
			$this->fail( 'Fee creation failed : ' . $result->get_error_message() );
		}
	}

	/**
	 * Test edit fee
	 */
	private function editFee() {
		Debug::debug( 'Editing the fee' );
		if ( ! $this->fee || ! $this->fee->exists() ) {
			$this->fail( 'Could not retrieve previously created fee plan' );
		}
		$result = $this->fee->update(
			array(
				'label'                => 'Premium Fee updated',
				'amount'               => 100.0,
				'days'                 => 100,
				'sticky'               => 0,
				'recurring'            => 0,
				'images'               => 5,
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => true,
			)
		);
		if ( ! is_wp_error( $result ) ) {
			$this->assertTrue( ( $this->fee->label === 'Premium Fee updated' ), 'Fee updated' );
		} else {
			$this->fail( 'Fee update failed : ' . $result->get_error_message() );
		}
	}

	/**
	 * Toggle fee enabled and disabled states
	 */
	private function toggleFee() {
		Debug::debug( 'Editing the fee' );
		if ( ! $this->fee || ! $this->fee->exists() ) {
			$this->fail( 'Could not retrieve previously created fee plan' );
		}
		$this->fee->enabled = false;
		$this->fee->save();

		$this->assertFalse( $this->fee->enabled, 'Fee disabled' );

		$this->fee->enabled = true;
		$this->fee->save();

		$this->assertTrue( $this->fee->enabled, 'Fee enabled' );
	}

	/**
	 * Test Delete Fee
	 */
	private function deleteFee() {
		Debug::debug( 'Deleting the fee' );
		if ( ! $this->fee || ! $this->fee->exists() ) {
			$this->fail( 'Could not retrieve previously created fee plan' );
		}
		$deleted = $this->fee->delete();
		if ( $deleted ) {
			$this->assertTrue( ( 1 === $deleted ), 'Fee deleted' );
			$this->fee = null;
		} else {
			$this->fail( 'Error deleting field ' );
		}
	}
}
