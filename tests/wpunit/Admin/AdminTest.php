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
	private static $instance;

	/**
	 * User id.
	 *
	 * @var int
	 */
	protected static $user_id;

	public static function set_up_before_class( $factory ) {
		self::$user_id = $factory->user->create(
			[
				'role' => 'administrator',
			]
		);

		static::$instance = new WPBDP_Admin();
	}

	public static function tear_down_after_class() {
		parent::tear_down_after_class();

		static::$instance = null;
	}

	public function testAdminMenuCombine() {
		$this->tester->wantToTest( 'Admin Menu Combine' );

		wp_set_current_user( self::$user_id );

		$this->instance->admin_menu_combine();

		global $submenu;
		$menu_id = $this->instance->get_menu_id();

		$this->assertNotFalse( $submenu[ $menu_id ] );
	}

	public function testAdminMenuCombineNoUser() {
		$this->tester->wantToTest( 'Admin Menu Combine No User' );

		$this->instance->admin_menu_combine();

		global $submenu;
		$menu_id = $this->instance->get_menu_id();

		$this->assertFalse( $submenu[ $menu_id ] );
	}
}
