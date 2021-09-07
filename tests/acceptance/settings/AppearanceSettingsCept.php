<?php

/**
 * Appearance Settings Test
 */
class AppearanceSettingsCest {
	
    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after( AcceptanceTester $I ) {
    }

    // tests
    public function tryToTestAppearanceSettings( AcceptanceTester $I ) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings&tab=appearance' );
        $I->see( 'Directory Settings', 'h1' );
        $I->seeCheckboxIsChecked('#themes-button-style');
    }
}