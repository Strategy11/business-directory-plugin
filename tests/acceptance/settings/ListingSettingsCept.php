<?php

/**
 * Listing Settings Test
 */
class ListingSettingsCest {
	
    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after( AcceptanceTester $I ) {
    }

    // tests
    public function tryToTestListingSettings( AcceptanceTester $I ) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings&tab=listings' );
        $I->see( 'Directory Settings', 'h1' );
    }
}