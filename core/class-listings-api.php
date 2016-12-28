<?php

if ( ! class_exists( 'WPBDP_Listings_API' ) ) {

/**
 * @since 3.5.4
 */
class WPBDP_Listings_API {

    public function __construct() {
        add_action( 'WPBDP_Payment::status_change', array( &$this, 'setup_listing_after_payment' ) );
        // FIXME: review before next-release.
        // add_action( 'WPBDP_Payment::status_change', array( &$this, 'auto_renewal_notification_email' ) );
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
                    $listing->set_fee_plan( $item['fee_id'] );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'recurring_fee':
                    $listing->set_fee_plan( $item->rel_id_2 );

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

        if ( 'initial' == $payment->payment_type )
            $listing->set_status( 'complete' );

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

