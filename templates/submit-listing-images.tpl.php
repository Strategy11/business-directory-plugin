<?php
$admin = isset( $admin ) ? $admin : false;
?>
<?php if ( ! $admin ): ?>
<h4><?php _ex( 'Current Images', 'templates', 'WPBDM' ); ?></h4>
<?php endif; ?>

<div id="no-images-message" style="<?php echo ( $images ? 'display: none;' : '' ); ?>"><?php _ex( 'There are no images currently attached to your listing.', 'templates', 'WPBDM' ); ?></div>
<div id="wpbdp-uploaded-images" class="cf">

<?php
foreach ( $images as $image_id ):
    if ( $admin ):
        $vars = array( 'image' => $image_id, 'listing_id' => $listing->get_id(), 'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image_id->id ) );
    else:
        $vars = array( 'image_id' => $image_id,
                       'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image_id ),
                       'weight' => $images_meta[ $image_id ]['order'],
                       'caption' => $images_meta[ $image_id ]['caption'],
                       'listing_id' => $listing->get_id() );
    endif;

    echo wpbdp_render( 'submit-listing-images-single', $vars, false );
endforeach;
?>
</div>

<?php
if ( $admin ):
    $vars = array( 'admin' => true, 'listing_id' => $listing->get_id() );
else:
    $vars = array( 'slots' => $image_slots,
                   'slots_available' => $image_slots_remaining,
                   'min_file_size' => $image_min_file_size,
                   'max_file_size' => $image_max_file_size,
                   'image_min_width' => $image_min_width,
                   'image_max_width' => $image_max_width,
                   'image_min_height' => $image_min_height,
                   'image_max_height' => $image_max_height,
                   'listing_id' => $listing->get_id() );
endif;

echo wpbdp_render( 'submit-listing-images-upload-form', $vars, false );
?>

<script type="text/javascript">
wpbdp.listingSubmit.images.init();
</script>
