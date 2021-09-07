<?php

/**
 * Email Settings Test
 */
class EmailSettingsCest {
	
    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after( AcceptanceTester $I ) {
    }

    // tests
    public function tryToTestEmailSettings( AcceptanceTester $I ) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings&tab=email' );
        $I->see( 'Directory Settings', 'h1' );
    }
}