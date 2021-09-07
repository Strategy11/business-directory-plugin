<?php

/**
 * Crude test for creating directories
 */
class DirectoryCrudCept {

    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=wpbdp_listing' );
    }

    public function _after( AcceptanceTester $I ) {

    }

    public function createDirectory( AcceptanceTester $I ) {
		$I->click( 'Add New Listing' );
		$I->fillField( 'post_title', 'Sample Test Listing' );
		//Fields do not have proper names. Very hard to determine
    }

    public function editDirectory( AcceptanceTester $I ) {

    }

    public function viewDirectory( AcceptanceTester $I ) {

    }

    public function deleteDirectory( AcceptanceTester $I ) {

    }
}