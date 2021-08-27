<?php
/**
 * TestCase for form fields types base class.
 */

namespace WPBDP\Tests\Plugin\Fields\Types;

use Brain\Monkey\Functions;
use Mockery;

use \WPBDP\Tests\TestCase;

use WPBDP_Form_Field_Type;

/**
 * Unit tests for Types class.
 */
class TypeTest extends TestCase {

	/**
	 * Test that required fileds show an asterisk next to the field's label on
	 * Search forms.
	 */
	public function test_render_shows_required_indicator_in_search_form() {
		$field = Mockery::mock( 'WPBDP_FormField' );
		$value = '';

		$form_field_type = new WPBDP_Form_Field_Type();

		$field->shouldReceive( 'get_id' )->andReturn( rand() + 1 );
		$field->shouldReceive( 'get_field_type' )->andReturn( $form_field_type );
		$field->shouldReceive( 'get_css_classes' )->andReturn( array() );
		$field->shouldReceive( 'get_label' )->andReturn( 'Field' );
		$field->shouldReceive( 'has_validator' )
			->once()
			->with( 'required-in-search' )
			->andReturn( 'Field' );

		$field->html_attributes = array();

		Functions\when( 'wpbdp_html_attributes' )->justReturn( '' );
		Functions\when( 'esc_html' )->returnArg();

		// Execution.
		$output = $form_field_type->render_field( $field, $value, 'search' );

		// Verification.
		$this->assertContains( 'wpbdp-form-field-required-indicator', $output );
	}
}

