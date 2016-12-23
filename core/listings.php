<?php
require_once( WPBDP_PATH . 'core/class-listing.php' );
require_once( WPBDP_PATH . 'core/class-listings-api.php' );


/**
 * @since next-release
 */
function wpbdp_save_listing( $data, $error = false ) {
    return WPBDP_Listing::insert_or_update( $data, $error );
}

/**
 * @since next-release
 */
function wpbdp_get_listing( $listing_id ) {
    return WPBDP_Listing::get( $listing_id );
}
