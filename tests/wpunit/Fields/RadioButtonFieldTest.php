<?php
/**
 * Includes tests for radio button form fields.
 */

namespace Fields;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Form_Field;

/**
 * Tests for radio button form fields.
 */
class RadioButtonFieldTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public function testRadioMetaFieldStoresValidSubmittedOption() {
		$field      = $this->create_radio_field();
		$listing_id = $this->create_listing();

		$_POST['listingfields'][ $field->get_id() ] = 'Farm';
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );
		unset( $_POST['listingfields'] );

		$this->assertEquals( 'Farm', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testRadioMetaFieldRejectsSubmittedOptionNotConfiguredOnField() {
		$field      = $this->create_radio_field();
		$listing_id = $this->create_listing();
		$payload    = '<script>alert(document.domain)</script>';

		$_POST['listingfields'][ $field->get_id() ] = $payload;
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );
		unset( $_POST['listingfields'] );

		$this->assertEquals( '', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testRadioMetaFieldPreservesStoredOptionWhenSubmittedOptionNotConfiguredOnField() {
		$field      = $this->create_radio_field();
		$listing_id = $this->create_listing();
		$payload    = '<script>alert(document.domain)</script>';

		$_POST['listingfields'][ $field->get_id() ] = 'Market';
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );

		$_POST['listingfields'][ $field->get_id() ] = $payload;
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );
		unset( $_POST['listingfields'] );

		$this->assertEquals( 'Market', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', true ) );
		$this->assertEquals( 'Market', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']_selected', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testRadioMetaFieldClearsStoredOptionWhenSubmittedEmpty() {
		$field      = $this->create_radio_field();
		$listing_id = $this->create_listing();

		$_POST['listingfields'][ $field->get_id() ] = 'Market';
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );

		$_POST['listingfields'][ $field->get_id() ] = '';
		$value                                      = $field->value_from_POST();

		$field->store_value( $listing_id, $value );
		unset( $_POST['listingfields'] );

		$this->assertEquals( '', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', true ) );
		$this->assertEquals( '', get_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']_selected', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testRadioMetaFieldEscapesLegacyStoredValueForHtmlDisplay() {
		$field      = $this->create_radio_field();
		$listing_id = $this->create_listing();
		$payload    = '<script>alert(document.domain)</script>';

		update_post_meta( $listing_id, '_wpbdp[fields][' . $field->get_id() . ']', $payload );

		$this->assertEquals( '&lt;script&gt;alert(document.domain)&lt;/script&gt;', $field->html_value( $listing_id ) );
	}

	/**
	 * @since x.x
	 *
	 * @return WPBDP_Form_Field
	 */
	private function create_radio_field() {
		$field = new WPBDP_Form_Field(
			array(
				'association'   => 'meta',
				'field_type'    => 'radio',
				'label'         => 'Business Type',
				'display_flags' => array( 'listing' ),
				'field_data'    => array(
					'options' => array( 'Farm', 'Market', 'Delivery' ),
				),
			)
		);

		$result = $field->save();

		if ( is_wp_error( $result ) ) {
			$this->fail( 'Field creation failed: ' . $result->get_error_message() );
		}

		return $field;
	}

	/**
	 * @since x.x
	 *
	 * @return int
	 */
	private function create_listing() {
		$listing_id = wp_insert_post(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Radio field test listing',
			)
		);

		$this->assertTrue( is_int( $listing_id ) );

		return $listing_id;
	}
}
