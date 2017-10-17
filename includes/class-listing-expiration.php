<?php
/**
 * @since 5.0
 */
class WPBDP__Listing_Expiration {

    function __construct() {
        add_action( 'wpbdp_daily_events', array( $this, 'check_for_expired_listings' ) );
        add_action( 'wpbdp_daily_events', array( $this, 'send_expiration_reminders' ) );
    }

    function check_for_expired_listings() {
        global $wpdb;

        $listings  = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->prefix}wpbdp_listings l ON l.listing_id = p.ID WHERE p.post_type = %s AND p.post_status != %s AND l.expiration_date IS NOT NULL AND l.expiration_date < %s AND l.listing_status NOT IN (%s, %s)",
            WPBDP_POST_TYPE,
            'auto-draft',
            current_time( 'mysql' ),
            'expired',
            'pending_renewal'
        ) );

        foreach ( $listings as $listing_id ) {
            $l = wpbdp_get_listing( $listing_id );
            $l->set_status( 'expired' );
        }
    }

    function send_expiration_reminders() {
        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return;

        $notices = wpbdp_get_option( 'expiration-notices', false );
        if ( ! $notices )
            return;

        $notices = wp_list_filter( $notices, array( 'event' => 'expiration' ) );
        $notices = wp_list_filter( $notices, array( 'relative_time' => '0 days' ), 'NOT' );
        $times = array_unique( wp_list_pluck( $notices, 'relative_time' ) );

        foreach ( $times as $t ) {
            $listings = $this->get_expiring_listings( $t );

            foreach ( $listings as $listing_id ) {
                $listing = wpbdp_get_listing( $listing_id );
                do_action( 'wpbdp_listing_maybe_send_notices', 'expiration', $t, $listing );
            }
        }
    }

    function get_expiring_listings( $period = '+1 month' ) {
        global $wpdb;

        $date_a = date( 'Y-m-d H:i:s', strtotime( $period . ' midnight' ) );
        $date_b = date( 'Y-m-d H:i:s', strtotime( $period . 'midnight' ) + DAY_IN_SECONDS );

        $listings  = $wpdb->get_col( $wpdb->prepare(
            "SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings WHERE expiration_date IS NOT NULL AND expiration_date >= %s AND expiration_date < %s",
            $date_a,
            $date_b ) );

        return $listings;
    }

}

