<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'business-directory-plugin' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'title' ) ); ?>" />
</p>
<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_listings' ) ); ?>"><?php _e( 'Number of listings to display:', 'business-directory-plugin' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number_of_listings' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number_of_listings' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'number_of_listings' ) ); ?>" />
</p>
<?php $this->_form( $instance ); ?>

<?php if ( in_array( 'images', $this->supports ) ) : ?>

	<?php $style = 'style="' . ( $this->get_field_value( $instance, 'show_images' ) ? '' : 'display: none;' ) . '"'; ?>
	<h4><?php _e( 'Thumbnails', 'business-directory-plugin' ); ?></h4>
	<p>
		<input id="<?php echo esc_attr( $this->get_field_id( 'show_images' ) ); ?>" class="wpbdp-toggle-images" name="<?php echo esc_attr( $this->get_field_name( 'show_images' ) ); ?>" type="checkbox" value="1" <?php checked( $this->get_field_value( $instance, 'show_images' ), true ); ?> /> <label for="<?php echo esc_attr( $this->get_field_id( 'show_images' ) ); ?>"><?php _e( 'Show thumbnails', 'business-directory-plugin' ); ?></label>
	</p>
	<p class="thumbnail-width-config" <?php echo $style; ?> >
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_width' ) ); ?>"><?php _e( 'Image width (in px)', 'business-directory-plugin' ); ?>:</label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_width' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'thumbnail_width' ) ); ?>" size="5" />
		<span class="help components-placeholder__instructions"><?php _e( 'Leave blank for automatic width.', 'business-directory-plugin' ); ?></span>
	</p>
	<p class="thumbnail-width-config" <?php echo $style; ?> >
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_height' ) ); ?>"><?php _e( 'Image height (in px)', 'business-directory-plugin' ); ?>:</label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_height' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_height' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'thumbnail_height' ) ); ?>" size="5" />
		<span class="help components-placeholder__instructions"><?php _e( 'Leave blank for automatic height.', 'business-directory-plugin' ); ?></span>
	</p>
	<p class="thumbnail-width-config"  <?php echo $style; ?>>
		<input id="<?php echo esc_attr( $this->get_field_id( 'default_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'default_image' ) ); ?>" type="checkbox" value="1" <?php checked( $this->get_field_value( $instance, 'default_image' ), true ); ?> /> <label for="<?php echo esc_attr( $this->get_field_id( 'default_image' ) ); ?>"><?php _e( 'Show "No Image" PNG when listing has no picture (improves layout).', 'business-directory-plugin' ); ?></label>
	</p>
	<p class="thumbnail-width-config"  <?php echo $style; ?>><strong><?php _e( 'Position of the thumbnail (Desktop):', 'business-directory-plugin' ); ?></strong></p>
	<p class="thumbnail-width-config"  <?php echo $style; ?>>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="above" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'above' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_above' ) ); ?>"><?php _e( 'Above the listing text.', 'business-directory-plugin' ); ?></label>
		<br/>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_left' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="left" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'left' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_left' ) ); ?>"><?php _e( 'To the left of the listing text.', 'business-directory-plugin' ); ?></label>
		<br/>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_right' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="right" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'right' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_right' ) ); ?>"><?php _e( 'To the right of the listing text.', 'business-directory-plugin' ); ?></label>
	</p>

	<p class="thumbnail-width-config"  <?php echo $style; ?>><strong><?php echo __( 'Position of the thumbnail (mobile):', 'business-directory-plugin' ); ?></strong></p>
	
	<p class="thumbnail-width-config"  <?php echo $style; ?>>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="above" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'above' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_above' ) ); ?>"><?php _e( 'Above the listing text.', 'business-directory-plugin' ); ?></label>
		<br/>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_left' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="left" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'left' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_left' ) ); ?>"><?php _e( 'To the left of the listing text.', 'business-directory-plugin' ); ?></label>
		<br/>
		<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_right' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="right" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'right' ); ?> />
		<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_right' ) ); ?>"><?php _e( 'To the right of the listing text.', 'business-directory-plugin' ); ?></label>
	</p>


<?php endif; ?>
