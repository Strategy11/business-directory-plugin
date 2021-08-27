<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Image;

class ImageTest extends TestCase {


	/**
	 * Test that correct image tags and data is returned from the image
	 */
	public function test_render_html_value() {
		$title = 'Sample Image Field';

		$post_title = 'Sample Listing';
		$listing_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);

		$listing = wpbdp_get_listing( $listing_id );

		$field = Phake::mock( 'WPBDP_Form_Field' );

		$type = new WPBDP_FieldTypes_Image();

		// Execution
		$output = $type->render_field_inner( $field, array( 1, $title ), 'submit', $listing );

		// Verification
		$this->assertContains( $title, $output );
		$this->assertContains( 'wpbdp-image-img', $output );
	}
}
