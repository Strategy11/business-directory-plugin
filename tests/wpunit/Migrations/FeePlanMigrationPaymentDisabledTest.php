<?php
namespace Migrations;

require_once WPBDP_PATH . 'includes/admin/upgrades/migrations/migration-18_5.php';

use Codeception\Util\Debug;
use WPBDP\Tests\BaseMigrationTestCase;
use WPBDP_Installer;
use WPBDP__Migrations__18_5;

class FeePlanMigrationPaymentDisabledTest extends BaseMigrationTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Test migration
	 */
	public function testPaymentsOnMigration() {
		global $wpdb;
		$this->tester->wantToTest( 'Payments Disabled Migration' );
		wpbdp_set_option( 'payments-on', false ); // Set payments as off.
		$payments_on = wpbdp_get_option( 'payments-on' );
		$counts      = $this->get_counts();
		$this->assertFalse( $payments_on, 'Payments are enabled' );
		Debug::debug( 'Total plans ' . $counts['all'] );
		Debug::debug( 'Total disabled ' . $counts['disabled'] );
		Debug::debug( 'Total enabled ' . $counts['enabled'] );
		$installer 	 = new WPBDP_Installer( '18.5' );
		$migrator    = new WPBDP__Migrations__18_5( $installer );
		$migrator->migrate();
		$after_counts = $this->get_counts();

		Debug::debug( 'After Total disabled ' . $after_counts['disabled'] );
		Debug::debug( 'After Total enabled ' . $after_counts['enabled'] );

		$this->assertTrue( ( $after_counts['enabled'] < $counts['enabled'] ), 'Total enabled after is more. Free tag is enabled' );
		$this->assertTrue( ( $after_counts['disabled'] > $counts['disabled'] ), 'Total disabled after is less. Free tag enabled' );
	}
}
