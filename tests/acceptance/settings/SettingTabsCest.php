<?php

/**
 * Settings tab click actions
 */
class SettingTabsCest {

	public function _before( AcceptanceTester $I ) {
		$I->wantTo( 'log in to site' );
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings' );
	}

	public function _after( AcceptanceTester $I ) {
	}

	// tests
	public function tryToTestSettings( AcceptanceTester $I ) {
		$I->wantTo( 'Test Directory Settings' );
		$I->see( 'Directory Settings', 'h1' );
		$I->click( 'Listings' );
		$I->seeCheckboxIsChecked( '#wpbdp-settings-listing-renewal' );
		$I->dontSeeCheckboxIsChecked( '#listing-link-in-new-tab' );
		$I->checkOption( '#listing-link-in-new-tab' );
		$I->click( 'Save Changes' );
		$I->reloadPage();
		$I->seeCheckboxIsChecked( '#listing-link-in-new-tab' );
		$I->uncheckOption( '#listing-link-in-new-tab' );
		$I->click( 'Save Changes' );
		$I->see( 'Settings saved.' );
		$I->dontSeeCheckboxIsChecked( '#listing-link-in-new-tab' );
	}
}
