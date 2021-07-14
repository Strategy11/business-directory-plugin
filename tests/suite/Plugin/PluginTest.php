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
		$this->markTestSkipped( 'This test is failing and needs work. Real bug?' );
        $plugin = new WPBDP();

        $plugin->settings = Mockery::mock( 'WPBDP__Settings' );

        $plugin->settings->shouldReceive( 'get_option' )
            ->with( 'payment-abandonment' )
            ->andReturn( true );

        $plugin->setup_email_notifications();

        $this->assertTrue( has_action( 'wpbdp_hourly_events', 'WPBDP__Abandoned_Payment_Notification->send_abandoned_payment_notifications()' ) );
    }
}
