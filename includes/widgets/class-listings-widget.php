<?php
/**
 * @since 3.5.3
 */
class WPBDP_Listings_Widget extends WP_Widget {

	protected $supports = array( 'images' );
	protected $defaults = array();


	public function __construct( $name, $description = '' ) {
		parent::__construct( false, $name, array( 'description' => $description ) );
		$this->defaults['title'] = str_replace( array( 'WPBDP', '_' ), array( '', ' ' ), get_class( $this ) );
	}

	/**
	 * Default Form Settings.
	 *
	 * @since  x.x
	 * 
	 * @return array
	 */
	protected function defaults() {
		return array(
			'number_of_listings' => 5,
			'show_images'        => 0,
			'default_image'      => 0,
			'thumbnail_desktop'  => 'left',
			'thumbnail_mobile'   => 'above',
		);
	}

	/**
	 * Instance defaults
	 *
	 * @return array
	 */
	protected function instance_defaults( $instance ) {
		return array_merge( $this->defaults(), $instance );
	}

	protected function set_default_option_value( $k, $v = '' ) {
		$this->defaults[ $k ] = $v;
	}

	protected function get_field_value( $instance, $k ) {
		$instance = $this->instance_defaults( $instance );
		if ( isset( $instance[ $k ] ) ) {
			return $instance[ $k ];
		}

		if ( isset( $this->defaults[ $k ] ) ) {
			return $this->defaults[ $k ];
		}

		return false;
	}

	public function print_listings( $instance ) {
		return '';
	}

	public function get_listings( $instance ) {
		return array();
	}

	protected function _form( $instance ) { }

	/**
	 * Render the settings form
	 */
	public function form( $instance ) {
		$instance = $this->instance_defaults( $instance );
		require WPBDP_INC . 'views/widget/widget-settings.php';
	}

	/**
	 * Handle settings update
	 */
	public function update( $new, $old ) {
		$instance                       = $old;
		$instance['title']              = strip_tags( $new['title'] );
		$instance['number_of_listings'] = max( intval( $new['number_of_listings'] ), 1 );
		$instance['show_images']        = ! empty( $new['show_images'] ) ? 1 : 0;

		if ( $instance['show_images'] ) {
			$instance['default_image']     = ! empty( $new['default_image'] ) ? 1 : 0;
			$instance['thumbnail_desktop'] = sanitize_text_field( $new['thumbnail_desktop'] );
			$instance['thumbnail_mobile']  = sanitize_text_field( $new['thumbnail_mobile'] );
		}

		return $instance;
	}

	/**
	 * Render the widget
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		$title    = apply_filters( 'widget_title', $this->get_field_value( $instance, 'title' ) );
		$instance = $this->instance_defaults( $instance );

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		$out = $this->print_listings( $instance );

		if ( ! $out ) {
			$listings = $this->get_listings( $instance );
			$out     .= '<ul class="wpbdp-listings-widget-list">';
			$out     .= $this->render( $listings, $instance );
			$out     .= '</ul>';
		}

		echo $out;
		echo $after_widget;
	}


	/**
	 * [render description]
	 *
	 * @param  [type] $items      [description]
	 * @param  [type] $instance   [description]
	 * @param  string $html_class CSS class for each LI element.
	 *
	 * @since  x.x
	 *
	 * @return string             HTML
	 */
	protected function render( $items, $instance, $html_class = '' ) {
		if ( empty( $items ) ) {
			return $this->render_empty_widget( $html_class );
		}

		return $this->render_widget( $items, $instance, $html_class );
	}

	/**
	 * Render empty message
	 *
	 * @param string $html_class - the html class to append to the view
	 *
	 * @since  x.x
	 *
	 * @return string
	 */
	private function render_empty_widget( $html_class ) {
		return wp_kses_post( sprintf( '<li class="wpbdp-empty-widget %s">%s</li>', $html_class, __( 'There are currently no listings to show.', 'business-directory-plugin' ) ) );
	}

	/**
	 * Render the widget
	 *
	 * @param array $items - the widget items
	 * @param array $instance - the settings instance
	 * @param string $html_class - the html class to append to the view
	 *
	 * @since  x.x
	 *
	 * @return string
	 */
	private function render_widget( $items, $instance, $html_class ) {
		$html_class = implode(
			' ',
			array(
				$this->get_item_thumbnail_position_css_class( $instance['thumbnail_desktop'], 'desktop' ),
				$this->get_item_thumbnail_position_css_class( $instance['thumbnail_mobile'], 'mobile' ),
				$html_class,
			)
		);

		$show_images       = in_array( 'images', $this->supports ) && isset( $instance['show_images'] ) && $instance['show_images'];
		$default_image     = $show_images && isset( $instance['default_image'] ) && $instance['default_image'];
		$coming_soon_image = WPBDP_Listing_Display_Helper::get_coming_soon_image();
		foreach ( $items as $post ) {
			$html[] = $this->render_item( $post, compact( 'show_images', 'default_image', 'coming_soon_image', 'html_class' ) );
		}

		return join( "\n", $html );
	}

	/**
	 * Generate the thumbnail position classes.
	 *
	 * @param string $thumbnail_position - the thumbnail position ( left, right )
	 * @param string $device - the device being used ( desktop, mobile )
	 *
	 * @since  x.x
	 *
	 * @return string
	 */
	private function get_item_thumbnail_position_css_class( $thumbnail_position, $device ) {
		if ( $thumbnail_position == 'left' || $thumbnail_position == 'right' ) {
			$css_class = sprintf( 'wpbdp-listings-widget-item-with-%s-thumbnail-in-%s', $thumbnail_position, $device );
		} else {
			$css_class = sprintf( 'wpbdp-listings-widget-item-with-thumbnail-above-in-%s', $device );
		}

		return $css_class;
	}

	/**
	 * Render item for widget
	 *
	 * @param WP_Post $post - the current listing post
	 * @param array $args - the view arguments
	 *
	 * @since  x.x
	 *
	 * @return string
	 */
	private function render_item( $post, $args ) {
		$listing       = wpbdp_get_listing( $post->ID );
		$listing_title = sprintf( '<div class="wpbdp-listing-title"><a class="listing-title" href="%s">%s</a></div>', esc_url( $listing->get_permalink() ), esc_html( $listing->get_title() ) );
		$html_image    = $this->render_image( $listing, $args );

		$template = '<li class="wpbdp-listings-widget-item %1$s"><div class="wpbdp-listings-widget-container">';
		if ( ! empty( $html_image ) ) {
			$template .= '<div class="wpbdp-listings-widget-thumb">%2$s</div>';
		} else {
			$args['html_class'] .= ' wpbdp-listings-widget-item-without-thumbnail';
		}
		$template .= '<div class="wpbdp-listings-widget-item--title-and-content">%3$s</div></div></li>';
		$args['image'] = $html_image;
		$output = sprintf( $template, $args['html_class'], $html_image, $listing_title );
		return apply_filters( 'wpbdp_listing_widget_item', wp_kses_post( $output ), $args );
	}

	/**
	 * Render the listing image
	 * Depending on the settings, this will return the listing image or the default image or none
	 *
	 * @param object $listing - the listing object
	 * @param array $args - the view arguments
	 *
	 * @since  x.x
	 *
	 * @return string
	 */
	private function render_image( $listing, $args ) {
		$image_link = '';
		if ( $args['show_images'] ) {
			$img_size = 'medium';
			$img_id = $listing->get_thumbnail_id();
			$permalink = esc_url( $listing->get_permalink() );
			if ( $img_id ) {
				$image_link = '<a href="' . $permalink . '">' . wp_kses_post( wp_get_attachment_image( $img_id, $img_size, false, array( 'class' => 'listing-image' ) ) ). '</a>';
			} elseif ( $args['default_image'] ) {
				$class      = "attachment-$img_size size-$img_size listing-image";
				$image_link = '<a href="' . $permalink . '"><img src="' . wp_kses_post( $args['coming_soon_image'] ) . '" class="' . $class . '" /></a>';
			} else {
				// For image spacing.
				$image_link = '<span></span>';
			}
		}
		return apply_filters( 'wpbdp_listings_widget_render_image', wp_kses_post( $image_link ), $listing );
	}

}
