<?php
/**
 * @package WPBDP\Admin\Upgrades\Migrations
 */

/**
 * Migration for DB version 18.5
 */
class WPBDP__Migrations__18_5 extends WPBDP__Migration {

	/**
	 * Delete the ajax compat plugin if it's installed.
	 *
	 * @since x.x
	 */
	public function migrate() {
        global $wpdb;

        if ( $wpdb->get_col( $wpdb->prepare( "SHOW COLUMNS FROM {$wpdb->prefix}wpbdp_form_fields LIKE %s", 'icon' ) ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}wpbdp_form_fields ADD COLUMN `icon` VARCHAR(100) NULL DEFAULT 'fa|fas fa-archive';" );
        }
            
	}
}
