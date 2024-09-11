<?php

namespace WPBDP\Tests;

use Codeception\TestCase\WPTestCase;
use WPBDP_Installer;
use WPBDP__Fee_Plan;

class WPUnitTestCase extends WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	private $installer = null;

	public function setUp() : void {
		parent::setUp();
		$this->installer = new WPBDP_Installer( 0 );
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

		// Delete listings.
		$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s", WPBDP_POST_TYPE ) );

		foreach ( $post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// Drop tables.
		$installer = new WPBDP_Installer( 0 );
		$tables    = array_keys( $installer->get_database_schema() );
		foreach ( $tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpbdp_{$table}" );
		}
	}

	/**
	 * Install data required for tests
	 */
	private function install() {
		global $wpbdp;
		$this->installer->install();
		$this->create_users();
		$wpbdp->form_fields->create_default_fields();
		$wpbdp->settings->set_new_install_settings();
		$this->maybe_create_default_fee();
		$this->maybe_create_dummy_listings();
	}


	/**
	 * Create an administrator, editor, and subscriber
	 */
	private function create_users() {
		$has_user = get_user_by( 'email', 'test@test.com' );
		if ( ! empty( $has_user ) ) {
			return;
		}

		$user_id = wp_create_user( 'testuser', 'password', 'test@test.com' );
		$this->assertNotEmpty( $user_id );
	}

	/**
	 * Maybe create a default fee plan
	 */
	private function maybe_create_default_fee() {
		$free_plan = wpbdp_get_fee_plan( 'free' );
		if ( ! $free_plan ) {
			$fee = new WPBDP__Fee_Plan(
				array(
					'label'                => 'Free Listing',
					'amount'               => 0.0,
					'days'                 => absint( wpbdp_get_option( 'listing-duration' ) ),
					'sticky'               => 0,
					'recurring'            => 0,
					'images'               => absint( wpbdp_get_option( 'free-images' ) ),
					'supported_categories' => 'all',
					'pricing_model'        => 'flat',
					'enabled'              => true,
					'tag'                  => 'free',
				)
			);
			$fee->save();
		}
	}


	/**
	 * Create dummy listings
	 */
	private function maybe_create_dummy_listings() {
		wpbdp_set_option( 'new-post-status', 'publish' );
		foreach ( range( 0, 10 ) as $number ) {
			wpbdp_save_listing(
				array(
					'post_author' => 1,
					'post_type'   => WPBDP_POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'My listing ' . $number,
				)
			);
		}
		wpbdp_set_option( 'new-post-status', 'pending' );
	}
}
