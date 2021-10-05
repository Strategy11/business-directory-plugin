<?php
/**
 * @since 3.5.3
 */
class WPBDP_Listings_Widget extends WP_Widget {

    protected $supports = array( 'images' );
    protected $defaults = array();


    public function __construct( $name, $description = '' ) {
        parent::__construct( false, $name, array( 'description' => $description ) );

        $this->defaults['title'] = str_replace( array( 'WPBDP', '_' ), array( '', ' '), get_class( $this ) );
        $this->defaults['number_of_listings'] = 10;
    }

    protected function set_default_option_value( $k, $v = '' ) {
        $this->defaults[ $k ] = $v;
    }

    protected function get_field_value( $instance, $k ) {
        if ( isset( $instance[ $k ] ) )
            return $instance[ $k ];

        if ( isset( $this->defaults[ $k ] ) )
            return $this->defaults[ $k ];

        return false;
    }

    public function print_listings( $instance ) {
        return '';
    }

    public function get_listings( $instance ) {
        return array();
    }

    protected function _form( $instance ) { }

    public function form( $instance ) {
        include_once WPBDP_INC . 'views/widget/widget-settings.php';
    }

    public function update( $new, $old ) {
        $new['title'] = strip_tags( $new['title'] );
        $new['number_of_listings'] = max( intval( $new['number_of_listings'] ), 1 );
        $new['show_images'] = intval( $new['show_images'] ) == 1 ? 1 : 0;

        if ( $new['show_images'] ) {
            $new['thumbnail_width'] = max( intval( $new['thumbnail_width'] ), 0 );
            $new['thumbnail_height'] = max( intval( $new['thumbnail_height'] ), 0 );
            $new['thumbnail-position-in-desktop'] = sanitize_text_field( $new['thumbnail-position-in-desktop'] );
            $new['thumbnail-position-in-mobile'] = sanitize_text_field( $new['thumbnail-position-in-mobile'] );
        }

        return $new;
    }

    public function widget( $args, $instance ) {
		extract( $args );
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $before_widget;
        if ( ! empty( $title ) )
            echo $before_title . $title . $after_title;

        $out = $this->print_listings( $instance );

        if ( ! $out ) {
            if ( $listings = $this->get_listings( $instance ) ) {
                $show_images = in_array( 'images', $this->supports ) && isset( $instance['show_images'] ) && $instance['show_images'];
                $thumb_w = isset( $instance['thumbnail_width'] ) ? $instance['thumbnail_width'] : 0;
                $thumb_h = isset( $instance['thumbnail_height'] ) ? $instance['thumbnail_height'] : 0;

                $img_size = 'wpbdp-thumb';
                if ( $show_images && ( $thumb_w > 0 || $thumb_h > 0 ) ) {
                    $img_size = array( $thumb_w, $thumb_h );
                }

                $out .= '<ul class="wpbdp-listings-widget-list">';

                foreach ( $listings as &$post ) {
                    $listing = WPBDP_Listing::get( $post->ID );

                    $out .= '<li>';
                    $out .= sprintf( '<a class="listing-title" href="%s">%s</a>', get_permalink( $post->ID ), get_the_title( $post->ID ) );

                    if ( $show_images ) {
                        if ( $img_id = $listing->get_thumbnail_id() ) {
                            $out .= '<a href="' . get_permalink( $post->ID ) . '">' . wp_get_attachment_image( $img_id, $img_size, false, array( 'class' => 'listing-image' ) ) . '</a>';
                        }
                    }

                    $out .= '</li>';
                }

                $out .= '</ul>';
            }
        }

        echo $out;
        echo $after_widget;
    }

}
