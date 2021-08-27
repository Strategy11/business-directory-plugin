<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Phone_Number;

class PhoneNumberTest extends TestCase {


	public function test_render_html_field() {

		$post_title = 'Sample Listing';
		$listing_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $post_title,
			)
		);

		$sample_number = '000-0000-0000';

		$field = Phake::mock( 'WPBDP_Form_Field' );
		Phake::when( $field )->get_association->thenReturn( 'meta' );

		update_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', $sample_number );

		$type = new WPBDP_FieldTypes_Phone_Number();

		// Execution
		$output = $type->get_field_html_value( $field, $listing_id );

		// Verification
		$this->assertEquals( '<a href="tel:' . esc_attr( $sample_number ) . '">' . esc_html( $sample_number ) . '</a>', $output );
	}
}
