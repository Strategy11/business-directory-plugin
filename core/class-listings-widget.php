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
        printf( '<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                $this->get_field_id( 'title' ),
                _x( 'Title:', 'widgets', 'WPBDM' ),
                $this->get_field_id( 'title' ),
                $this->get_field_name( 'title' ),
                esc_attr( $this->get_field_value( $instance, 'title' ) ) );
        printf( '<p><label for="%s">%s</label> <input id="%s" name="%s" type="text" value="%s" size="5" /></p>',
                $this->get_field_id( 'number_of_listings' ),
                _x( 'Number of listings to display:', 'widgets', 'WPBDM' ),
                $this->get_field_id( 'number_of_listings' ),
                $this->get_field_name( 'number_of_listings' ),
                intval( $this->get_field_value( $instance, 'number_of_listings' ) ) );

        $this->_form( $instance );

        if ( in_array( 'images', $this->supports ) ) {
            echo '<h4>';
            _ex( 'Thumbnails', 'widgets', 'WPBDM' );
            echo '</h4>';

            printf( '<p><input id="%s" class="wpbdp-toggle-images" name="%s" type="checkbox" value="1" %s /> <label for="%s">%s</label></p>',
                    $this->get_field_id( 'show_images' ),
                    $this->get_field_name( 'show_images' ),
                    $this->get_field_value( $instance, 'show_images' ) ? 'checked="checked"' : '',
                    $this->get_field_id( 'show_images' ),
                    _x( 'Show thumbnails', 'widgets', 'WPBDM' ) );

            echo '<p class="thumbnail-width-config" style="' . ( $this->get_field_value( $instance, 'show_images' ) ? '' : 'display: none;' ) . '">';
            echo '<label for="' . $this->get_field_id( 'thumbnail_width' ) . '">';
            _ex( 'Image width (in px):', 'widgets', 'WPBDM' );
            echo '</label> ';
            printf( '<input type="text" name="%s" id="%s" value="%s" size="5" />',
                    $this->get_field_name( 'thumbnail_width' ),
                    $this->get_field_id( 'thumbnail_width' ),
                    $this->get_field_value( $instance, 'thumbnail_width' ) );
            echo '<br /><span class="help">' . _x( 'Leave blank for automatic width.', 'widgets', 'WPBDM' ) . '</span>';
            echo '</p>';

            echo '<p class="thumbnail-height-config" style="' . ( $this->get_field_value( $instance, 'show_images' ) ? '' : 'display: none;' ) . '">';
            echo '<label for="' . $this->get_field_id( 'thumbnail_height' ) . '">';
            _ex( 'Image height (in px):', 'widgets', 'WPBDM' );
            echo '</label> ';
            printf( '<input type="text" name="%s" id="%s" value="%s" size="5" />',
                    $this->get_field_name( 'thumbnail_height' ),
                    $this->get_field_id( 'thumbnail_height' ),
                    $this->get_field_value( $instance, 'thumbnail_height' ) );
            echo '<br /><span class="help">' . _x( 'Leave blank for automatic height.', 'widgets', 'WPBDM' ) . '</span>';
            echo '</p>';
        }
    }

    public function update( $new, $old ) {
        $new['title'] = strip_tags( $new['title'] );
        $new['number_of_listings'] = max( intval( $new['number_of_listings'] ), 1 );
        $new['show_images'] = intval( $new['show_images'] ) == 1 ? 1 : 0;

        if ( $new['show_images'] ) {
            $new['thumbnail_width'] = max( intval( $new['thumbnail_width'] ), 0 );
            $new['thumbnail_height'] = max( intval( $new['thumbnail_height'] ), 0 );
        }

        return $new;
    }

    public function widget( $args, $instance ) {
        extract($args);
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
