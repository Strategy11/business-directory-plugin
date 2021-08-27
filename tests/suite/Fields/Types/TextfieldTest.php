<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_TextField;

class TextfieldTest extends TestCase {

	public function test_render_html_output() {
		$content = 'Sample Test';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		$type = new WPBDP_FieldTypes_TextField();

		// Execution
		$output = $type->render_field_inner( $field, $content, 'anything' );

		// Verification
		$this->assertContains( 'wpbdp-field-' . $field->get_id(), $output );
		$this->assertContains( $content, $output );
	}
}
