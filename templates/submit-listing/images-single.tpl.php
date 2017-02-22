<?php
$is_thumbnail = isset( $is_thumbnail ) ? $is_thumbnail : false;
$listing_id = isset( $listing_id ) ? absint( $listing_id ) : 0;

if ( isset( $image ) ) {
    $image_id = $image->id;
    $weight = $image->weight;
    $caption = $image->caption;
}
?>
<div class="wpbdp-image" data-imageid="<?php echo $image_id; ?>">
<input type="hidden" name="images_meta[<?php echo $image_id; ?>][order]" value="<?php echo ( isset( $weight ) ? $weight : 0 ); ?>" />
    <input type="hidden" name="images_meta[<?php echo $image_id; ?>][caption]" value="<?php echo ( isset( $caption ) ? esc_attr( $caption ) : '' ); ?>" />

    <img src="<?php echo wp_get_attachment_thumb_url( $image_id ); ?>" /><br />
    <input type="button"
           class="wpbdp-button button delete-image"
           value="<?php _ex('Delete Image', 'templates', 'WPBDM'); ?>"
           data-action="<?php echo  esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wpbdp-listing-submit-image-delete',
                                                                  'state_id' => isset( $state_id ) ? $state_id : '',
                                                                  'listing_id' => isset( $listing_id ) ? $listing_id : 0,
                                                                  'image_id' => $image_id ), admin_url( 'admin-ajax.php' ) ), 'delete-listing-' . ( $listing_id ? $listing_id : $state_id ). '-image-' . $image_id ) ); ?>" /> <br />
    <label>
        <input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo $is_thumbnail ? 'checked="checked"' : ''; ?> />
        <?php _ex('Set this image as the listing thumbnail.', 'templates', 'WPBDM'); ?>
    </label>
</div>
