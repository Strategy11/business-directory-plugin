<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_LinkedIn;

class LinkedInTest extends TestCase {

	/**
	 * Test to check if the LinkedIn render is valid
	 * This checks to ensure the html share is returned
	 */
	public function test_render_html_value() {
		$post_title = 'Sample Listing';
		$listing_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $post_title,
			)
		);

		$this->assertTrue( is_int( $listing_id ) );

		$field = Phake::mock( 'WPBDP_Form_Field' );
		Phake::when( $field )->value( $listing_id )->thenReturn( $listing_id );

		$type = new WPBDP_FieldTypes_LinkedIn();

		// Execution
		$output = $type->get_field_html_value( $field, $listing_id );

		// Verification
		$this->assertContains( "data-id=\"$listing_id\"", $output );
	}
}
