<?php

/**
 * Crude test for creating directories
 */
class DirectoryCrudCest {

	private $listing_title = '';

	private $slug = '';

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
		$I->click( 'Publish' );
		$I->see( 'Post published.', 'p' );
		$I->click( array( 'link' => 'View post' ) );
		$I->seeInTitle( $this->listing_title );
	}


	public function viewDirectory( AcceptanceTester $I ) {
		$I->amOnPage( '/business-directory/' . $this->slug );
		$I->seeInTitle( $this->listing_title );
	}
}
