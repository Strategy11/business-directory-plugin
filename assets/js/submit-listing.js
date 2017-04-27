jQuery(function( $ ) {
    $( '#wpbdp-submit-page.step-images #wpbdp-uploaded-images' ).sortable({
        placeholder: 'wpbdp-image-draggable-highlight',
        update: function( event, ui ) {
            var sorted = $( '#wpbdp-uploaded-images' ).sortable( 'toArray', { attribute: "data-imageid" } );
            var no_images = sorted.length;

            $.each( sorted, function( i, v ) {
                $( 'input[name="images_meta[' + v + '][order]"]' ).val( no_images - i );
            } );
        }
    });
});
