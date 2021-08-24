<?php
/**
 * @package WPBDP\Tests\Plugin\Fields
 */

namespace WPBDP\Tests\Plugin\Fields;

use Brain\Monkey\Functions;
use Brain\Monkey\Filters;
use Mockery;
use Patchwork;

use WPBDP\Tests\TestCase;

use WPBDP_Form_Field;

/**
 * Test cases for Form Field class
 */
class FormFieldTest extends TestCase {

	/**
	 * Test convert_input().
	 */
	public function test_convert_input_applies_filter() {
		// Uncomment me when FormField becomes easier to test.
		$this->markTestSkipped();

		$original_value = 'something';

		// This is a hack to avoid having to load FormField and all form field
		// types just for this test.
		$this->redefine( 'WPBDP_Form_Field::__construct', Patchwork\always( null ) );

		Functions\when( 'wp_parse_args' )->returnArg();

		Filters\expectApplied( 'wpbdp_form_field_pre_convert_input' )
			->never()
			->with( Mockery::any(), Mockery::any() );

		Filters\expectApplied( 'wpbdp_form_field_pre_convert_input' )
			->once()
			->with( null, Mockery::any(), Mockery::any() );

		$form_field = new WPBDP_Form_Field();

		// Execution
		$form_field->convert_input( $original_value );
	}
}
