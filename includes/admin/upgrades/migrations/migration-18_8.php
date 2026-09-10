<?php
/**
 * @package WPBDP\Admin\Upgrades\Migrations
 */

/**
 * Migration for DB version 18.8
 */
class WPBDP__Migrations__18_8 extends WPBDP__Migration {

	/**
	 * Stop leftover weekly tracking cron events and hide the unused tracking pointer.
	 *
	 * @since x.x
	 */
	public function migrate() {
		wp_clear_scheduled_hook( 'wpbdp_site_tracking' );
		delete_option( 'wpbdp-show-tracking-pointer' );
	}
}
