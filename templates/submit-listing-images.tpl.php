<?php
$admin = isset( $admin ) ? $admin : false;
?>
<?php if ( ! $admin ): ?>
<div id="current-images-header" style="<?php echo ( ! $images ? 'display: none;' : '' ); ?>">
    <?php esc_html_e( 'Current Images', 'business-directory-plugin' ); ?>
	<span class="wpbdp-setting-tooltip wpbdp-tooltip dashicons dashicons-warning" title="<?php esc_attr_e( 'Drag and drop to reorder your images', 'business-directory-plugin' ); ?>"></span>
</div>
<input type="hidden" id="_thumbnail_id" name="_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>"/>
<?php endif; ?>

<div id="wpbdp-uploaded-images" class="cf">

<?php
foreach ( $images as $image ):
    $image_id = $image;

    if ( is_object( $image ) && $image->id ) :
        $image_id = $image->id;
    endif;

    $vars = array(
        'image'        => $image,
        'listing_id'   => $listing->get_id(),
        'is_thumbnail' => ( 1 == count( $images ) || $thumbnail_id == $image_id ),
		'admin'        => $admin,
        'echo'         => true,
    );
    if ( ! $admin ):
        $vars['image_id'] = $image_id;
        $vars['weight']   = $images_meta[ $image_id ]['order'];
        $vars['caption']  = $images_meta[ $image_id ]['caption'];
    endif;

    wpbdp_render( 'submit-listing-images-single', $vars, false );
endforeach;
?>
</div>

<?php
if ( $admin ) :
    $vars = array( 'admin' => true, 'listing_id' => $listing->get_id() );
else :
    $conditions = array();

    if ( $image_min_file_size || $image_max_file_size ) :
        $conditions[] = sprintf(
            '%1$s: %2$s - %3$s',
            esc_html_x( 'File size', 'templates', 'business-directory-plugin' ),
            esc_html( $image_min_file_size ),
            $image_max_file_size ? esc_html( $image_max_file_size ) : esc_html_x( 'No limit', 'templates', 'business-directory-plugin' )
        );
    endif;
    if ( $image_min_width || $image_max_width ) :
        $conditions[] = sprintf(
            '%1$s: %2$s - %3$s',
            esc_html_x( 'Image width', 'templates', 'business-directory-plugin' ),
            esc_html( $image_min_width ) . 'px',
            $image_max_width ? esc_html( $image_max_width ) . 'px' : esc_html_x( 'No limit', 'templates', 'business-directory-plugin' )
        );
    endif;
    if ( $image_min_height || $image_max_height ) :
        $conditions[] = sprintf(
            '%1$s: %2$s - %3$s',
            esc_html_x( 'Image height', 'templates', 'business-directory-plugin' ),
            esc_html( $image_min_height ) . 'px',
            $image_max_height ? esc_html( $image_max_height ) . 'px' : esc_html_x( 'No limit', 'templates', 'business-directory-plugin' )
        );
    endif;

    $vars = array(
        'slots_available' => $image_slots_remaining,
        'slots'           => $image_slots,
        'conditions'      => $conditions,
        'listing_id'      => $listing->get_id()
    );
endif;
$vars['echo'] = true;
wpbdp_render( 'submit-listing-images-upload-form', $vars, false );
?>

<script type="text/javascript">
wpbdp.listingSubmit.images.init();
</script>
