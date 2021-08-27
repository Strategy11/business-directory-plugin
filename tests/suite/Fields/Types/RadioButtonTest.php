<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_RadioButton;

class RadioButtonTest extends TestCase {


	public function test_render_html_options() {
		$context = 'whatever';

		$options = array(
			'first'  => 'First Select',
			'second' => 'Second Select',
			'third'  => 'Third Select',
		);

		$value = 'second';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		Phake::when( $field )->get_association->thenReturn( 'not-category' );
		Phake::when( $field )->data( 'show_empty_option' )->thenReturn( true );
		Phake::when( $field )->data( 'options', Phake::ignoreRemaining() )->thenReturn( $options );
		Phake::when( $field )->set_data( 'options', $options );

		$type = new WPBDP_FieldTypes_RadioButton();

		// Execution
		// SEt selected items
		$output = $type->render_field_inner( $field, $value, $context );

		// Verification
		$this->assertContains( $value, $output );

		// There should be 2 selected occurences
		$selected = substr_count( $output, 'checked="checked"' );

		$this->assertEquals( 1, $selected );
	}
}
