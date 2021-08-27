<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_TextArea;

class TextAreaTest extends TestCase {


	public function test_render_html_output() {
		$content = 'Sample Test';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		$type = new WPBDP_FieldTypes_TextArea();

		// Execution
		$output = $type->render_field_inner( $field, $content, 'anything' );

		// Verification
		$this->assertContains( 'textarea', $output );
		$this->assertContains( $content, $output );
	}
}
