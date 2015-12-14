var wpbdp = window.wpbdp || {};
var admin = wpbdp.admin = wpbdp.admin || {};

( function( $ ) {
    var listing = admin.listing = admin.listing || {};

    var images = listing.images = wpbdp.admin.listing.images = {
        init: function() {
            var t = this;

            // Handle image deletes.
            $( '#wpbdp-uploaded-images' ).delegate( '.delete-image', 'click', function( e ) {
                e.preventDefault();
                $.post( $( this ).attr( 'data-action' ), {}, function( res ) {
                    if ( ! res.success )
                        return;

                    $( '#wpbdp-uploaded-images .wpbdp-image[data-imageid="' + res.data.imageId + '"]' ).remove();

                    if ( 0 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length )
                        $( '#no-images-message' ).show();
                }, 'json' );
            } );

            $( '#wpbdp-uploaded-images' ).sortable({
                placeholder: 'wpbdp-image-draggable-highlight',
                    update: function( event, ui ) {
                        var sorted = $( '#wpbdp-uploaded-images' ).sortable( 'toArray', { attribute: "data-imageid" } );
                        var no_images = sorted.length;

                        $.each( sorted, function( i, v ) {
                            $( 'input[name="images_meta[' + v + '][order]"]' ).val( no_images - i );
                        } );
                    }
                });

                // Image upload.
            wpbdp.dnd.setup( $( '#image-upload-dnd-area' ), {
                validate: function( data ) {
                    $( this ).siblings( '.wpbdp-msg' ).remove();
                    return true;
                },
                done: function( res ) {
                    var uploadErrors = ( 'undefined' !== typeof res.data.uploadErrors ) ? res.data.uploadErrors : false;

                    if ( uploadErrors ) {
                        var errorMsg = $( '<div>' ).addClass('wpbdp-msg error').html( '<p>' + res.data.uploadErrors + '</p>' );
                        $( '.area-and-conditions' ).prepend( errorMsg );
                        return;
                    }

                    $( '#no-images-message' ).hide();
                    $( '#wpbdp-uploaded-images' ).append( res.data.html );
                }
            } );
        }
    };

    // Initialization.
    $( document ).ready( function() {
        images.init();
    } );

} )( jQuery );
