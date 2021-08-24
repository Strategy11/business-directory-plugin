<?php
/**
 * @package WPBDP\Tests\Plugin
 */

namespace WPBDP\Tests\Plugin;

use Brain\Monkey\Functions;
use Mockery;
use Patchwork;
use Phake;

use WPBDP\Tests\TestCase;

use WPBDP__Listing_Email_Notification;


/**
 * Unit tests for Listing Email Notification class.
 */
class ListingEmailNotificationTest extends TestCase {

	/**
	 * @since 5.1.10
	 */
	public function test_send_listing_published_notifiation_method_is_called() {
		$post = (object) array(
			'ID' => rand() + 1,
		);

		$new_status = 'publish';
		$old_status = 'pending';
		$args       = array();

		Functions\when( 'get_post_type' )->justReturn( WPBDP_POST_TYPE );

		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( array( 'listing-published' ) );

		$notification = new WPBDP__Listing_Email_Notification();

		Patchwork\redefine(
			'WPBDP__Listing_Email_Notification::send_listing_published_notification',
			function( $post_id, $post ) use ( &$args ) {
				$args['post_id'] = $post_id;
				$args['post']    = $post;
			}
		);

		// Execution.
		$notification->listing_published_notification( $new_status, $old_status, $post );

		// Verification.
		$this->assertEquals( $post->ID, $args['post_id'] );
		$this->assertEquals( $post, $args['post'] );
	}

	/**
	 * Tests to confirm that the send_listing_published_notification method
	 * sends an email only when the post being saved is a BD listing.
	 *
	 * See: https://github.com/drodenbaugh/BusinessDirectoryPlugin/issues/2998
	 */
	public function test_send_listing_published_notification_works_with_listings_only() {
		$post = (object) array(
			'ID'        => rand() + 1,
			'post_type' => 'not_wpbdp_listing',
		);

		Patchwork\redefine( 'WPBDP_Listing_Upgrade_API::instance', Patchwork\always( null ) );

		Functions\expect( 'wpbdp_email_from_template' )->never();

		$notification = new WPBDP__Listing_Email_Notification();

		// Execution.
		$notification->send_listing_published_notification( $post->ID, $post );
	}

	/**
	 * @since 5.1.10
	 */
	public function test_status_change_notifiications_dont_send_expiration_notifications() {
		$listing    = (object) array();
		$new_status = 'expired';

		Functions\expect( 'wpbdp_get_option' )
			->with( 'listing-renewal' )
			->andReturn( true );

		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( array( 'not listing-expires' ) );

		$notification = Mockery::mock( 'WPBDP__Listing_Email_Notification' )->makePartial();

		$notification->shouldReceive( 'send_notices' )
			->never();

		// Execution.
		$notification->status_change_notifications( $listing, null, $new_status );
	}

	/**
	 * @since 5.1.10
	 */
	public function test_listing_renewal_email() {
		Functions\expect( 'wpbdp_get_option' )
			->with( 'admin-notifications' )
			->andReturn( array() );

		Functions\expect( 'wpbdp_get_option' )
			->with( 'user-notifications' )
			->andReturn( array( 'not listing-expires' ) );

		Functions\expect( 'do_action' )
			->never();

		$notification = new WPBDP__Listing_Email_Notification();

		// Execution.
		$notification->listing_renewal_email( null, null, null );
	}
}
