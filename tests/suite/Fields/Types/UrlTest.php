<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_URL;

class UrlTest extends TestCase {

	/**
	 * Needs more tests for url validation
	 */
	public function test_render_html_output() {
		$content = 'http://example.com';

		$field = Phake::mock( 'WPBDP_Form_Field' );

		$type = new WPBDP_FieldTypes_URL();
		$type->setup_field( $field );

		// Execution
		$output = $type->render_field_inner( $field, array( $content, 'Test Link' ), 'anything' );

		// Verification
		$this->assertContains( 'wpbdp-field-' . $field->get_id(), $output );
		$this->assertContains( $content, $output );
	}
}
