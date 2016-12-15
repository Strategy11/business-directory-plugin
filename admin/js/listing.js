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

    jQuery(function( $ ) {
        $( '#wpbdp-listing-metabox-plan-info select[name="listing_plan[fee_id]"]' ).change(function(e) {
            var msg = $( this ).attr( 'data-confirm-text' );
            var plan = $.parseJSON( $( this ).find( 'option:selected' ).attr( 'data-plan-info' ) );

            // var confirm = window.confirm( msg.replace( '%s', plan.label ) );
            //
            // if ( ! confirm )
            //     return;

            var $expiration = $( 'input[name="listing_plan[expiration_date]"]' );
            var $images = $( 'input[name="listing_plan[fee_images]"]' );
            var $sticky = $( 'input[name="listing_plan[is_sticky]"]' );

            $expiration.val( plan.expiration_date );
            $images.val( plan.images );
            $sticky.prop( 'checked', plan.sticky );
        });

        $( '#wpbdp-listing-metabox-plan-info select[name="listing_plan[fee_id]"]').change();
    });

    // Disable Preview button until the first draft is saved
    $( document ).ready( function() {

        var $form = $( 'body.post-type-wpbdp_listing form#post' ),
            post_status = $form.find( '#original_post_status' );

        if ( post_status.length == 0 || post_status.val() != 'auto-draft' ) {
            return;
        }

        $form.find( '#preview-action .button' ).addClass( 'disabled' );

        $form.find( '#minor-publishing' ).tooltip( {
            items: '#preview-action',
            content: WPBDP_admin_listings_config.messages.preview_button_tooltip,
            position: {
                my: "left top+40",
                at: "left bottom",
                collision: "flipfit",
                within: '#minor-publishing'
            }
        } );
    } );

} )( jQuery );
