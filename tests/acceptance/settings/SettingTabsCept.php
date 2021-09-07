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
        $I->see( 'Directory Settings', 'h1' );
		$I->click( 'Listings' );
		$I->seeCheckboxIsChecked('#wpbdp-settings-listing-renewal');
		$I->dontSeeCheckboxIsChecked('#listing-link-in-new-tab');
		$I->checkOption('#listing-link-in-new-tab');
		$I->click('Save Changes');
		$I->seeCheckboxIsChecked('#listing-link-in-new-tab');
		$I->uncheckOption('#listing-link-in-new-tab');
		$I->click('Save Changes');
		$I->dontSeeCheckboxIsChecked('#listing-link-in-new-tab');
    }
}