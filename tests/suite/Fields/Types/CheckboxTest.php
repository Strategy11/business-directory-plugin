<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Checkbox;

class CheckboxTest extends TestCase {

	public function test_render_checkbox_options() {
		$value   = 'anything';
		$context = 'whatever';
		$label   = 'Sample Check Box';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		$options = array(
			$value => $label,
		);

		Phake::when( $field )->get_association->thenReturn( 'not-category' );
		Phake::when( $field )->set_data( 'options', $options );
		Phake::when( $field )->data( 'options', Phake::ignoreRemaining() )->thenReturn( $options );

		$type = new WPBDP_FieldTypes_Checkbox();

		// Execution
		// This sets the checked value to that of the option
		$output = $type->render_field_inner( $field, $value, $context );

		// Verification
		$this->assertContains( $label, $output );
		$this->assertContains( $value, $output );
	}
}
