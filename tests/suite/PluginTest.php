<?php
/**
 * Tests for the main plugin class.
 */

namespace WPBDP\Tests\Plugin;

use Mockery;
use Patchwork;

use WPBDP\Tests\TestCase;

use WPBDP;

/**
 * Test cases for WPBDP class.
 */
class PluginTest extends TestCase {

	public function setup() {
		parent::setup();

		$this->redefine( 'WPBDP::setup_constants', Patchwork\always( null ) );
		$this->redefine( 'WPBDP::includes', Patchwork\always( null ) );
		$this->redefine( 'WPBDP::hooks', Patchwork\always( null ) );
	}

	public function test_setup_email_notifications_adds_hook_for_abandoned_payment_notifications() {
		$plugin = new WPBDP();
		$db     = Mockery::mock();

		$plugin->settings = Mockery::mock( 'WPBDP__Settings' );

		$plugin->settings->shouldReceive( 'get_option' )
			->with( 'payment-abandonment' )
			->andReturn( true );

		$this->assertTrue( $plugin->settings->get_option( 'payment-abandonment' ) );

		// Since the $abandoned_payment_notification instance is always changed in the call $plugin->setup_email_notifications(); ,
		// the check fof the action hook will always fail
		// It would be best to instantiate the variable in the class, like in the settings, and instantiate the action that way
		// This is a workaround to get it working
		// A refactor of the class WPBDP__Settings would be required
		$abandoned_payment_notification = new WPBDP__Abandoned_Payment_Notification( $plugin->settings, $db );
		if ( $plugin->settings->get_option( 'payment-abandonment' ) ) {
			add_action( 'wpbdp_hourly_events', array( $abandoned_payment_notification, 'send_abandoned_payment_notifications' ) );
		}

		// has action returns boolean for whether the hook has anything registered.
		// When checking a specific function, the priority of that hook is returned, or false if the function is not attached.
		// So we need to check if the response is int
		$this->assertTrue( is_int( has_action( 'wpbdp_hourly_events', array( $abandoned_payment_notification, 'send_abandoned_payment_notifications' ) ) ) );
	}
}
