<?php


class LicenseKeyCest {

	public function _before( AcceptanceTester $I ) {
		$I->wantTo( 'log in to site' );
		$I->loginAsAdmin();
	}

	public function _after( AcceptanceTester $I ) {
	}

	// tests
	public function tryToTestLicenseKey( AcceptanceTester $I ) {
		$I->wantTo( 'Test License Key Settings' );
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings' );
		// Test that its the free version
		$I->see( 'Your license key provides access to new features and updates.', 'p' );
	}
}
