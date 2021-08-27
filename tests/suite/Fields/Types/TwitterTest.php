<?php

namespace WPBDP\Tests\Plugin\Fields\Types;

use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Twitter;

class TwitterTest extends TestCase {


	public function test_render_html_output() {
		$social_handle = 'https://twitter.com/businessdirectory';
		$listing_id    = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Sample Social Listing',
			)
		);

		$this->assertTrue( is_int( $listing_id ) );

		$field = Phake::mock( 'WPBDP_Form_Field' );

		update_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', $social_handle );

		Phake::when( $field )->get_association->thenReturn( 'meta' );
		Phake::when( $field )->value( $listing_id )->thenReturn( $listing_id );

		$type = new WPBDP_FieldTypes_Twitter();

		// Execution
		$output = $type->get_field_value( $field, $listing_id );

		// Verification
		$this->assertContains( 'businessdirectory', $output );
	}
}
