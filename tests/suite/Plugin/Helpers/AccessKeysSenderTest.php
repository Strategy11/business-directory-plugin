<?php
/**
 * @package WPBDP\Tests\Helpers
 */

namespace WPBDP\Tests\Helpers;

use Brain\Monkey\Functions;
use Mockery;
use Patchwork;

use WPBDP\Tests\TestCase;

use WPBDP__Access_Keys_Sender;

/**
 * Test cases for Access Keys Sender class.
 */
class AccessKeysSenderTest extends TestCase {

    /**
     * Tests that send_access_keys() throws an exception when it receives an
     * invalid email address.
     *
     * @expectedException           Exception
     * @expectedExceptionMessage    not a valid e-mail address
     */
    public function test_send_access_keys_with_invalid_email_address() {
        Functions\when( 'is_email' )->justReturn( false );

        $sender = new WPBDP__Access_Keys_Sender();

        // Execution.
        $sender->send_access_keys( 'not an email address' );
    }

    /**
     * Tests that send_access_keys() throws an exception when there are no listings
     * associated wirth the given email address.
     *
     * @expectedException           Exception
     * @expectedExceptionMessage    no listings associated
     */
    public function test_send_access_keys_with_no_associated_listings() {
        Functions\when( 'is_email' )->justReturn( true );

        $this->redefine( 'WPBDP__Access_Keys_Sender::find_listings_by_email_address', Patchwork\always( array() ) );

        $sender = new WPBDP__Access_Keys_Sender();

        // Execution.
        $sender->send_access_keys( 'johndoe@example.org' );
    }

    /**
     * Tests that send_access_keys() throws an exception when the message can't be
     * sent.
     *
     * @expectedException           Exception
     * @expectedExceptionMessage    error occurred while sending
     */
    public function test_send_access_keys_when_message_cant_be_sent() {
        $listings = array(
            (object) array(
            ),
        );

        Functions\when( 'is_email' )->justReturn( true );

        $this->redefine( 'WPBDP__Access_Keys_Sender::find_listings_by_email_address', Patchwork\always( $listings ) );

        $message = Mockery::mock( 'WPBDP_Email' );
        $message->shouldReceive( 'send' )->andReturn( false );

        Functions\when( 'wpbdp_email_from_template' )->justReturn( $message );
        Functions\when( 'get_bloginfo' )->justReturn( 'Some Website' );

        $sender = new WPBDP__Access_Keys_Sender();

        // Execution.
        $sender->send_access_keys( 'johndoe@example.org' );
    }
}
