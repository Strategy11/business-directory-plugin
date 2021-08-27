<?php

namespace WPBDP\Tests\Plugin\Settings;

use Brain\Monkey\Functions;
use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_Settings;

class SortBarTest extends TestCase {

	public function test_sortbar_fields_cb_applies_render_field_label_filter() {
		$label = 'Some Label';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		Phake::when( $field )->get_label->thenReturn( $label );

		Functions\when( 'wpbdp_get_form_fields' )->justReturn( array( $field ) );

		Functions\expect( 'apply_filters' )
			->atLeast()->once()
			->with( 'wpbdp_render_field_label', $label, $field );

		// Execution
		wpbdp_sortbar_get_field_options();
	}
}

