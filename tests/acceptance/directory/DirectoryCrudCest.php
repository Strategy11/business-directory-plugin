<?php

/**
 * Crude test for creating directories
 */
class DirectoryCrudCest {

	private $listing_title = '';

	private $slug = '';

	private $edit_url = '';

	public function _before( AcceptanceTester $I ) {
		$I->wantTo( 'log in to site' );
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=wpbdp_listing' );
		$this->listing_title = 'Sample Test Listing';
		$this->slug = 'sample-test-listing';
	}

	public function _after( AcceptanceTester $I ) {

	}

	public function createDirectory( AcceptanceTester $I ) {
		$I->amGoingTo( 'Create a new listing' );
		$I->click( 'Add New Listing' );
		$I->fillField( 'post_title', $this->listing_title );
		// Fields do not have proper names. Very hard to determine

		foreach ( wpbdp_get_form_fields( array( 'association' => 'meta' ) ) as $field ) {
			$field_id = $field->get_id();
			if ( strtolower( $field->get_label() ) === 'email' ) {
				$I->fillField( array( 'name' => 'listingfields[' . $field_id . ']' ), 'jon@example.com' );
			}
		}
		$I->click( 'Publish' );
		$I->see( 'Post published.', 'p' );
		$I->click( array( 'link' => 'View post' ) );
		$I->seeInTitle( $this->listing_title );
		$this->edit_url = $I->getCurrentUrl();
	}

	public function editDirectory( AcceptanceTester $I ) {
		$I->amOnPage( $this->edit_url );
		$I->seeInCurrentUrl( 'edit' );
		$I->see( 'Update', 'input' );
		$I->click( 'Update' );
		$I->see( 'Post updated.', 'p' );
	}

	public function viewDirectory( AcceptanceTester $I ) {
		$I->amOnPage( '/business-directory/' . $this->slug );
		$I->seeInTitle( $this->listing_title );
	}
}
