<?php


class LicenseKeyCest {
	
    public function _before(AcceptanceTester $I) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after(AcceptanceTester $I) {
    }

    // tests
    public function tryToTestLicenseKey(AcceptanceTester $I) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings' );
		$I->fillField( 'Enter License Key here', '123456789' );
		$I->click( '.wpbdp-license-key-activate-btn' );
    }
}