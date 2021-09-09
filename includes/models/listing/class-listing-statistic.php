<?php
/**
 *
 * @subpackage  Listing Statistic
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WPBDP_Listing_Statistic {
    
    /**
     * The statistics table name
     *
     * @var string
     */
    private $table_name = '';


    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix."wpbdp_statistics";
    }

    /**
     * Save or update a listing view
     */
    public function save_listing_view( $listing_id, $page_id ) {

    }
}
