<?php
/**
 * @since next-release
 */
class WPBDP__Cron {

    public function __construct() {
        $this->schedule_events();
        $this->maybe_run_events();
    }

    private function schedule_events() {
        if ( ! wp_next_scheduled( 'wpbdp_hourly_events' ) )
            wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'wpbdp_hourly_events' );

        if ( ! wp_next_scheduled( 'wpbdp_daily_events' ) )
            wp_schedule_event( current_time( 'timestamp' ), 'daily', 'wpbdp_daily_events' );
    }

    private function maybe_run_events() {
        global $wpbdp;

        if ( ! $wpbdp->is_debug_on() )
            return;

        // In debugging mode, run events more frequently (5 min) for testing purposes.
        $last_run = (int) get_transient( 'wpbdp-debugging-last-cron-run' );

        if ( $last_run )
            return;

        wpbdp_log( 'Running cron...' );
        do_action( 'wpbdp_hourly_events' );
        do_action( 'wpbdp_daily_events' );

        set_transient( 'wpbdp-debugging-last-cron-run', 1, 60 * 5 );
    }

}
