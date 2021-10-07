<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'business-directory-plugin' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'title' ) ); ?>" />
</p>
<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'number_of_listings' ) ); ?>"><?php esc_html_e( 'Number of listings to display:', 'business-directory-plugin' ); ?></label>
	<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number_of_listings' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number_of_listings' ) ); ?>" type="text" value="<?php echo esc_attr( $this->get_field_value( $instance, 'number_of_listings' ) ); ?>" />
</p>
<?php $this->_form( $instance ); ?>

<?php if ( in_array( 'images', $this->supports ) ) : ?>

	<?php $class_name = $this->get_field_value( $instance, 'show_images' ) ? '' : 'hidden'; ?>
	<h4><?php esc_html_e( 'Thumbnails', 'business-directory-plugin' ); ?></h4>
	<p>
		<input id="<?php echo esc_attr( $this->get_field_id( 'show_images' ) ); ?>" class="wpbdp-toggle-images" name="<?php echo esc_attr( $this->get_field_name( 'show_images' ) ); ?>" type="checkbox" value="1" <?php checked( $this->get_field_value( $instance, 'show_images' ), true ); ?> /> <label for="<?php echo esc_attr( $this->get_field_id( 'show_images' ) ); ?>"><?php esc_html_e( 'Show thumbnails', 'business-directory-plugin' ); ?></label>
	</p>
	<div class="thumbnail-width-config <?php echo esc_attr( $class_name ); ?>">
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'default_image' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'default_image' ) ); ?>" type="checkbox" value="1" <?php checked( $this->get_field_value( $instance, 'default_image' ), true ); ?> /> <label for="<?php echo esc_attr( $this->get_field_id( 'default_image' ) ); ?>"><?php esc_html_e( 'Show "No Image" PNG when listing has no picture (improves layout).', 'business-directory-plugin' ); ?></label>
		</p>
		<p><strong><?php esc_html_e( 'Position of the thumbnail (Desktop):', 'business-directory-plugin' ); ?></strong></p>
		<p>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="above" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'above' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_above' ) ); ?>"><?php esc_html_e( 'Above the listing text.', 'business-directory-plugin' ); ?></label>
			<br/>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_left' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="left" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'left' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_left' ) ); ?>"><?php esc_html_e( 'To the left of the listing text.', 'business-directory-plugin' ); ?></label>
			<br/>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_right' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_desktop' ) ); ?>" value="right" <?php checked( $this->get_field_value( $instance, 'thumbnail_desktop' ), 'right' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_desktop_right' ) ); ?>"><?php esc_html_e( 'To the right of the listing text.', 'business-directory-plugin' ); ?></label>
		</p>

		<p><strong><?php echo __( 'Position of the thumbnail (mobile):', 'business-directory-plugin' ); ?></strong></p>
		
		<p>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="above" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'above' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_above' ) ); ?>"><?php esc_html_e( 'Above the listing text.', 'business-directory-plugin' ); ?></label>
			<br/>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_left' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="left" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'left' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_left' ) ); ?>"><?php esc_html_e( 'To the left of the listing text.', 'business-directory-plugin' ); ?></label>
			<br/>
			<input type="radio" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_right' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_mobile' ) ); ?>" value="right" <?php checked( $this->get_field_value( $instance, 'thumbnail_mobile' ), 'right' ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_mobile_right' ) ); ?>"><?php esc_html_e( 'To the right of the listing text.', 'business-directory-plugin' ); ?></label>
		</p>
	</div>

<?php endif; ?>
