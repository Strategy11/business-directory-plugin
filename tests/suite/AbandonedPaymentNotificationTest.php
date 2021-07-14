<?php
/**
 * Tests for AbandonedPaymentNotification class.
 */

namespace WPBDP\Tests\Plugin;

use Brain\Monkey\Functions;
use Mockery;
use Patchwork;

use \WPBDP\Tests\TestCase;

use WPBDP__Abandoned_Payment_Notification;

/**
 * Test case for AbandonedPaymentNotification class.
 */
class AbandonedPaymentNotificationTest extends TestCase {

    public function test_send_abandoned_payment_notifications_sends_email() {
        $settings = Mockery::mock( 'WPBDP__Settings' );
        $db = Mockery::mock();
        $email = Mockery::mock( 'WPBDP__Email' );
        $payments = array(
            (object) array(
                'id' => rand() + 1,
            ),
        );
        $listing = Mockery::mock( 'WPBDP_Listing' );
        $payment = Mockery::mock( 'WPBDP_Payment' );

        $email->shouldReceive( 'send' )->atLeast()->once();

        $listing->shouldReceive( 'get_title' )->andReturn( 'The Title' );
        $listing->shouldReceive( 'get_id' )->andReturn( rand() + 1 );

        $payment->shouldReceive( 'get_listing' )->andReturn( $listing );
        $payment->shouldReceive( 'get_checkout_url' )->andReturn( 'https://example.com' );

        $settings->shouldReceive( 'get_option' )
            ->with( 'payment-abandonment-threshold' )
            ->andReturn( 1 );

        $db->prefix = 'wp_';
        $db->shouldReceive( 'prepare' )->andReturnUsing( function() {
            return call_user_func_array( 'sprintf', func_get_args() );
        } );
        $db->shouldReceive( 'get_results' )->andReturn( $payments );

        $objects = Mockery::mock();
        $objects->shouldReceive( 'get' )->andReturn( $payment );

        $this->redefine( 'WPBDP_Payment::objects', Patchwork\always( $objects ) );

        Functions\when( 'absint' )->alias( 'intval' );
        Functions\when( 'current_time' )->justReturn( time() );
        Functions\when( 'wpbdp_format_time' )->justReturn( '2017-12-02 13:39:00' );
        Functions\when( 'update_option' )->justReturn( null );
        Functions\when( 'get_the_title' )->justReturn( 'The Title' );
        Functions\when( 'esc_url' )->returnArg();
        Functions\when( 'wpbusdirman_get_the_business_email' )->justReturn( 'admin@example.com' );

        Functions\expect( 'get_option' )
            ->with( 'wpbdp-payment-abandonment-notified', array() )
            ->andReturn( array() );

        Functions\expect( 'wpbdp_email_from_template' )
            ->atLeast()->once()
            ->with( 'email-templates-payment-abandoned', Mockery::any() )
            ->andReturn( $email );

        $notification = new WPBDP__Abandoned_Payment_Notification( $settings, $db );

        // Execution.
        $notification->send_abandoned_payment_notifications();
    }
}
