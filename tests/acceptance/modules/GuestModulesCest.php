<?php

/**
 * Appearance Settings Test
 */
class GuestModulesCest {

	public function _before( AcceptanceTester $I ) {
		$I->wantTo( 'log in to site' );
		$I->loginAsAdmin();
	}

	public function _after( AcceptanceTester $I ) {
	}

	// tests
	public function tryToTestModuleGuest( AcceptanceTester $I ) {
		$I->wantTo( 'Test Modules' );
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp-addons' );
		$I->see( 'Directory Modules', 'h1' );
	}
}
