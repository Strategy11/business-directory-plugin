<?php
/**
 * Latest listings widget.
 * @since 2.1
 */
class WPBDP_LatestListingsWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(false,
                            _x('Business Directory - Latest Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of the latest listings in the Business Directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        if (isset($instance['title']))
            $title = $instance['title'];
        else
            $title = _x('Latest Listings', 'widgets', 'WPBDM');

        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     esc_attr($title)
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );        
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );

        echo $before_widget;
        if ( ! empty( $title ) )
            echo $before_title . $title . $after_title;
        echo wpbdp_latest_listings($instance['number_of_listings']);
        echo $after_widget;        
    }

}


/**
 * Featured listings widget.
 * @since 2.1
 */
class WPBDP_FeaturedListingsWidget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(false, _x('Business Directory - Featured Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of the featured/sticky listings in the directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     isset($instance['title']) ? esc_attr($instance['title']) : _x('Featured Listings', 'widgets', 'WPBDM')
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters( 'widget_title', $instance['title'] );

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => $instance['number_of_listings'],
            'orderby' => 'date',
            'meta_query' => array(
                array('key' => '_wpbdp[sticky]', 'value' => 'sticky')
            )
        ));

        if ($posts) {
            echo $before_widget;
            if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

            echo '<ul>';
            foreach ($posts as $post) {
                echo '<li>';
                echo sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
                echo '</li>';
            }

            echo '</ul>';
            echo $after_widget;
        }
    }    


}

/**
 * Random listings widget.
 * @since 2.1
 */
class WPBDP_RandomListingsWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(false,
                            _x('Business Directory - Random Listings', 'widgets', 'WPBDM'),
                            array('description' => _x('Displays a list of random listings from the Business Directory.', 'widgets', 'WPBDM')));
    }

    public function form($instance) {
        if (isset($instance['title']))
            $title = $instance['title'];
        else
            $title = _x('Random Listings', 'widgets', 'WPBDM');

        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('title'),
                     _x('Title:', 'widgets', 'WPBDM'),
                     $this->get_field_id('title'),
                     $this->get_field_name('title'),
                     esc_attr($title)
                    );
        echo sprintf('<p><label for="%s">%s</label> <input class="widefat" id="%s" name="%s" type="text" value="%s" /></p>',
                     $this->get_field_id('number_of_listings'),
                     _x('Number of listings to display:', 'widgets', 'WPBDM'),
                     $this->get_field_id('number_of_listings'),
                     $this->get_field_name('number_of_listings'),
                     isset($instance['number_of_listings']) ? intval($instance['number_of_listings']) : 10
                    );        
    }

    public function update($new_instance, $old_instance) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $new_instance['number_of_listings'] = max(intval($new_instance['number_of_listings']), 0);
        return $new_instance;
    }

    private function random_posts($n) {
        global $wpdb;

        $n = max(intval($n), 0);

        $query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY RAND() LIMIT {$n}",
                                wpbdp_post_type(), 'publish');
        return $wpdb->get_col($query);
    }

    public function widget($args, $instance) {
        $post_ids = $this->random_posts($instance['number_of_listings']);

        if (!$post_ids) return;

        $posts = get_posts(array(
            'post_type' => wpbdp_post_type(),
            'post_status' => 'publish',
            'numberposts' => $instance['number_of_listings'],
            'post__in' => $post_ids
        ));

        if ($posts) {
            extract($args);
            $title = apply_filters( 'widget_title', $instance['title'] );

            echo $before_widget;
            if ( ! empty( $title ) ) echo $before_title . $title . $after_title;

            echo '<ul>';
            foreach ($posts as $post) {
                echo '<li>';
                echo sprintf('<a href="%s">%s</a>', get_permalink($post->ID), get_the_title($post->ID));
                echo '</li>';
            }

            echo '</ul>';
            echo $after_widget;
        }        

    }

}