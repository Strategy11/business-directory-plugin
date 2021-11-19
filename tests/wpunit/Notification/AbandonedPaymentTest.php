<?php
/**
 * Includes tests for the Notification API class.
 */
namespace Notification;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Abandoned_Payment_Notification;
use WPBDP__Settings;

/**
 * Tests for the Notifications.
 */
class AbandonedPaymentTest extends WPUnitTestCase {

    /**
	 * @var \WpunitTester
	 */
	protected $tester;

    public function testAbandonedPaymentNotification() {
		global $wpdb;
		$this->tester->wantToTest( 'Abandoned Payment Notification' );

		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'pending_payment',
				'post_title'  => '(no title)',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
			$table_name = $wpdb->prefix . 'wpbdp_payments';
			$payment_id = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE listing_id = %d", $this->id ) );
			if ( $payment_id ) {
				$notified = get_option( 'wpbdp-payment-abandonment-notified', array() );
				$before_notified = count( $notified );
				$created_at = date_i18n( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
				$wpdb->update( $table_name, array( 'status'=> 'pending', 'payment_type' => 'initial', 'created_at' => $created_at ), array( 'id' => $payment_id )  );

				$settings = new WPBDP__Settings();
        		$settings->bootstrap();
				$abandoned_payment_notification = new WPBDP__Abandoned_Payment_Notification( $settings, $wpdb );
				$abandoned_payment_notification->send_abandoned_payment_notifications();
				$notified = get_option( 'wpbdp-payment-abandonment-notified', array() );
				$after_notified = count( $notified );

				// Assert that the notification was sent
				$this->assertTrue( $after_notified > $before_notified, 'Notification was sent' );
			} else {
				$this->fail( 'Listing has no payment' );
			}
		} else {
			$this->fail( $listing->get_error_message() );
		}
    }
}