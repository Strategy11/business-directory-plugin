<?php
namespace WPBDP\Tests;


use Codeception\Util\Debug;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;

class BaseListingTestCase extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Save and persist fee plans for testing.
	 */
	protected function after_setup() {
		// Free plan check. If it does not exist, we create.
		// The database is created late
		$free_plan = wpbdp_get_fee_plan( 'free' );
		if ( ! $free_plan ) {
			$fee = new WPBDP__Fee_Plan(
				array(
					'label' 	=> 'Free Listing',
					'amount'	=> 0.0,
					'days'		=> absint( wpbdp_get_option( 'listing-duration' ) ),
					'sticky'	=> 0,
					'recurring'	=> 0,
					'images'    => absint( wpbdp_get_option( 'free-images' ) ),
					'supported_categories' => 'all',
					'pricing_model' => 'flat',
					'enabled' => 1,
					'tag' => 'free',
				)
			);
			$fee->save();
		}
	}
}
