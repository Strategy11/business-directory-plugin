<?php
/**
 * Includes tests for the Admin.
 */

namespace Admin;

use WPBDP_Themes;
use WPBDP_Admin;
use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;

/**
 * Tests for the Admin.
 *
 * @since 6.3.2
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

		set_current_screen( 'index.php' );
		static::$admin = new WPBDP_Admin();
		static::$menu_id = static::$admin->get_menu_id();
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

	public function testListingPostTypeDoesNotExistAtTopLevelAsEditor() {
		$this->tester->wantToTest( 'Listing Post Type Does Not Exist At Top Level As Admin' );
		$this->wpbdp_listing_post_type_at_top_level_exists( static::$editor_user_id );
	}

	// Todo:
	// Find a way to check the custom post type as a top-level menu and write a test for the admin_menu_combine method.
	public function testCombineListingPostTypeAndWPBDP_Admin() {
		$this->tester->wantToTest( 'Combine Listing Post Type And WPBDP_Admin' );
	}

	public function testSubmenusExistAsAdmin() {
		$this->tester->wantToTest( 'Submenus Exist As Admin' );

		global $submenu;
		$original_submenu = $submenu;

		wp_set_current_user( static::$admin_user_id );
		$this->assertTrue( is_admin() );

		$themes = new WPBDP_Themes();
		static::$admin->admin_menu();
		static::$admin->hide_menu();

		$this->assertEquals( 'wpbdp_settings', $submenu[ static::$menu_id ][2][2] );
		$this->assertEquals( 'wpbdp_admin_payments', $submenu[ static::$menu_id ][5][2] );
		$this->assertEquals( 'wpbdp-addons', $submenu[ static::$menu_id ][8][2] );
		$this->assertEquals( 'wpbdp-themes', $submenu[ static::$menu_id ][9][2] );

		$submenu = $original_submenu;
	}

	protected function wpbdp_admin_menu_exists( $user_id ) {
		global $submenu;
		$original_submenu = $submenu;

		wp_set_current_user( $user_id );
		static::$admin->admin_menu();
		static::$admin->hide_menu();

		$this->assertArrayHasKey( static::$menu_id, $submenu );

		$submenu = $original_submenu;
	}

	// Todo:
	// Find a way to check the custom post type as a top-level menu.
	protected function wpbdp_listing_post_type_at_top_level_exists( $user_id ) {
	}
}
