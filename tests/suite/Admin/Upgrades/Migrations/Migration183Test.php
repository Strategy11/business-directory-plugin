<?php
/**
 * @package WPBDP\Tests\Admin\Upgrades\Migrations
 */

namespace WPBDP\Tests\Admin\Upgrades\Migrations;

use Brain\Monkey\Functions;
use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP__Migrations__18_3;

/**
 * Unit tests for Migration to DB version 18.3.
 */
class Migration183Test extends TestCase {

	/**
	 * @since 5.1.10
	 */
	public function setup() {
		parent::setup();

		$this->installer = Mockery::mock( 'WPBPD_Installer' );

		$this->installer->shouldReceive(
			array(
				'get_migration_version_from_class_name' => '18.3',
			)
		);
		include_once WPBDP_INC . 'admin/upgrades/migrations/migration-18_3.php';
	}

	/**
	 * @since 5.1.10
	 */
	public function test_migrate_when_user_notifications_settings_were_never_stored() {
		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( array( 'new-listing', 'listing-published' ) );

		$this->check_all_user_notifications_are_enabled();
	}

	/**
	 * @since 5.1.10
	 */
	private function check_all_user_notifications_are_enabled() {
		Functions\expect( 'wpbdp_set_option' )
			->once()
			->with(
				'user-notifications',
				Mockery::on(
					function( $options ) {
						if ( ! in_array( 'listing-expires', $options, true ) ) {
							  return false;
						}

						if ( 3 !== count( $options ) ) {
							return false;
						}

						return true;
					}
				)
			);

		$migration = new WPBDP__Migrations__18_3( $this->installer );

		// Execution.
		$migration->migrate();
	}

	/**
	 * @since 5.1.10
	 */
	public function test_migrate_when_user_notifications_settings_are_false() {
		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( false );

		$this->check_all_user_notifications_are_enabled();
	}

	/**
	 * @since 5.1.10
	 */
	public function test_migrate_when_user_notifications_settings_are_disabled() {
		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( array() );

		Functions\expect( 'wpbdp_set_option' )
			->once()
			->with(
				'user-notifications',
				Mockery::on(
					function( $options ) {
						return array( 'listing-expires' ) === $options;
					}
				)
			);

		$migration = new WPBDP__Migrations__18_3( $this->installer );

		// Execution.
		$migration->migrate();
	}
}
