<?php
/**
 * Includes tests for the Admin.
 */

namespace Admin;

use WPBDP__CPT_Integration;
use WPBDP_Admin;
use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;

/**
 * Tests for the Admin.
 *
 * @since x.x
 */
class AdminTest extends WPUnitTestCase {

	/**
	 * WpunitTester instance.
	 *
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * WPBDP_Admin instance.
	 *
	 * @var \WPBDP_Admin
	 */
	private static $admin;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_user_id;

	/**
	 * Editor user ID.
	 *
	 * @var int
	 */
	protected static $editor_user_id;

	/**
	 * WPBDP menu ID.
	 *
	 * @var string
	 */
	protected static $menu_id;

	public static function wpSetUpBeforeClass( $factory ) {
		static::$admin_user_id = $factory->user->create( [ 'role' => 'administrator' ] );
		static::$editor_user_id = $factory->user->create( [ 'role' => 'editor' ] );

		if ( ! class_exists( 'WPBDP_Admin' ) ) {
			require_once WPBDP_INC . 'admin/class-admin.php';
		}

		static::$admin = new WPBDP_Admin();
		static::$menu_id = static::$admin->get_menu_id();
	}

	public static function set_up() {
		parent::set_up();

		global $wp_actions;
		remove_all_actions( 'init' );
		remove_all_actions( 'admin_menu' );
		remove_all_actions( 'admin_head' );
		$wp_actions = [];
	}

	public function testMenuExistsAsAdmin() {
		$this->tester->wantToTest( 'Admin Menu Exists As Admin' );
		$this->wpbdp_admin_menu_exists( static::$admin_user_id );
	}

	public function testMenuExistsAsEditor() {
		$this->tester->wantToTest( 'Admin Menu Exists For Editor User' );
		$this->wpbdp_admin_menu_exists( static::$editor_user_id );
	}

	public function testListingPostTypeDoesNotExistAtTopLevelAsAdmin() {
		$this->tester->wantToTest( 'Listing Post Type Does Not Exist At Top Level As Admin' );
		$this->wpbdp_listing_post_type_at_top_level_exists( static::$admin_user_id );
	}

	public function testSubmenusExistAsAdmin() {
		global $submenu;
		$original_submenu = $submenu;

		wp_set_current_user( static::$admin_user_id );

		do_action( 'init' );
		do_action( 'admin_menu' );
		do_action( 'admin_head' );

		$this->assertEquals( 'wpbdp_settings', $submenu[ static::$menu_id ][2][2] );
		$this->assertEquals( 'wpbdp_admin_payments', $submenu[ static::$menu_id ][5][2] );
		$this->assertEquals( 'wpbdp-addons', $submenu[ static::$menu_id ][8][2] );
		// $this->assertEquals( 'wpbdp-themes', $submenu[ static::$menu_id ] );

		$submenu = $original_submenu;
	}

	protected function wpbdp_admin_menu_exists( $user_id ) {
		global $submenu;
		$original_submenu = $submenu;

		wp_set_current_user( $user_id );

		do_action( 'admin_menu' );
		do_action( 'admin_head' );

		$this->assertArrayHasKey( static::$menu_id, $submenu );

		$submenu = $original_submenu;
	}

	protected function wpbdp_listing_post_type_at_top_level_exists( $user_id ) {
		global $submenu;
		global $menu;

		wp_set_current_user( $user_id );

		do_action( 'init' );
		do_action( 'admin_menu' );
		do_action( 'admin_head' );
	}
}
