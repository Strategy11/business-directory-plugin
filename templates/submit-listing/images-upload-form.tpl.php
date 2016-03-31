<?php
$admin = isset( $admin ) ? $admin : false;
$listing_id = isset( $listing_id ) ? $listing_id : 0;

$action = '';
if ( $admin && $listing_id ) {
    $action = add_query_arg( array( 'action' => 'wpbdp-listing-submit-image-upload',
                                    'listing_id' => $listing_id ),
                             admin_url( 'admin-ajax.php' ) );
} else {
    $action = add_query_arg( array( 'action' => 'wpbdp-listing-submit-image-upload',
                                    'state_id' => $state_id ),
                             admin_url( 'admin-ajax.php' ) );
}
?>
<div class="image-upload-wrapper">
    <h4><?php _ex( 'Upload Images', 'templates', 'WPBDM' ); ?></h4>

    <div class="area-and-conditions cf">
        <div id="image-upload-dnd-area" class="wpbdp-dnd-area" data-action="<?php echo $action; ?>">
            <div class="dnd-area-inside">
                <p class="dnd-message"><?php _ex( 'Drop files here', 'templates', 'WPBDM' ); ?></p>
                <p><?php _ex( 'or', 'templates image upload', 'WPBDM' ); ?></p>
                <p class="dnd-buttons"><span class="upload-button"><a><?php _ex( 'Select images from your hard drive', 'templates', 'WPBDM' ); ?></a><input type="file" name="images[]" multiple="multiple" /></span></p>
            </div>
            <div class="dnd-area-inside-working" style="display: none;">
                <p><?php echo sprintf( _x( 'Uploading %s file(s)... Please wait.', 'templates', 'WPBDM' ), '<span>0</span>' ); ?></p>
            </div>
            <?php if ( ! $admin ): ?>
            <div class="dnd-area-inside-error" style="display: none;">
                <p id="noslots-message" style="display: none;"><?php _ex( 'Your image slots are all full at this time.  You may click "Continue" if you are done, or "Delete Image" to upload a new image in place of a current one.', 'templates', 'WPBDM' ); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( ! $admin ): ?>
        <div id="image-upload-conditions">
            <dl class="image-conditions">
                <dt><?php _ex( 'Image slots available:', 'templates', 'WPBDM' ); ?></dt>
                <dd>
                    <span id="image-slots-remaining"><?php echo $slots_available; ?></span> / <span id="image-slots-total"><?php echo $slots; ?></span>
                </dd>

                <?php if ( $min_file_size ): ?>
                <dt><?php _ex( 'Min. file size:', 'templates', 'WPBDM' ); ?></dt>
                <dd>
                    <?php echo $min_file_size; ?>
                </dd>
                <?php endif; ?>

                <dt><?php _ex( 'Max. file size:', 'templates', 'WPBDM' ); ?></dt>
                <dd>
                    <?php echo $max_file_size; ?>
                </dd>
            </dl>
        </div>
        <?php endif; ?>
    </div>
</div>

<br />
