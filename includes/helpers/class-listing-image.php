<?php

final class WPBDP_Listing_Image {

    public $id = 0;
    public $slug = '';
    public $mime_type = '';

    public $width = 0;
    public $height = 0;
    public $path = '';
    public $url = '';

    public $thumb_width = 0;
    public $thumb_height = 0;
    public $thumb_path = '';
    public $thumb_url = '';

    public $weight = 0;
    public $caption = '';


    private function __construct( $id ) {
        $this->id = $id;

        // Basic info about the image.
        $post = get_post( $this->id );
        $this->slug = $post->post_name;
        $this->mime_type = $post->post_mime_type;

        // Listing-related metadata.
        $this->weight = (int) get_post_meta( $this->id, '_wpbdp_image_weight', true );
        $this->caption = strval( get_post_meta( $this->id, '_wpbdp_image_caption', true ) );

        $size_data = wp_get_attachment_image_src( $this->id, 'full' );
        $this->width = (int) $size_data[1];
        $this->height = (int) $size_data[2];
        $this->url = (int) $size_data[0];

        $size_data = wp_get_attachment_image_src( $this->id, 'wpbdp-thumb' );
        $this->thumb_width = (int) $size_data[1];
        $this->thumb_height = (int) $size_data[2];
        $this->thumb_url = (int) $size_data[0];
    }

    public static function get( $id ) {
        $id = absint( $id );

        if ( ! $id )
            return false;

        $post = get_post( $id );
        if ( 'attachment' != $post->post_type || WPBDP_POST_TYPE != get_post_type( $post->post_parent ) || ! wp_attachment_is_image( $post->ID ) )
            return false;

        return new WPBDP_Listing_Image( $post->ID );
    }

	/**
	 * If images are not assigned to the directory post type, they'll
	 * be removed from the listing later.
	 *
	 * @since 5.9
	 *
	 * @param array $image_ids - The new media ids being linked.
	 * @param int   $listing_id - The new post parent.
	 */
	public static function maybe_set_post_parent( $image_ids, $listing_id ) {
		foreach ( $image_ids as $image_id ) {
			self::set_post_parent( $image_id, $listing_id );
		}
	}

	/**
	 * If images are not assigned to the directory post type, they'll
	 * be removed from the listing in get().
	 *
	 * @since 5.9
	 *
	 * @param int $id - The attachment id.
	 */
	public static function set_post_parent( $id, $parent ) {
		$post = get_post( $id );
		if ( WPBDP_POST_TYPE !== get_post_type( $post->post_parent ) ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_parent' => $parent
				)
			);
		}
	}

	/**
	 * Detach an image from the listing.
	 *
	 * @since 5.12
	 */
	public static function clear_post_parent( $id ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_parent' => ''
			)
		);
	}

	/**
	 * Only delete if it's not attached to any other posts.
	 *
	 * @since 5.12
	 */
	public static function maybe_delete_image( $id, $listing_id ) {
		global $wpdb;

		// Check if the image is used on another listing.
		$linked_listings = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key=%s AND meta_value LIKE %s LIMIT 2',
				'_wpbdp[images]',
				'%i:' . absint( $id ) . ';%'
			)
		);

		// If the current listing is still cached, don't count it.
		$linked_listings = array_diff( $linked_listings, array( $listing_id ) );

		if ( empty( $linked_listings ) ) {
			wp_delete_attachment( $id, true );
		} else {
			// Attach to the next listing.
			self::set_post_parent( $id, reset( $linked_listings ) );
		}
	}
}
