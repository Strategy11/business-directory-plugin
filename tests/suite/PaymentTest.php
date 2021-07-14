<?php
/**
 * @package WPBDP\Tests\Plugin
 */

namespace WPBDP\Tests\Plugin;

use Patchwork;

use WPBDP\Tests\TestCase;

use WPBDP_Payment;

/**
 * Tests for Payment class.
 */
class PaymentTest extends TestCase {

    public function test_get_payer_details() {
        $required_keys = array(
            'email',
            'first_name',
            'last_name',
            'address',
            'city',
            'state',
            'country',
            'zip',
        );

        $model_info = array();

        Patchwork\redefine( 'WPBDP__DB__Model::get_model_info', Patchwork\always( $model_info ) );
        Patchwork\redefine( 'WPBDP__DB__Model::init', Patchwork\always( null ) );
        Patchwork\redefine( 'WPBDP__DB__Model::is_valid_attr', Patchwork\always( true ) );

        $payment = new WPBDP_Payment( array() );

        // Execution
        $data = $payment->get_payer_details();

        // Verification
        foreach( $required_keys as $key ) {
            $this->assertArrayHasKey( $key, $data );
        }
    }
}

