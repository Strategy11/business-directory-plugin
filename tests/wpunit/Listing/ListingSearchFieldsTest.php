<?php
/**
 * Tests for the fields an advanced search request may filter by.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Listing_Search;
use WPBDP_Form_Field;

/**
 * Advanced search only uses fields that are in the search form or shown on listings.
 */
class ListingSearchFieldsTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';
		wp_set_current_user( 0 );
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function field_provider() {
		return array(
			'search form field'          => array( array( 'search' ), array(), true ),
			'shown on listings only'     => array( array( 'listing' ), array(), true ),
			'shown in excerpts only'     => array( array( 'excerpt' ), array(), true ),
			'shown nowhere'              => array( array(), array(), false ),
			'email field'                => array( array( 'excerpt', 'listing', 'search' ), array( 'email' ), false ),
			'admin-only field'           => array( array( 'listing', 'private' ), array(), false ),
			'admin-only field in search' => array( array( 'search', 'private' ), array(), false ),
		);
	}

	/**
	 * @dataProvider field_provider
	 *
	 * @since x.x
	 *
	 * @param string[] $display_flags Field display flags.
	 * @param string[] $validators    Field validators.
	 * @param bool     $expected      Whether the term is kept for a visitor.
	 */
	public function test_visitor_search_terms( $display_flags, $validators, $expected ) {
		$field = $this->field( $display_flags, $validators );

		$this->assertSame( $expected ? array( 'term' ) : array(), $this->terms( $field, 'term' ) );
	}

	/**
	 * @since x.x
	 */
	public function test_admin_keeps_admin_only_fields_but_not_email() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$private = $this->field( array( 'listing', 'private' ) );
		$email   = $this->field( array( 'excerpt', 'listing', 'search' ), array( 'email' ) );

		$this->assertSame( array( 'term' ), $this->terms( $private, 'term' ) );
		$this->assertSame( array(), $this->terms( $email, 'term' ) );
	}

	/**
	 * @since x.x
	 */
	public function test_unknown_field_is_ignored() {
		$tree = WPBDP__Listing_Search::parse_request( array( 'listingfields' => array( 999999 => 'term' ) ) );

		$this->assertSame( array( 'and' ), $tree );
	}

	/**
	 * @since x.x
	 */
	public function test_ignored_field_does_not_filter_results() {
		$public = $this->field( array( 'search' ) );
		$hidden = $this->field( array() );

		$first  = $this->listing( array( $public->get_id() => 'shared-value', $hidden->get_id() => 'first-only' ) );
		$second = $this->listing( array( $public->get_id() => 'shared-value', $hidden->get_id() => 'second-only' ) );

		$search  = WPBDP__Listing_Search::from_request(
			array(
				'dosrch'        => 1,
				'listingfields' => array(
					$public->get_id() => 'shared-value',
					$hidden->get_id() => 'first-only',
				),
			)
		);
		$results = array_map( 'intval', $search->get_results() );
		sort( $results );

		$this->assertSame( array( $first, $second ), $results );
	}

	/**
	 * @since x.x
	 *
	 * @param string[] $display_flags Field display flags.
	 * @param string[] $validators    Field validators.
	 *
	 * @return WPBDP_Form_Field
	 */
	private function field( $display_flags, $validators = array() ) {
		$field = new WPBDP_Form_Field(
			array(
				'association'   => 'meta',
				'field_type'    => 'textfield',
				'label'         => 'Search test field',
				'display_flags' => $display_flags,
				'validators'    => $validators,
			)
		);
		$field->save();

		return $field;
	}

	/**
	 * @since x.x
	 *
	 * @param WPBDP_Form_Field $field Field to search.
	 * @param string           $term  Search term.
	 *
	 * @return array
	 */
	private function terms( $field, $term ) {
		$search = WPBDP__Listing_Search::from_request(
			array(
				'dosrch'        => 1,
				'listingfields' => array( $field->get_id() => $term ),
			)
		);

		return $search->terms_for_field( $field );
	}

	/**
	 * @since x.x
	 *
	 * @param array $values Field values keyed by field ID.
	 *
	 * @return int
	 */
	private function listing( $values ) {
		$id = wp_insert_post(
			array(
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Search listing ' . wp_generate_password( 6, false ),
			)
		);

		foreach ( $values as $field_id => $value ) {
			update_post_meta( $id, '_wpbdp[fields][' . $field_id . ']', $value );
		}

		return $id;
	}
}
