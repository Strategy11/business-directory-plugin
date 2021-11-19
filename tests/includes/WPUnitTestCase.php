<?php

namespace WPBDP\Tests;

use Codeception\TestCase\WPTestCase;
use WPBDP_Installer;

class WPUnitTestCase extends WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp() : void {
		parent::setUp();
		$this->install();
		$this->after_setup();
	}

	public static function tearDownAfterClass() : void {
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

	/**
	 * Install data required for tests
	 */
	private function install() {
		$free_plan = wpbdp_get_fee_plan( 'free' );
		if ( ! $free_plan ) {
			$fee = new WPBDP__Fee_Plan(
				array(
					'label' 	=> 'Free Listing',
					'amount'	=> 0.0,
					'days'		=> absint( wpbdp_get_option( 'listing-duration' ) ),
					'sticky'	=> 0,
					'recurring'	=> 0,
					'images'    => absint( wpbdp_get_option( 'free-images' ) ),
					'supported_categories' => 'all',
					'pricing_model' => 'flat',
					'enabled' => 1,
					'tag' => 'free',
				)
			);
			$fee->save();
		}
	}
}