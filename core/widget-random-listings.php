<?php
require_once( WPBDP_PATH . 'core/class-listings-widget.php' );

/**
 * Random listings widget.
 * @since 2.1
 */
class WPBDP_RandomListingsWidget extends WPBDP_Listings_Widget {

    public function __construct() {
        parent::__construct( _x( 'Business Directory - Random Listings', 'widgets', 'WPBDM' ),
                             _x( 'Displays a list of random listings from the Business Directory.', 'widgets', 'WPBDM' ) );

        $this->set_default_option_value( 'title', _x( 'Random Listings', 'widgets', 'WPBDM' ) );
    }

    public function get_listings( $instance ) {
        return get_posts( array( 'post_type' => WPBDP_POST_TYPE,
                                 'post_status' => 'publish',
                                 'numberposts' => $instance['number_of_listings'],
                                 'orderby' => 'rand' ) );
    }

}
