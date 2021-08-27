<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_MultiSelect;

class MultiSelectTest extends TestCase {


	public function test_render_html_options() {
		$context            = 'whatever';
		$empty_option_label = 'Empty Option Label';

		$options = array(
			'first'  => 'First Select',
			'second' => 'Second Select',
			'third'  => 'Third Select',
		);

		$values = array( 'first', 'second' );

		$field = Phake::mock( 'WPBDP_Form_Field' );

		Phake::when( $field )->get_association->thenReturn( 'not-category' );
		Phake::when( $field )->data( 'show_emprty_option' )->thenReturn( true );
		Phake::when( $field )->data( 'empty_option_label', Phake::ignoreRemaining() )->thenReturn( $empty_option_label );
		Phake::when( $field )->data( 'options', Phake::ignoreRemaining() )->thenReturn( $options );
		Phake::when( $field )->set_data( 'options', $options );

		$type = new WPBDP_FieldTypes_MultiSelect();

		// Execution
		// SEt selected items
		$output = $type->render_field_inner( $field, $values, $context );

		// Verification
		$this->assertContains( $empty_option_label, $output );
		$this->assertContains( 'multiple="multiple"', $output );

		// There should be 2 selected occurences
		$selected = substr_count( $output, 'selected="selected"' );

		$this->assertEquals( count( $values ), $selected );
	}
}
