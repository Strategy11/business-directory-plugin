<?php


class SettingTabsCest {
	
    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after( AcceptanceTester $I ) {
    }

    // tests
    public function tryToTestSettings( AcceptanceTester $I ) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings' );
        $I->see( 'Directory Settings' );
		$I->click( 'Listings' );
    }
}