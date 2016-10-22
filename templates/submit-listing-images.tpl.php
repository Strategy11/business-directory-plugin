<h4><?php _ex( 'Current Images', 'templates', 'WPBDM' ); ?></h4>
<div id="no-images-message" style="<?php echo ( $images ? 'display: none;' : '' ); ?>"><?php _ex( 'There are no images currently attached to your listing.', 'templates', 'WPBDM' ); ?></div>
<div id="wpbdp-uploaded-images" class="cf">
<?php
foreach ( $images as $image_id ):
    echo wpbdp_render( 'submit-listing-images-single',
                       array( 'image_id' => $image_id,
                              'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image_id ),
                              'weight' => $images_meta[ $image_id ]['order'],
                              'caption' => $images_meta[ $image_id ]['caption'],
                              'listing_id' => $listing->get_id() ),
                       false );
endforeach;
?>
</div>

<?php
echo wpbdp_render( 'submit-listing-images-upload-form',
                   array( 'slots' => $image_slots,
                          'slots_available' => $image_slots_remaining,
                          'min_file_size' => $image_min_file_size,
                          'max_file_size' => $image_max_file_size,
                          'image_min_width' => $image_min_width,
                          'image_max_width' => $image_max_width,
                          'image_min_height' => $image_min_height,
                          'image_max_height' => $image_max_height,
                          'listing_id' => $listing->get_id() ),
                   false );
?>

<script type="text/javascript">
wpbdp.listingSubmit.images.init();
</script>
