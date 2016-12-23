<?php
/**
 * @since next-release
 */
class WPBDP__Listing_Expiration {

    function __construct() {
        add_action( 'wpbdp_daily_events', array( $this, 'check_for_expired_listings' ) );
        add_action( 'wpbdp_hourly_events', array( $this, 'send_expiration_reminders' ) );
    }

    function check_for_expired_listings() {
        global $wpdb;

        $listings  = $wpdb->get_col( wpbdp_debug_e( $wpdb->prepare(
            "SELECT listing_id FROM {$wpdb->prefix}wpbdp_listings WHERE expiration_date IS NOT NULL AND expiration_date < %s",
            current_time( 'mysql' ) ) ) );

        foreach ( $listings as $listing_id ) {
            $l = wpbdp_get_listing( $listing_id );

            if ( ! $l || in_array( $l->get_status(), array( 'expired', 'pending_renewal' ) ) )
                continue;

            $l->set_status( 'expired' );
        }
    }

    function send_expiration_reminders() {
        if ( ! wpbdp_get_option( 'listing-renewal' ) )
            return;

        $notices = array();

        if ( ( $th = absint( wpbdp_get_option( 'renewal-email-threshold', 5 ) ) ) > 0 )
            $notices[ '+' . $th . ' days' ] = 'future';

        if ( wpbdp_get_option( 'renewal-reminder' ) && ( $th = absint( wpbdp_get_option( 'renewal-reminder-threshold', 5 ) ) ) > 0 )
            $notices[ '-' . $th . ' days' ] = 'reminder';

        foreach ( $notices as $notice_period => $notice_kind ) {
            $listings = $this->get_expiring_listings( $notice_period );

            foreach ( $listings as $listing_id ) {
                $listing = WPBDP_Listing::get( $listing_id );

                if ( ! $listing )
                    continue;

                // $listing->send_renewal_notice( $notice_kind );
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

