<?php
/**
 * @package WPBDP\Tests\Plugin
 */

use Brain\Monkey\Functions;

use WPBDP\Tests\TestCase;

/**
 * TestCase for payment related functions.
 */
class PaymentFunctionsTest extends TestCase {

	public function test_payments_possible_returns_false_if_payments_are_off() {
		Functions\expect( 'wpbdp_get_option' )
			->once()
			->with( 'payments-on' )
			->andReturn( false );

		Functions\expect( 'wpbdp' )->never();

		// Execution & Verification
		$this->assertFalse( wpbdp_payments_possible() );
	}

	public function test_payments_possible_returns_true_if_payments_are_on_and_a_gateway_is_enabled() {
		// Creating anonymous mock because WPBDP is hard to mock.
		$plugin = Mockery::mock();

		$plugin->payment_gateways = Mockery::mock( 'WPBDP__Payment_Gateways' );

		$plugin->payment_gateways->shouldReceive( 'can_pay' )->andReturn( true );

		Functions\expect( 'wpbdp_get_option' )
			->once()
			->with( 'payments-on' )
			->andReturn( true );

		Functions\expect( 'wpbdp' )->once()->andReturn( $plugin );

		// Execution & Verification
		$this->assertTrue( wpbdp_payments_possible() );
	}
}

