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

        global $wpbdp;

        $notices = array();

        if ( ( $th = absint( wpbdp_get_option( 'renewal-email-threshold', 5 ) ) ) > 0 )
            $notices[ '+' . $th . ' days' ] = 'future';

        if ( wpbdp_get_option( 'renewal-reminder' ) && ( $th = absint( wpbdp_get_option( 'renewal-reminder-threshold', 5 ) ) ) > 0 )
            $notices[ '-' . $th . ' days' ] = 'reminder';

        foreach ( $notices as $notice_period => $notice_kind ) {
            $listings = $this->get_expiring_listings( $notice_period );

            foreach ( $listings as $listing_id ) {
                $listing = wpbdp_get_listing( $listing_id );
                do_action( 'wpbdp_listing_expiration_remind', $notice_kind, $listing );
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

