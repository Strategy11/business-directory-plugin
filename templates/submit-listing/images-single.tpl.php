<?php
$is_thumbnail = isset( $is_thumbnail ) ? $is_thumbnail : false;
?>

<div class="wpbdp-image" data-imageid="<?php echo $image_id; ?>">
    <img src="<?php echo wp_get_attachment_thumb_url( $image_id ); ?>" /><br />
    <input type="button"
           class="button delete-image"
           value="<?php _ex('Delete Image', 'templates', 'WPBDM'); ?>"
           data-action="<?php echo esc_url( add_query_arg( array( 'action' => 'wpbdp-listing-submit-image-delete',
                                                                  'state_id' => isset( $state_id ) ? $state_id : '',
                                                                  'image_id' => $image_id ), admin_url( 'admin-ajax.php' ) ) ); ?>" /> <br />

    <label>
        <input type="radio" name="thumbnail_id" value="<?php echo $image_id; ?>" <?php echo $is_thumbnail ? 'checked="checked"' : ''; ?> />
        <?php _ex('Set this image as the listing thumbnail.', 'templates', 'WPBDM'); ?>
    </label>
</div>
