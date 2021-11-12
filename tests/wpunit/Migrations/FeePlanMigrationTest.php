<?php
namespace Migrations;

use Codeception\Util\Debug;
use WPBDP__Fee_Plan;

class FeePlanMigrationTest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	protected function _before() {
		$this->save_fee();
	}

	protected function _after() {
		global $wpdb;

		// Update plans.
		$wpdb->query( "UPDATE {$wpdb->prefix}wpbdp_plans SET `enabled` = 1 WHERE `amount` > 0;" );
	}

	public function testMigration() {
		global $wpdb;
		$this->tester->wantToTest( 'Migration' );

		require_once( WPBDP_PATH . 'includes/admin/upgrades/migrations/migration-18_5.php' );
		
		$all         = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
		$disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$enabled     = $all - $disabled;

		Debug::debug( 'Total disabled ' . $disabled );
		Debug::debug( 'Total enabled ' . $enabled );

		$migrator    = new WPBDP__Migrations__18_5();
		$migrator->migrate();
		$after_disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$after_enabled     = $all - $after_disabled;

		Debug::debug( 'After Total disabled ' . $after_disabled );
		Debug::debug( 'After Total enabled ' . $after_enabled );

		$this->assertTrue( ( $after_disabled > $disabled ), 'Total disabled after is greater' );
	}

	private function save_fee() {
		$this->markTestSkipped(
			'mysqli fetch error on save'
		);
		$fee = new WPBDP__Fee_Plan(
			array(
				'label' 	=> 'Premium Fee Plan',
				'amount'	=> 100,
				'days'		=> 365,
				'sticky'	=> 0,
				'recurring'	=> 0,
				'supported_categories' => 'all'
			)
		);
		$result = $fee->save();
		if ( is_wp_error( $result ) ) {
			Debug::debug( 'Error saving paid plan' );
			Debug::debug( $result );
		}
	}
}
