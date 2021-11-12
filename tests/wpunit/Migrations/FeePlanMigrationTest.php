<?php
namespace Migrations;

require_once( WPBDP_PATH . 'includes/admin/upgrades/migrations/migration-18_5.php' );

use Codeception\Util\Debug;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;
use WPBDP_Installer;
use WPBDP__Migrations__18_5;

class FeePlanMigrationTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	private static $saved_fees = array();
	
	protected static function before_tear_down() {
		global $wpdb;
		if ( empty( self::$saved_fees ) ) {
			return;
		}
		// Delete plans.
		$sql   = "DELETE FROM {$wpdb->prefix}wpbdp_plans p WHERE p.id IN(" . implode( ', ', array_fill( 0, count( self::$saved_fees ), '%d' ) ) . ')';
		$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), self::$saved_fees ) );
		$wpdb->query( $query );
	}

	/**
	 * Test migration
	 */
	public function testMigration() {
		global $wpdb;
		$this->tester->wantToTest( 'Migration' );
		$all         = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans" ) );
		$disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$enabled     = $all - $disabled;

		Debug::debug( 'Total disabled ' . $disabled );
		Debug::debug( 'Total enabled ' . $enabled );
		$installer 	 = new WPBDP_Installer( '18.5' );
		$migrator    = new WPBDP__Migrations__18_5( $installer );
		$migrator->migrate();
		$after_disabled    = absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_plans WHERE enabled = %d", 0 ) ) );
		$after_enabled     = $all - $after_disabled;

		Debug::debug( 'After Total disabled ' . $after_disabled );
		Debug::debug( 'After Total enabled ' . $after_enabled );

		$this->assertTrue( ( $after_disabled > $disabled ), 'Total disabled after is greater' );
	}

	/**
	 * Save and persist fee plans for  testing
	 */
	protected function after_setup() {
		self::$saved_fees = array();
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
			self::$saved_fees[] = $fee->id;
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
			self::$saved_fees[] = $fee->id;
		}
	}
}
