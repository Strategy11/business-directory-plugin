<?php
require_once( WPBDP_PATH . 'core/class-fee-plan.php' );

if ( ! class_exists( 'WPBDP_Fees_API' ) ) {

class WPBDP_Fees_API {

    public function __construct() {
        $this->setup_default_fees();
    }

    private function setup_default_fees() {
        global $wpdb;

        if ( 0 === intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_fees WHERE tag = %s", 'free' ) ) ) ) {
            // Add free fee to the DB.
            $wpdb->insert( $wpdb->prefix . 'wpbdp_fees',
                           array( 'id' => 0,
                                  'tag' => 'free',
                                  'label' => _x( 'Free Listing', 'fees-api', 'WPBDM' ),
                                  'amount' => 0.0,
                                  'images' => absint( wpbdp_get_option( 'free-images' ) ),
                                  'days' => absint( wpbdp_get_option( 'listing-duration' ) ),
                                  'categories' => maybe_serialize( array( 'all' => true, 'categories' => array() ) ),
                                  'sticky' => 0,
                                  'enabled' => 1 ) );
            $fee_id = $wpdb->insert_id;

            // Update all "free fee" listings to use this.
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_listing_fees SET fee_id = %d WHERE fee_id = %d", $fee_id, 0 ) );
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpbdp_payments_items SET rel_id_2 = %d WHERE rel_id_2 = %d AND item_type = %s", $fee_id, 0, 'fee' ) );
        }
    }

    // TODO: check if this is being used.
    /**
     * @deprecated since 3.7.
     */
    public static function get_free_fee() { return false; }

    /**
     * @deprecated since 3.7. See {@link WPBDP_Fee_Plan}.
     */
    public function get_fees( $categories = null ) {
        global $wpdb;

        if ( ! $categories )
            return WPBDP_Fee_Plan::find();

        $fees = array();
        foreach ( $categories as $cat_id ) {
            $category_fees = WPBDP_Fee_Plan::for_category( $cat_id );

            // XXX: For now, we keep the free plan a 'secret' when payments are enabled. This is for backwards compat.
            if ( wpbdp_payments_possible() ) {
                foreach ( $category_fees as $k => $v ) {
                    if ( 'free' == $v->tag || ! $v->enabled )
                        unset( $category_fees[ $k ] );
                }
            }

            // Do this so the first fee is at index 0.
            $category_fees = array_merge( array(), $category_fees );
            $fees[ $cat_id ] = $category_fees;
        }

        return $fees;
    }

}

}
