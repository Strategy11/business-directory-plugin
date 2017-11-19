<?php

if ( ! class_exists( 'WPBDP_Listings_API' ) ) {

/**
 * @since 3.5.4
 */
class WPBDP_Listings_API {

    public function __construct() {
        add_action( 'wpbdp_payment_completed', array( $this, 'update_listing_after_payment' ) );
    }

    public function update_listing_after_payment( $payment ) {
        $listing = $payment->get_listing();

        if ( ! $listing )
            return;

        $is_renewal = 'renewal' == $payment->payment_type;

        foreach ( $payment->payment_items as $item ) {
            switch ( $item['type'] ) {
            case 'recurring_plan':
            case 'plan':
            case 'plan_renewal': // Do we really use/need this?
                $listing->update_plan( $item );

                if ( 'plan_renewal' == $item['type'] ) {
                    $is_renewal = true;
                }
                break;
            }
        }

        $listing->set_status( 'complete' );

        if ( $is_renewal) {
            wpbdp_insert_log( array( 'log_type' => 'listing.renewal', 'object_id' => $payment->listing_id, 'message' => _x( 'Listing renewed', 'listings api', 'WPBDM' ) ) );
            $listing->set_post_status( 'publish' );

            do_action( 'wpbdp_listing_renewed', $listing, $payment );
        }
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

    /**
     * @deprecated since 5.0. Added back in 5.1.2 for compatibility with other plugins (#3178)
     */
    public function get_thumbnail_id( $listing_id ) {
        if ( $listing = wpbdp_get_listing( $listing_id ) ) {
            return $listing->get_thumbnail_id();
        }

        return 0;
    }

}

}

