<?php

/**
 * Check that all plugin menus are active
 */
class PluginMenuCest {

    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/index.php' );
    }

    public function tryToTestMenusExist( AcceptanceTester $I ) {
        $I->wantTo('Click the Business Directory plugin menu');
        $I->click( array( 'link' => 'Directory') );
		$I->see( 'Directory', 'h1' );
        $I->see( 'Directory Listings', 'a' );
        $I->see( 'Add New Listing', 'a' );
        $I->see( 'Directory Categories', 'a' );
        $I->see( 'Directory Tags', 'a' );
        $I->see( 'Settings', 'a' );
        $I->see( 'Fee Plans', 'a' );
        $I->see( 'Payment History', 'a' );
        $I->see( 'Import & Export', 'a' );
        $I->see( 'Modules', 'a' );
        $I->see( 'Themes', 'a' );
        $I->see( 'SMTP', 'a' );
        $I->click( array( 'link' => 'Directory Listings') );
        $I->see( 'Directory', 'h1' );
        $I->click( array( 'link' => 'Add New Listing') );
        $I->see( 'Add New Listing', 'h1' );
        $I->click( array( 'link' => 'Directory Categories') );
        $I->see( 'Directory Categories', 'h1' );
        $I->click( array( 'link' => 'Directory Tags') );
        $I->see( 'Directory Tags', 'h1' );
        $I->click( array( 'link' => 'Settings') );
        $I->see( 'Directory Settings', 'h1' );
        $I->click( array( 'link' => 'Fee Plans') );
        $I->see( 'Fee Plans', 'h1' );
        $I->click( array( 'link' => 'Payment History') );
        $I->see( 'Payment History', 'h1' );
        $I->click( array( 'link' => 'Import & Export') );
        $I->see( 'Import & Export', 'h1' );
        $I->click( array( 'link' => 'Modules') );
        $I->wait( 10 ); // wait for 10 secs to load
        $I->see( 'Directory Modules', 'h1' );
        $I->click( array( 'link' => 'Themes') );
        $I->see( 'Directory Themes', 'h1' );
        $I->click( array( 'link' => 'SMTP') );
        $I->see( 'Making Email Deliverability Easy for WordPress', 'h1' );
    }
}