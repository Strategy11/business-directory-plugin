<?php

if ( ! class_exists( 'WPBDP_Listings_API' ) ) {

/**
 * @since 3.5.4
 */
class WPBDP_Listings_API {

    public function __construct() {
        add_action( 'WPBDP_Payment::status_change', array( &$this, 'setup_listing_after_payment' ) );
    }

    /**
     * @since 3.4
     */
    public function setup_listing_after_payment( &$payment ) {
        $listing = $payment->get_listing();

        if ( ! $listing || ! $payment->is_completed() )
            return;

        $is_renewal = false;
        $recurring_data = array();

        if ( ! empty( $payment->data['subscription_id'] ) ) {
            $recurring_data['subscription_id'] = $payment->data['subscription_id'];
        }

        if ( ! empty( $payment->data['subscription_data'] ) ) {
            $recurring_data['subscription_data'] = $payment->data['subscription_data'];
        }

        // Process items.
        foreach ( $payment->payment_items as $item ) {
            switch ( $item['type'] ) {
                case 'recurring_plan':
                case 'plan':
                case 'plan_renewal':
                    $listing->set_fee_plan( $item['fee_id'], $recurring_data );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'recurring_fee':
                    $listing->set_fee_plan( $item->rel_id_2, $recurring_data );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'fee':
                    // This item type is no longer used as of next-release, but we have this for backwards-compat.
                    if ( ! $listing->is_recurring() )
                        $listing->set_fee_plan( $item->rel_id_2, $recurring_data );
                    break;
                case 'featured_upgrade':
                    global $wpdb;
                    $wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'is_sticky' => 1 ), array( 'listing_id' => $listing->get_id() ) );
                    break;
            }
        }

        if ( 'renewal' == $payment->payment_type )
            wpbdp_insert_log( array( 'log_type' => 'listing.renewal', 'object_id' => $payment->listing_id, 'message' => _x( 'Listing renewed', 'listings api', 'WPBDM' ) ) );

        if ( 'initial' == $payment->payment_type )
            $listing->set_status( 'complete' );

        if ( $is_renewal )
            $listing->set_post_status( 'publish' );
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

