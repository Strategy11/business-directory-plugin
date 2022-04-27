<?php
namespace WPBDP\Tests;

require_once WPBDP_PATH . 'includes/admin/upgrades/migrations/migration-18_5.php';

use Codeception\Util\Debug;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;
use WPBDP_Installer;

class BaseMigrationTestCase extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;


	/**
	 * Get counts of plans.
	 *
	 * @return array
	 */
	protected function get_counts() {
		global $wpdb;
		$all         = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
		$disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$enabled     = $all - $disabled;
		return compact( 'all', 'disabled', 'enabled' );
	}

	/**
	 * Save and persist fee plans for testing.
	 * Total new plans : 5 (excluding free plan).
	 * Enabled premium - 1
	 * Disabled premium - 1
	 * Enabled free - 2
	 * Disabled free 1
	 *
	 * If payments are off, disabled count goes up, enabled count goes down.
	 * If payments are on, disabled count goes up (enabled free is disabled), enabled count goes down (free plan is disabled).
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
		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Premium Fee Plan 1',
				'amount'	=> 100,
				'days'		=> 365,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$result = $fee->save();
		if ( ! is_wp_error( $result ) && $result ) {
			$fee->enabled = true;
        	$fee->save();
		}

		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Premium Fee Plan 2',
				'amount'	=> 100,
				'days'		=> 365,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$result = $fee->save();
		if ( ! is_wp_error( $result ) && $result ) {
			$fee->enabled = false;
        	$fee->save();
		}

		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Free Fee Plan 3',
				'amount'	=> 0,
				'days'		=> 365,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$fee->save();

		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Free Fee Plan 4',
				'amount'	=> 0,
				'days'		=> 24,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$fee->save();
		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Disabled Free Fee Plan 5',
				'amount'	=> 0,
				'days'		=> 24,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$result = $fee->save();
		if ( ! is_wp_error( $result ) && $result ) {
			$fee->enabled = false;
        	$fee->save();
		}
	}
}
