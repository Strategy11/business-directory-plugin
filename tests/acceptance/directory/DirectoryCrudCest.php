<?php

/**
 * Crude test for creating directories
 */
class DirectoryCrudCept {

    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
    }

    public function _after( AcceptanceTester $I ) {

    }

    public function createDirectory( AcceptanceTester $I ) {

    }

    public function editDirectory( AcceptanceTester $I ) {

    }

    public function viewDirectory( AcceptanceTester $I ) {

    }

    public function deleteDirectory( AcceptanceTester $I ) {

    }
}