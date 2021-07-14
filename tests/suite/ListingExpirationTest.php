<?php
/**
 * @package WPBDP\Tests
 */

namespace WPBDP\Tests;

use Brain\Monkey\Functions;

use WPBDP__Listing_Expiration;

/**
 * Unit tests for Listing Expiration class.
 */
class ListingExpirationTest extends TestCase {

    /**
     * @since 5.1.10
     */
    public function test_send_expiration_reminders_dont_send_anything_if_expiration_notifications_are_disabled() {
        Functions\expect( 'wpbdp_get_option' )
            ->with( 'listing-renewal' )
            ->andReturn( true );

        // The enabled user notifications does not include 'listing-expires'.
        Functions\expect( 'wpbdp_get_option' )
            ->with( 'user-notifications' )
            ->andReturn( [ 'new-listing', 'listing-published' ] );

        Functions\expect( 'do_action' )
            ->never()
            ->withAnyArgs();

        $expirations = new WPBDP__Listing_Expiration();

        // Execution.
        $expirations->send_expiration_reminders();
    }

	/**
	 * @covers WPBDP__Listing_Expiration::get_expiring_listings
	 */
	public function test_convert_month_to_days() {
		$checks = array(
			'+1 month'  => '+30 days',
			'2 months'  => '60 days',
			'-1 month'  => '-30 days',
			'+2 months' => '+60 days',
			'+7 days'   => '+7 days',
			'+1 week'   => '+1 week',
		);

		$email_class = new WPBDP__Listing_Expiration();
		foreach ( $checks as $setting => $expected ) {
			$this->run_private_method( array( $email_class, 'convert_month_to_days' ), array( &$setting ) );
			$this->assertEquals( $expected, $setting );
		}
	}
}
