<div class="image-upload-wrapper">
    <h4><?php _ex( 'Upload Images', 'templates', 'WPBDM' ); ?></h4>

    <div class="area-and-conditions cf">
        <div id="image-upload-dnd-area" class="wpbdp-dnd-area">
            <div class="dnd-area-inside">
                <p class="dnd-message"><?php _ex( 'Drop files here', 'templates', 'WPBDM' ); ?></p>
                <p>or</p>
                <p class="dnd-buttons"><input type="button" value="<?php _ex( 'Select images from your hard drive', 'templates', 'WPBDM' ); ?>" /></p>
            </div>
            <div class="dnd-area-inside-working" style="display: none;">
                <p><?php echo sprintf( _x( 'Uploading %s file(s)... Please wait.', 'templates', 'WPBDM' ), '<span>0</span>' ); ?></p>
            </div>
            <div class="dnd-area-inside-error" style="display: none;">
                <p id="noslots-message" style="display: none;"><?php _ex( 'Your image slots are all full at this time.  You may click "Continue" if you are done, or "Delete Image" to upload a new image in place of a current one.', 'templates', 'WPBDM' ); ?></p>
            </div>
        </div>

        <div id="image-upload-conditions">
            <dl class="image-conditions">
                <dt><?php _ex( 'Image slots available:', 'templates', 'WPBDM' ); ?></dt>
                <dd>
                    <span id="image-slots-remaining"><?php echo $slots_available; ?></span> / <span id="image-slots-total"><?php echo $slots; ?></span>
                </dd>

                <dt><?php _ex( 'Max. file size:', 'templates', 'WPBDM' ); ?></dt>
                <dd>
                    <?php echo $max_file_size; ?>
                </dd>
            </dl>
        </div>
    </div>

    <div id="image-upload-form-no-js">
        <input type="file" id="image-upload-input" name="images[]" multiple="multiple" />
        <input type="submit" class="submit" name="upload-image" value="<?php _ex('Upload Image', 'templates', 'WPBDM'); ?>" />
    </div>
</div>

<br />
