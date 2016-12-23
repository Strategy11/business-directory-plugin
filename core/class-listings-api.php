<?php

if ( ! class_exists( 'WPBDP_Listings_API' ) ) {

require_once( WPBDP_PATH . 'core/class-listing-expiration.php' );


/**
 * @since 3.5.4
 */
class WPBDP_Listings_API {

    public function __construct() {
        // add_action( 'WPBDP_Listing::listing_created', array( &$this, 'new_listing_admin_email' ) );
        // add_action( 'WPBDP_Listing::listing_created', array( &$this, 'new_listing_confirmation_email' ) );
        // add_action( 'wpbdp_edit_listing', array( &$this, 'edit_listing_admin_email' ) );

        add_action( 'WPBDP_Payment::status_change', array( &$this, 'setup_listing_after_payment' ) );
        // FIXME: review before next-release.
        // add_action( 'WPBDP_Payment::status_change', array( &$this, 'auto_renewal_notification_email' ) );

        // add_action( 'transition_post_status', array( &$this, 'listing_published_notification' ), 10, 3 );

        $this->expiration = new WPBDP__Listing_Expiration();
    }

    /**
     * @since 3.4
     */
    public function setup_listing_after_payment( &$payment ) {
        $listing = $payment->get_listing();

        if ( ! $listing || ! $payment->is_completed() )
            return;

        $is_renewal = false;

        foreach ( $payment->payment_items as $item ) {
            switch ( $item['type'] ) {
                case 'recurring_plan':
                case 'plan':
                case 'plan_renewal':
                    $listing->set_fee_plan( $item['fee_id'], 'recurring_plan' == $item['type'] ? true : false );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'recurring_fee':
                    $listing->set_fee_plan( $item->rel_id_2, true );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'fee':
                    // This item type is no longer used as of next-release, but we have this for backwards-compat.
                    if ( ! $listing->is_recurring() )
                        $listing->set_fee_plan( $item->rel_id_2 );
                    break;
                case 'featured_upgrade':
                    global $wpdb;
                    $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'is_sticky' => 1 ), array( 'listing_id' => $listing->get_id() ) );
                    break;
            }
        }

        if ( $is_renewal )
            $listing->set_post_status( 'publish' );
    }

    /**
     * @since 3.5.2
     */
    public function auto_renewal_notification_email( &$payment ) {
        if ( ! $payment->is_completed() || ! $payment->has_item_type( 'recurring_fee' ) )
            return;

        if ( ! $payment->get_data( 'parent_payment_id' ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $recurring_item = $payment->get_recurring_item();

        $replacements = array();
        $replacements['listing'] = sprintf( '<a href="%s">%s</a>',
                                            get_permalink( $payment->get_listing_id() ),
                                            get_the_title( $payment->get_listing_id() ) );
        $replacements['author'] = get_the_author_meta( 'display_name', get_post( $payment->get_listing_id() )->post_author );
        $replacements['category'] = wpbdp_get_term_name( $recurring_item->rel_id_1 );
        $replacements['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                                           strtotime( $payment->get_processed_on() ) );
        $replacements['site'] = sprintf( '<a href="%s">%s</a>',
                                         get_bloginfo( 'url' ),
                                         get_bloginfo( 'name' ) );

        $email = wpbdp_email_from_template( 'listing-autorenewal-message', $replacements );
        $email->to[] = wpbusdirman_get_the_business_email( $payment->get_listing_id() );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

    function listing_published_notification( $new_status, $old_status, $post ) {
        if ( ! in_array( 'listing-published', wpbdp_get_option( 'user-notifications' ), true ) )
            return;

        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return;

        if ( $new_status == $old_status || 'publish' != $new_status || ( 'pending' != $old_status && 'draft' != $old_status ) )
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

    public function new_listing_confirmation_email( &$listing ) {
        if ( ! in_array( 'new-listing', wpbdp_get_option( 'user-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = wpbdp_email_from_template( 'email-confirmation-message', array(
            'listing' => $listing->get_title()
        ) );
        $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

   public function new_listing_admin_email( &$listing ) {
        if ( ! in_array( 'new-listing', wpbdp_get_option( 'admin-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] New listing notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );

        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

        $email->body = wpbdp_render( 'email/listing-added', array( 'listing' => $listing ), false );
        $email->send();
    }

   public function edit_listing_admin_email( &$listing ) {
        if ( ! in_array( 'listing-edit', wpbdp_get_option( 'admin-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] Listing edit notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );

        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

        $email->body = wpbdp_render( 'email/listing-edited', array( 'listing' => $listing ), false );

        $email->send();
    }

    /**
     * @since 3.4.1
     */
    public function calculate_sequence_id( $listing_id ) {
        $sequence_id = get_post_meta( $listing_id, '_wpbdp[import_sequence_id]', true );

        if ( ! $sequence_id ) {
            global $wpdb;

            $candidate = intval( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(meta_value AS UNSIGNED INTEGER )) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                                                                 '_wpbdp[import_sequence_id]' ) ) );
            $candidate++;

            if ( false == add_post_meta( $listing_id, '_wpbdp[import_sequence_id]', $candidate, true ) )
                $sequence_id = 0;
            else
                $sequence_id = $candidate;
        }

        return $sequence_id;
    }

    public function get_listing_fees($listing_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id));
    }

    /*
     * Featured listings.
     */

    public function get_sticky_status( $listing_id ) {
        $listing = WPBDP_Listing::get( $listing_id );

        if ( ! $listing )
            return 'normal';

        return $listing->get_sticky_status( false );
    }

    // {{{ Quick search.

    private function get_quick_search_fields() {
        $fields = array();

        foreach ( wpbdp_get_option( 'quick-search-fields', array() ) as $field_id ) {
            if ( $field = WPBDP_FormField::get( $field_id ) )
                $fields[] = $field;
        }

        if ( ! $fields ) {
            // Use default fields.
            foreach( wpbdp_get_form_fields() as $field ) {
                if ( in_array( $field->get_association(), array( 'title', 'excerpt', 'content' ) ) )
                    $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Performs a "quick search" for listings on the fields marked as quick-search fields in the plugin settings page.
     * @uses WPBDP_ListingsAPI::get_quick_search_fields().
     * @param string $keywords The string used for searching.
     * @param mixed $location Location information.
     * @return array The listing IDs.
     * @since 3.4
     */
    public function quick_search( $keywords, $location = false ) {
        $keywords = trim( $keywords );

        if ( ! $keywords && ! $location )
            return array();

        require_once( WPBDP_PATH . 'core/helpers/class-search-helper.php' );

        $args = array( 'query' => $keywords,
                       'mode' => 'quick-search',
                       'location' => $location,
                       'fields' => $this->get_quick_search_fields() );

        $helper = new WPBDP__Search_Helper( $args );
        return $helper->get_posts();
    }

    // }}}

}

}

