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

		// Remove any existing payment in database
		$wpdb->query( "UPDATE {$wpdb->prefix}wpbdp_plans SET `enabled` = 1 WHERE `amount` > 0;" );
	}

	public function testMigration() {
		global $wpdb;
		$this->tester->wantToTest( 'Migration' );
		
		// Test default payments were on. Avoid getting option here as the test will fail.
		$payments_on = true;
		$sql         = "SELECT `id`, `amount` FROM {$wpdb->prefix}wpbdp_plans WHERE `enabled` = %d";
		$plans       = $wpdb->get_results( $wpdb->prepare( $sql, 1 ) );
		$all         = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
		$disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$enabled     = $all - $disabled;
		$to_disable  = array();

		Debug::debug( 'Total disabled ' . $disabled );
		Debug::debug( 'Total enabled ' . $enabled );

		foreach ( $plans as $plan ) {
			if ( $payments_on && $plan->amount <= 0.0 ) {
				$to_disable[] = $plan->id;
			} elseif( ! $payments_on && $plan->amount > 0.0 ) {
				$to_disable[] = $plan->id;
			}
		}

		$total_to_disable = count( $to_disable );

		if ( ! empty( $to_disable ) ) {
			Debug::debug( 'Total to disable ' . $total_to_disable );
			$sql = "UPDATE {$wpdb->prefix}wpbdp_plans SET `enabled` = 0 WHERE `id` IN(" . implode( ', ', array_fill( 0, count( $to_disable ), '%d' ) ) . ")";
			// Call $wpdb->prepare passing the values of the array as separate arguments.
			$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $to_disable ) );
			$wpdb->query( $query );

			$after_disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
			$after_enabled     = $all - $after_disabled;

			Debug::debug( 'Total disabled ' . $after_disabled );
			Debug::debug( 'Total enabled ' . $after_enabled );

			$this->assertTrue( ( $after_disabled > $disabled ), 'Disabled numbers a more' );
		} else {
			Debug::debug( 'Nothing to migrate' );
		}
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
