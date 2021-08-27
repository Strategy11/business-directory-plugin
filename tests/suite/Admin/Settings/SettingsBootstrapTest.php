<?php
/**
 * @package WPBDP\Tests\Plugin\Admin\Settings
 */

namespace WPBDP\Tests\Plugin\Admin\Settings;

use Brain\Monkey\Functions;
use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP__Settings__Bootstrap;

/**
 * Unit tests for Settings Bootstrap class.
 */
class SettingsBootstrapTest extends TestCase {

	/**
	 * @since 5.1.10
	 */
	public function test_setting_to_control_expiration_notifications_for_users() {
		Functions\when( 'wpbdp_register_settings_group' )->justReturn( null );
		Functions\when( 'wpbdp_get_form_fields' )->justReturn( array() );
		Functions\when( 'get_option' )->justReturn( false );

		Functions\expect( 'wpbdp_register_setting' )
			->once()
			->with(
				Mockery::on(
					function( $args ) {
						if ( isset( $args['id'] ) && 'user-notifications' === $args['id'] ) {
							  return isset( $args['options']['listing-expires'] );
						}

						return false;
					}
				)
			);

		$settings = new WPBDP__Settings__Bootstrap();

		// Execution.
		$settings->register_initial_settings();
	}
}
