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
		$wpbdp->formfields->create_default_fields();
		$wpbdp->settings->set_new_install_settings();
		$this->maybe_create_default_fee();
	}


	/**
	 * Create an administrator, editor, and subscriber
	 */
	private function create_users() {
		$has_user = get_user_by( 'email', 'admin@mail.com' );
		if ( ! empty( $has_user ) ) {
			return;
		}

		$admin_args = array(
			'user_login' => 'admin',
			'user_email' => 'admin@mail.com',
			'user_pass'  => 'admin',
			'role'       => 'administrator',
		);
		$admin      = $this->factory->user->create_object( $admin_args );
		$this->assertNotEmpty( $admin );

		$editor_args = array(
			'user_login' => 'editor',
			'user_email' => 'editor@mail.com',
			'user_pass'  => 'editor',
			'role'       => 'editor',
		);
		$editor      = $this->factory->user->create_object( $editor_args );
		$this->assertNotEmpty( $editor );

		$subscriber_args = array(
			'user_login' => 'subscriber',
			'user_email' => 'subscriber@mail.com',
			'user_pass'  => 'subscriber',
			'role'       => 'subscriber',
		);
		$subscriber      = $this->factory->user->create_object( $subscriber_args );
		$this->assertNotEmpty( $subscriber );
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
					'enabled'              => 1,
					'tag'                  => 'free',
				)
			);
			$fee->save();
		}
	}
}
