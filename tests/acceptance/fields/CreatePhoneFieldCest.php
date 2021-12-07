<?php

/**
 * Create Field Test
 */
class CreatePhoneFieldCest {

	public function _before( AcceptanceTester $I ) {
		$I->wantTo( 'log in to site' );
		$I->loginAsAdmin();
	}

	public function _after( AcceptanceTester $I ) {
	}

	// tests
	public function tryToTestCreateField( AcceptanceTester $I ) {
		$I->wantTo( 'Test creating a phone field' );
		$I->amOnPage( '/wp-admin/admin.php?page=wpbdp_admin_formfields' );
		$I->see( 'Form Fields', 'h1' );
		$I->click( array( 'link' => 'Add New Form Field' ) );
		$I->see( 'Add Form Field', 'h1' );
		$I->selectOption( 'field[association]', 'meta' );
		$I->selectOption( 'field[field_type]', 'phone_number' );
		$I->fillField( 'field[label]', 'Sample Phone Field' );
		$I->click( 'Add Field' );
		$I->see( 'Form fields updated.', 'p' );
		$I->see( 'Sample Phone Field', 'a' ); // Field link.
	}
}
