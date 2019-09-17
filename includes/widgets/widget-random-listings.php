<?php
require_once( WPBDP_PATH . 'includes/widgets/class-listings-widget.php' );

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
        $posts = new WP_Query(
            array(
                'post_type' => WPBDP_POST_TYPE,
                'post_status' => 'publish',
                'suppress_filters' => false
            )
        );

        $posts       = $posts->posts;
        $posts_count = count( $posts );

        $keys = array_rand( $posts, $instance['number_of_listings'] < $posts_count ? $instance['number_of_listings'] : $posts_count );
        $rand = array();

        foreach ( $keys as $key ) {
            $rand[] = $posts[$key];
        }

        return $rand;
    }

}
