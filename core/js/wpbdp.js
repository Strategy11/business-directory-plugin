if (typeof(window.WPBDP) == 'undefined') {
    window.WPBDP = {};
}

if (typeof(window.wpbdp) == 'undefined') {
    window.wpbdp = {};
}

jQuery(document).ready(function($){

    if ( $('.wpbdp-bar').children().length == 0 && $.trim( $('.wpbdp-bar').text() ) == '' ) {
        $('.wpbdp-bar').remove();
    }

    $( '.wpbdp-listing .contact-form .send-message-button' ).click(function() {
        $( '.contact-form .contact-form-wrapper' ).toggle();
    });

    $( '.wpbdp-listings-sort-options.wpbdp-show-on-mobile select' ).change(function(e) {
        var selected = $(this).val();
        location.href = selected;
    });
});

jQuery(function( $ ) {

    var form_fields = {
        init: function() {
            var t = this;

            $( '.wpbdp-form-field-type-date' ).each(function(i, v) {
                t.configure_date_picker( $(v).find( 'input' ) );
            });
        },

        configure_date_picker: function( $e ) {
            $e.datepicker({
                dateFormat: $e.attr( 'data-date-format' ),
                defaultDate: $e.val()
            });
        }
    };

    form_fields.init();
});

WPBDP.fileUpload = {

    resizeIFrame: function(field_id, height) {
        var iframe = jQuery( '#wpbdp-upload-iframe-' + field_id )[0];
        var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;
        
        if ( iframeWin.document.body ) {
            iframe.height = iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
        }
    },

    handleUpload: function(o) {
        var $input = jQuery(o);
        var $form = $input.parent('form');

        $form.submit();
    },

    finishUpload: function(field_id, upload_id) {
        var $iframe = jQuery('#wpbdp-upload-iframe-' + field_id);
        // $iframe.contents().find('form').hide();

        var $input = jQuery('input[name="listingfields[' + field_id + ']"]');
        $input.val(upload_id);

        var $preview = $input.siblings('.preview');
        $preview.find('img').remove();
        $preview.prepend($iframe.contents().find('.preview').html());
        $iframe.contents().find('.preview').remove();

        $preview.find('.delete').show();
    },

    deleteUpload: function(field_id) {
        var $input = jQuery('input[name="listingfields[' + field_id + ']"]');
        var $preview = $input.siblings('.preview');

        $input.val('');
        $preview.find('img').remove();

        $preview.find('.delete').hide();
        
        return false;
    }

};


// {{ Listing submit process.
( function( $ ) {
    var sb = wpbdp.listingSubmit = {
        init: function() {
            if ( $( '.wpbdp-submit-page.step-fee-selection' ).length > 0 ) {
                $( '#wpbdp-listing-form-fees .fee-selection input' ).change(function( e ) {
                    console.log(this);
                    if ( 1 == $( this ).attr( 'data-canrecur' ) ) {
                        if ( $( '.make-charges-recurring-option' ).not( ':visible' ) )
                            $( '.make-charges-recurring-option' ).fadeIn( 'fast' );
                    } else {
                        $( '.make-charges-recurring-option' ).fadeOut( 'fast' );
                    }
                }).filter( ':checked' ).trigger( 'change' );
            }

            if ( $( '.wpbdp-submit-page.step-images' ).length > 0 )
                sb.images.init();
        }
    };

    var sbImages = sb.images = wpbdp.listingSubmit.images = {
        _slots: 0,
        _slotsRemaining: 0,
        _working: false,

        init: function() {
            var t = this;

            // Initialize slot quantities.
            sb.images._slots = parseInt( $( '#image-slots-total' ).text() );
            sb.images._slotsRemaining = parseInt( $( '#image-slots-remaining' ).text() );

            // Handle image deletes.
            $( '#wpbdp-uploaded-images' ).delegate( '.delete-image', 'click', function( e ) {
                e.preventDefault();
                var url = $( this ).attr('data-action');

                $.post( url, { 'state': $( 'form#wpbdp-listing-form-images input[name="_state"]' ).val() }, function( res ) {
                    if ( ! res.success )
                        return;

                    $( '#wpbdp-uploaded-images .wpbdp-image[data-imageid="' + res.data.imageId + '"]' ).fadeOut( function() {
                        $( this ).remove();

                        if ( 1 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length )
                            $( '#wpbdp-uploaded-images .wpbdp-image:first input[name="thumbnail_id"] ').attr( 'checked', 'checked' );

                        t._slotsRemaining++;
                        $( '#image-slots-remaining' ).text( t._slotsRemaining );

                        if ( t._slotsRemaining == t._slots )
                            $( '#no-images-message' ).show();

                        if (  t._slotsRemaining > 0 ) {
                            $( '#image-upload-dnd-area .dnd-area-inside' ).show();
                            $( '#noslots-message' ).hide();
                            $( '#image-upload-dnd-area' ).removeClass('error');
                            $( '#image-upload-dnd-area .dnd-area-inside-error' ).hide();
                        }

                    } );
                }, 'json' );
            } );

            wpbdp.dnd.setup( $( '#image-upload-dnd-area' ), {
                init: function() {
                    if ( t._slotsRemaining > 0 )
                        return;

                    $( '#image-upload-dnd-area .dnd-area-inside' ).hide();
                    $( '#noslots-message' ).show();
                    $( '#image-upload-dnd-area' ).addClass('error');
                    $( '#image-upload-dnd-area .dnd-area-inside-error' ).show();
                },
                validate: function( data ) {
                    $( this ).siblings( '.wpbdp-msg' ).remove();
                    return ( t._slotsRemaining - data.files.length ) >= 0;
                },
                done: function( res ) {
                    var uploadErrors = ( 'undefined' !== typeof res.data.uploadErrors ) ? res.data.uploadErrors : false;

                    if ( uploadErrors ) {
                        var errorMsg = $( '<div>' ).addClass('wpbdp-msg error').html( res.data.uploadErrors );
                        $( '.area-and-conditions' ).prepend( errorMsg );
                        return;
                    }

                    $( '#no-images-message' ).hide();
                    $( '#wpbdp-uploaded-images' ).append( res.data.html );

                    if ( 1 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length ) {
                        $( '#wpbdp-uploaded-images .wpbdp-image:first input[name="thumbnail_id"] ').attr( 'checked', 'checked' );
                    }

                    t._slotsRemaining -= res.data.attachmentIds.length;
                    $( '#image-slots-remaining' ).text( t._slotsRemaining );

                    if ( 0 == t._slotsRemaining ) {
                        $( '#image-upload-dnd-area .dnd-area-inside' ).hide();
                        $( '#noslots-message' ).show();
                        $( '#image-upload-dnd-area' ).addClass('error');
                        $( '#image-upload-dnd-area .dnd-area-inside' ).hide();
                        $( '#image-upload-dnd-area .dnd-area-inside-error' ).show();
                    }
                }
            } );
        },
    };

    $( document ).ready( function() {
        if ( 0 == $( '.wpbdp-submit-page' ).length )
            return;

        sb.init();
    } );
} )( jQuery );

// }}
