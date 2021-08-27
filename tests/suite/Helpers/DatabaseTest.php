<?php

namespace WPBDP\Tests\Plugin\Helpers;

use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP_Database_Helper;

class DatabaseTest extends TestCase {

	public function test_get_collate_never_returns_an_empty_value() {
		$db = Mockery::mock();

		$db->charset = 'utf8';
		$db->collate = '';

		$database_helper = new WPBDP_Database_Helper( $db );

		// Execution
		$collate = $database_helper->get_collate();

		// Verification
		$this->assertNotEmpty( $collate );
		$this->assertEquals( 'utf8_general_ci', $collate );
	}

	public function test_replace_charset_and_collate() {
		$sql = 'CHARACTER SET <charset> COLLATE <collate>';

		$db = Mockery::mock();

		$db->charset = 'utf8';
		$db->collate = 'collate';

		$database_helper = new WPBDP_Database_Helper( $db );

		// Execution
		$collate = $database_helper->replace_charset_and_collate( $sql );

		// Verification
		$this->assertEquals( 'CHARACTER SET utf8 COLLATE collate', $collate );
	}

	public function test_replace_charset_and_collate_when_charset_is_empty() {
		$sql = 'COLLATE <collate>';

		$db          = Mockery::mock();
		$db->charset = '';

		$database_helper = new WPBDP_Database_Helper( $db );

		// Execution
		$collate = $database_helper->replace_charset_and_collate( $sql );

		// Verification
		$this->assertEquals( 'COLLATE utf8_general_ci', $collate );
	}

	public function test_replace_charset_and_collate_when_db_collate_is_empty() {
		$sql = 'COLLATE <collate>';

		$db          = Mockery::mock();
		$db->charset = 'utf8';
		$db->collate = '';

		$database_helper = new WPBDP_Database_Helper( $db );

		// Execution
		$collate = $database_helper->replace_charset_and_collate( $sql );

		// Verification
		$this->assertEquals( 'COLLATE utf8_general_ci', $collate );
	}
}

