<?php
/**
 * @since 5.0
 */
class WPBDP__Listing_Email_Notification {

    public function __construct() {
        add_action( 'transition_post_status', array( $this, 'listing_published_notification' ), 10, 3 );
        add_action( 'wpbdp_listing_status_change', array( $this, 'status_change_notifications' ), 10, 3 );
        add_action( 'wpbdp_edit_listing', array( $this, 'edit_listing_admin_email' ) );

        add_action( 'wpbdp_listing_renewed', array( $this, 'listing_renewal_email' ), 10, 3 );

        add_action( 'wpbdp_listing_maybe_send_notices', array( $this, 'send_notices' ), 10, 3 );

        add_action( 'wpbdp_listing_maybe_flagging_notice', array( $this, 'reported_listing_email' ), 10, 2 );
    }

    /**
     * Sent when a listing is published either by the admin or automatically.
     */
    public function listing_published_notification( $new_status, $old_status, $post ) {
        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return;

        if ( $new_status == $old_status || 'publish' != $new_status || ( 'pending' != $old_status && 'draft' != $old_status ) )
            return;

        if ( ! in_array( 'listing-published', wpbdp_get_option( 'user-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = wpbdp_email_from_template( 'email-templates-listing-published', array(
            'listing' => get_the_title( $post->ID ),
            'listing-url' => get_permalink( $post->ID )
        ) );
        $email->to[] = wpbusdirman_get_the_business_email( $post->ID );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

    /**
     * Used to handle notifications related to listing status changes (i.e. expired, etc.)
     */
    public function status_change_notifications( $listing, $old_status, $new_status ) {
        // Expiration notice.
        if ( 'expired' == $new_status && wpbdp_get_option( 'listing-renewal' ) ) {
            $this->send_notices( 'expiration', '0 days', $listing );
        }

        // When a listing is submitted.
        if ( 'incomplete' == $old_status && ( 'complete' == $new_status || 'pending_payment' == $new_status ) ) {
            $this->send_new_listing_email( $listing );
        }
    }

    public function send_notices( $event, $relative_time, $listing, $force_resend = false ) {
        $listing = is_object( $listing ) ? $listing : wpbdp_get_listing( absint( $listing ) );
        if ( ! $listing ) {
            return;
        }

        $post_status = get_post_status( $listing->get_id() );
        if ( ! $post_status || in_array( $post_status, array( 'trash', 'auto-draft' ) ) ) {
            return;
        }

        $all_notices = wpbdp_get_option( 'expiration-notices' );

        foreach ( $all_notices as $notice_key => $notice ) {
            if ( $notice['event'] != $event || $notice['relative_time'] != $relative_time )
                continue;

            if ( ( 'non-recurring' == $notice['listings'] && $listing->is_recurring() ) || ( 'recurring' == $notice['listings'] && ! $listing->is_recurring() ) )
                continue;


            $already_sent = (int) get_post_meta( $listing->get_id(), '_wpbdp_notice_sent_' . $notice_key, true );

            if ( $already_sent && ! $force_resend )
                continue;

            $payments = $listing->get_latest_payments();
            $payment = $payments ? array_shift( $payments ) : array();

            $expiration_date = date_i18n( get_option( 'date_format' ), strtotime( $listing->get_expiration_date() ) );
            $payment_date = date_i18n( get_option( 'date_format' ), $payment ? strtotime( implode( '/', $payment->get_created_at_date() ) ) : time() );

            $email = wpbdp_email_from_template(
                $notice,
                array(
                    'site'          => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
                    'author'        => $listing->get_author_meta( 'display_name' ),
                    'listing'       => sprintf( '<a href="%s">%s</a>', $listing->get_permalink(), esc_attr( $listing->get_title() ) ),
                    'expiration'    => $expiration_date,
                    'link'          => sprintf( '<a href="%1$s">%1$s</a>', $listing->get_renewal_url() ),
                    'category'      => get_the_term_list( $listing->get_id(), WPBDP_CATEGORY_TAX, '', ', ' ),
                    'date'          => $expiration_date,
                    'payment_date'  => $payment_date,
                    'access_key'    => $listing->get_access_key(),
            ) );

            $email->template = 'businessdirectory-email';
            $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );

            if ( 'expiration' == $event && in_array( 'renewal', wpbdp_get_option( 'admin-notifications' ), true ) ) {
                $email->cc[] = get_option( 'admin_email' );

                if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                    $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );
            }

            if ( $email->send() ) {
                // update_post_meta( $listing->get_id(), '_wpbdp_notice_sent_' . $notice_key, current_time( 'timestamp' ) );
            }
        }
    }

    private function send_new_listing_email( $listing ) {
        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        // Notify the admin.
        if ( in_array( 'new-listing', wpbdp_get_option( 'admin-notifications' ), true ) ) {
            $admin_email = new WPBDP_Email();
            $admin_email->subject = sprintf( _x( '[%s] New listing notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
            $admin_email->to[] = get_bloginfo( 'admin_email' );

            if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                $admin_email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

            $admin_email->body = wpbdp_render( 'email/listing-added', array( 'listing' => $listing ), false );
            $admin_email->send();
        }

        // Notify the submitter.
        if ( in_array( 'new-listing', wpbdp_get_option( 'user-notifications' ), true ) ) {
            $email = wpbdp_email_from_template( 'email-confirmation-message', array(
                'listing' => $listing->get_title()
            ) );
            $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );
            $email->template = 'businessdirectory-email';
            $email->send();
        }
    }

    /**
     * Sent when a listing is edited.
     */
    public function edit_listing_admin_email( $listing_id ) {
        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        if ( ! in_array( 'listing-edit', wpbdp_get_option( 'admin-notifications' ), true ) )
            return;

        $listing = wpbdp_get_listing( $listing_id );

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] Listing edit notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );

        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

        $email->body = wpbdp_render( 'email/listing-edited', array( 'listing' => $listing ), false );

        $email->send();
    }

    /**
     * Sent when a listing is renewed.
     * @since 5.0.6
     */
    public function listing_renewal_email( $listing, $payment = false, $context = '' ) {
        // Notify admin.
        if ( in_array( 'after_renewal', wpbdp_get_option( 'admin-notifications' ), true ) ) {
            $email = new WPBDP_Email();
            $email->to[] = get_bloginfo( 'admin_email' );
            $email->subject = sprintf( '[%s] Listing "%s" has renewed', get_bloginfo( 'name' ), $listing->get_title() );

            if ( $cc = wpbdp_get_option( 'admin-notifications-cc' ) ) {
                $email->cc[] = $cc;
            }

            $owner = wpbusdirman_get_the_business_email( $listing->get_id() );
            if ( ! empty( $payment ) ) {
                $amount = $payment->amount;
            } else {
                $plan = $listing->get_fee_plan();
                $amount = $plan->fee_price;
            }

            $amount = wpbdp_currency_format( $amount );

            $email->body = sprintf(
                'The listing "%s" has just renewed for %s from %s.',
                '<a href="' . $listing->get_admin_edit_link() . '">' . $listing->get_title() . '</a>',
                $amount,
                $owner
            );

            $email->send();
        }

        // Notify users.
        do_action( 'wpbdp_listing_maybe_send_notices', 'renewal', '0 days', $listing );
    }

    public function reported_listing_email( $listing, $report ) {
        // Notify the admin.
        if ( in_array( 'flagging_listing', wpbdp_get_option( 'admin-notifications' ), true ) ) {
            $admin_email = new WPBDP_Email();
            $admin_email->subject = sprintf( _x( '[%s] Reported listing notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
            $admin_email->to[] = get_bloginfo( 'admin_email' );

            if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                $admin_email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

            $admin_email->body = wpbdp_render( 'email/listing-reported', array( 'listing' => $listing, 'report' => $report ), false );
            $admin_email->send();
        }
    }

}
