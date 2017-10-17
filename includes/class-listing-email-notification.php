<?php
/**
 * @since 5.0
 */
class WPBDP__Listing_Email_Notification {

    public function __construct() {
        add_action( 'transition_post_status', array( $this, 'listing_published_notification' ), 10, 3 );
        add_action( 'wpbdp_listing_status_change', array( $this, 'status_change_notifications' ), 10, 3 );
        add_action( 'wpbdp_edit_listing', array( $this, 'edit_listing_admin_email' ) );

        add_action( 'wpbdp_listing_maybe_send_notices', array( $this, 'send_notices' ), 10, 3 );
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
        if ( 'expired' == $new_status ) {
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

            $email = wpbdp_email_from_template(
                $notice,
                array(
                    'site'       => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
                    'author'     => $listing->get_author_meta( 'display_name' ),
                    'listing'    => sprintf( '<a href="%s">%s</a>', $listing->get_permalink(), esc_attr( $listing->get_title() ) ),
                    'expiration' => date_i18n( get_option( 'date_format' ), strtotime( $listing->get_expiration_date() ) ),
                    'link'       => sprintf( '<a href="%1$s">%1$s</a>', $listing->get_renewal_url() ),
                    'category'   => get_the_term_list( $listing->get_id(), WPBDP_CATEGORY_TAX, '', ', ' )
            ) );

            $email->template = 'businessdirectory-email';
            $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );

            if ( in_array( 'renewal', wpbdp_get_option( 'admin-notifications' ), true ) ) {
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

}
