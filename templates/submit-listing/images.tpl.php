<?php
$thumbnail_id = ! isset( $thumbnail_id ) ? 0 : intval( $thumbnail_id );
?>
<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Listing Images', 'templates', 'WPBDM' ); ?></h3>

<form id="wpbdp-listing-form-images" class="wpbdp-listing-form" method="POST" action="" enctype="multipart/form-data">
    <span class="confirm-submit-message" style="display: none;"><?php _ex( 'There is an image pending upload. Would you still like to continue without saving the image?',
                                                                           'templates',
                                                                           'WPBDM' ); ?></span>
    <input type="hidden" name="_state" value="<?php echo $_state->id; ?>" />

    <h4><?php _ex( 'Current Images', 'templates', 'WPBDM' ); ?></h4>
    <div id="no-images-message" style="<?php echo ( $images ? 'display: none;' : '' ); ?>"><?php _ex( 'There are no images currently attached to your listing.', 'templates', 'WPBDM' ); ?></div>
    <div id="wpbdp-uploaded-images" class="cf">
    <?php
    foreach ( $images as $image_id ):
        echo wpbdp_render( 'submit-listing/images-single',
                           array( 'image_id' => $image_id,
                                  'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image_id ),
                                  'weight' => $images_meta[ $image_id ]['order'],
                                  'caption' => $images_meta[ $image_id ]['caption'],
                                  'state_id' => $_state->id ),
                           false );
    endforeach;
    ?>
    </div>

    <?php
    echo wpbdp_render( 'submit-listing/images-upload-form',
                       array( 'slots' => $image_slots,
                              'slots_available' => $image_slots_remaining,
                              'min_file_size' => $image_min_file_size,
                              'max_file_size' => $image_max_file_size,
                              'state_id' => $_state->id ),
                       false );
    ?>

    <input type="submit" class="submit" name="finish" value="<?php _ex( 'Continue', 'templates', 'WPBDM' ); ?>" />      
</form>
