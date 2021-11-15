<?php

namespace WPBDP\Tests;

use WPBDP_Installer;

class WPUnitTestCase extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
		$this->after_setup();
	}

	public static function tearDownAfterClass(): void {
		global $wpdb;
		@$wpdb->check_connection();
		self::before_tear_down();
		self::reset_data();
		parent::tearDownAfterClass();
	}

	/**
	 * Class called after test is set up
	 */
	protected function after_setup() {

	}

	/**
	 * Action called before test is cleaned and completed
	 */
	protected static function before_tear_down() {

	}

	/**
	 * Reset plugin data for each test.
	 * This prevents duplicates and clean tests.
	 */
	private static function reset_data() {
		global $wpdb;
		$installer = new WPBDP_Installer( 0 );

		// Delete listings.
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", WPBDP_POST_TYPE ) );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// Drop tables.
		$tables = array_keys( $installer->get_database_schema() );
		foreach ( $tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpbdp_{$table}" );
		}
	}
}
