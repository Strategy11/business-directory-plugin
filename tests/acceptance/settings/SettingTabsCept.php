<?php

/**
 * Settings tab click actions
 */
class SettingTabsCest {
	
    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings' );
    }

    public function _after( AcceptanceTester $I ) {
    }

    // tests
    public function tryToTestSettings( AcceptanceTester $I ) {
        $I->see( 'Directory Settings', 'h1' );
		$I->click( 'Listings' );
		$I->seeCheckboxIsChecked('#wpbdp-settings-listing-renewal');
		$I->dontSeeCheckboxIsChecked('#listing-link-in-new-tab');
		$I->checkOption('#listing-link-in-new-tab');
		$I->click('Save Changes');
		$I->reloadPage();
		$I->seeCheckboxIsChecked('#listing-link-in-new-tab');
		$I->uncheckOption('#listing-link-in-new-tab');
		$I->click('Save Changes');
		$I->see('Settings saved.');
		$I->dontSeeCheckboxIsChecked('#listing-link-in-new-tab');
    }

	public function tryToTestSettingsSearchTab( AcceptanceTester $I ) {
		$I->click('Searching');
		$I->see( 'Display advanced search form', 'label' );
	}

	public function tryToTestSettingsCateoryTab( AcceptanceTester $I ) {
		$I->click('Categories');
		$I->see( 'Show listings under categories on main page?', 'label' );
	}

	public function tryToTestSettingsContactTab( AcceptanceTester $I ) {
		$I->click('Contact Form');
		$I->see( 'Include listing contact form on listing pages?', 'label' );
	}

	public function tryToTestSettingsButtonsTab( AcceptanceTester $I ) {
		$I->click('Buttons');
		$I->see( 'Include button to report listings?', 'label' );
	}

	public function tryToTestSettingsSortingTab( AcceptanceTester $I ) {
		$I->click('Sorting');
		$I->see( 'Order directory listings by', 'label' );
	}
}