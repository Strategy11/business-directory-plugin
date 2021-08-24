<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Select;

class SelectTest extends TestCase {

	public function test_render_field_inner_shows_empty_option() {
		$value              = 'anything';
		$context            = 'whatever';
		$empty_option_label = 'Empty Option Label';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		Phake::when( $field )->get_association->thenReturn( 'not-category' );
		Phake::when( $field )->data( 'show_emprty_option' )->thenReturn( true );
		Phake::when( $field )->data( 'empty_option_label', Phake::ignoreRemaining() )->thenReturn( $empty_option_label );

		$type = new WPBDP_FieldTypes_Select();

		// Execution
		$output = $type->render_field_inner( $field, $value, $context );

		// Verification
		$this->assertContains( $empty_option_label, $output );
	}
}
