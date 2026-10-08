<?php
/**
 * @package WPBDP\Admin\Upgrades\Migrations
 */

/**
 * Migration for DB version 18.9
 */
class WPBDP__Migrations__18_9 extends WPBDP__Migration {

	/**
	 * Mark listings that expired before 6.4.28 as expired from publish so owners can renew them.
	 *
	 * @since x.x
	 */
	public function migrate() {
		global $wpdb;

		$listing_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				JOIN {$wpdb->prefix}wpbdp_listings l ON l.listing_id = p.ID
				LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = %s AND l.listing_status IN (%s, %s) AND m.meta_id IS NULL",
				'_wpbdp_expired_from_publish',
				WPBDP_POST_TYPE,
				'draft',
				'expired',
				'pending_renewal'
			)
		);

		foreach ( $listing_ids as $listing_id ) {
			add_post_meta( $listing_id, '_wpbdp_expired_from_publish', 1, true );
		}
	}
}
