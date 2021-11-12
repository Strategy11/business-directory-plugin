<?php
/**
 * @package WPBDP\Admin\Upgrades\Migrations
 */

/**
 * Migration for DB version 18.5
 */
class WPBDP__Migrations__18_5 extends WPBDP__Migration {

	/**
	 * If payments were on, we disable all free plans and vice versa.
	 * We scan all enabled plans first for this migration.
	 * If payments was enabled, we disable the free plans.
	 * If payments was disabled, we disabled the paid plans.
	 *
	 * @since x.x
	 */
	public function migrate() {
		global $wpdb;
		$payments_on = wpbdp_get_option( 'payments-on' );
		$sql         = "SELECT p.id, p.amount FROM {$wpdb->prefix}wpbdp_plans p WHERE p.enabled != 0";
		$plans       = $wpdb->get_results( $sql );
		$to_disable  = array();

		if ( ! $plans ) {
			return;
		}

		foreach ( $plans as $plan ) {
			if ( $payments_on && $plan->amount <= 0.0 ) {
				$to_disable[] = $plan->id;
			} elseif ( ! $payments_on && $plan->amount > 0.0 ) {
				$to_disable[] = $plan->id;
			}
		}

		if ( empty( $to_disable ) ) {
			return;
		}

		$sql = "UPDATE {$wpdb->prefix}wpbdp_plans p SET p.enabled = 0 WHERE p.id IN(" . implode( ', ', array_fill( 0, count( $to_disable ), '%d' ) ) . ')';
		// Call $wpdb->prepare passing the values of the array as separate arguments.
		$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $to_disable ) );
		$wpdb->query( $query );
	}
}
