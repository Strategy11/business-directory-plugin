<?php
require_once( WPBDP_PATH . 'core/class-listings-widget.php' );

/**
 * Featured listings widget.
 * @since 2.1
 */
class WPBDP_FeaturedListingsWidget extends WPBDP_Listings_Widget {

    public function __construct() {
        parent::__construct( _x( 'Business Directory - Featured Listings', 'widgets', 'WPBDM' ),
                             _x( 'Displays a list of the featured/sticky listings in the directory.', 'widgets', 'WPBDM' ) );

        $this->set_default_option_value( 'title', _x( 'Featured Listings', 'widgets', 'WPBDM' ) );
    }

    protected function _form( $instance ) {
        printf( '<p><input id="%s" name="%s" type="checkbox" value="1" %s /> <label for="%s">%s</label></p>',
                $this->get_field_id( 'random_order' ),
                $this->get_field_name( 'random_order' ),
                ( isset( $instance['random_order'] ) && $instance['random_order'] == 1 ) ? 'checked="checked"' : '',
                $this->get_field_id( 'random_order' ),
                _x( 'Display listings in random order', 'widgets', 'WPBDM' )
              );
    }

    public function update( $new, $old ) {
        $new = parent::update( $new, $old );
        $new['random_order'] = intval( $new['random_order'] ) == 1 ? 1 : 0;

        return $new;
    }

    public function get_listings( $instance ) {
        return get_posts( array( 'post_type' => WPBDP_POST_TYPE,
                                 'post_status' => 'publish',
                                 'numberposts' => $instance['number_of_listings'],
                                 'orderby' => ( isset( $instance['random_order'] ) && $instance['random_order'] ) ? 'rand' : 'date',
                                 'meta_query' => array(
                                        array( 'key' => '_wpbdp[sticky]', 'value' => 'sticky' )
                                ) ) );
    }

}
