<?php

/**
 * Listing Settings Test
 */
class PaymentSettingsCest {
	
	public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

	public function _after( AcceptanceTester $I ) {
    }

    // tests
	public function tryToTestPaymentSettings( AcceptanceTester $I ) {
		
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_settings&tab=payment' );
		$I->see( 'Directory Settings', 'h1' );
		$I->dontSeeCheckboxIsChecked('#payments-on');
		$I->checkOption('#payments-on');
		$I->see( 'Put payment gateways in test mode?', 'label' );
		$I->click('Save Changes');
		$I->reloadPage();
		$I->seeCheckboxIsChecked('#payments-on');
    }
}