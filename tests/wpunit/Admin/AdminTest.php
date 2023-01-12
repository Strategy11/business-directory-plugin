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

	public function testAdminMenuCombine() {
		$this->tester->wantToTest( 'Admin Menu Combine' );

		$instance = new WPBDP_Admin();

		$user_id = $factory->user->create(
			[
				'role' => 'administrator',
			]
		);

		wp_set_current_user( $user_id );
		$instance->admin_menu_combine();

		global $submenu;
		$menu_id = $instance->get_menu_id();

		$this->assertNotFalse( $submenu[ $menu_id ] );
	}

	public function testAdminMenuCombineNoUser() {
		$this->tester->wantToTest( 'Admin Menu Combine No User' );

		$instance = new WPBDP_Admin();

		$instance->admin_menu_combine();

		global $submenu;
		$menu_id = $instance->get_menu_id();

		$this->assertFalse( $submenu[ $menu_id ] );
	}
}
