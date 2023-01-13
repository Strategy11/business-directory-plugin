<?php
/**
 * Includes tests for the Admin.
 */

namespace Admin;

use WPBDP_Admin;
use WPBDP\Tests\WPUnitTestCase;

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
	 * User ID.
	 *
	 * @var int
	 */
	protected static $user_id;

	public static function wpSetUpBeforeClass( $factory ) {
		static::$user_id = $factory->user->create(
			[
				'role' => 'administrator',
			]
		);

		if ( ! class_exists( 'WPBDP_Admin' ) ) {
			require_once WPBDP_INC . 'admin/class-admin.php';
		}

		static::$admin = new WPBDP_Admin();
	}

	public function testAdminMenuCombineNoUser() {
		$this->tester->wantToTest( 'Admin Menu Combine No User' );

		// Reset $submenu variable.
		global $submenu;
		$submenu   = [];

		wp_delete_user( static::$user_id );
		static::$admin->admin_menu();

		$this->assertEmpty( $submenu );
	}

	public function testAdminMenuCombine() {
		$this->tester->wantToTest( 'Admin Menu Combine' );

		// Reset $submenu variable.
		global $submenu;
		$submenu   = [];

		// Add WPBDP listing post type to $submenu.
		$cpt_menu  = 'edit.php?post_type=' . WPBDP_POST_TYPE;
		$submenu[$cpt_menu] = [
			5 => [
				'Directory Listings',
				'edit_posts',
				'edit.php?post_type=' . WPBDP_POST_TYPE
			]
		];

		wp_set_current_user( static::$user_id );
		static::$admin->admin_menu();

		$menu_id = static::$admin->get_menu_id();

		$this->assertContains( $cpt_menu, $submenu[ $menu_id ][0] );
	}
}
