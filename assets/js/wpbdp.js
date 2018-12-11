if (typeof(window.WPBDP) == 'undefined') {
    window.WPBDP = {};
}

if (typeof(window.wpbdp) == 'undefined') {
    window.wpbdp = {};
}

jQuery(function( $ ) {
    $( '.wpbdp-no-js' ).hide();
});

jQuery(document).ready(function($){
    if ( $( '.wpbdp-js-select2' ).length > 0 ) {
        $( '.wpbdp-js-select2' ).select2();
    }


    /**
     * Handles flex behavior for main box columns.
     * @since 5.0
     */
    wpbdp.main_box = {
        init: function() {
            return;
            this.$box = $( '#wpbdp-main-box' );
            this.$cols = this.$box.find( '.box-col' );
            this.$cols_expanding = this.$cols.filter( '.box-col-expand' );
            this.$cols_fixed = this.$cols.not(this.$cols_expanding);
            var self = this;

            $( window ).on( 'load', function() {
                var max_height = 0;

                // Obtain original width for cols.
                self.$cols.each(function() {
                    $(this).data( 'initial-width', $(this).outerWidth() );
                    max_height = Math.max( max_height, $(this).height() );
                });

                self.$cols.height(max_height);
                self.resize();
            });

            $( window ).on( 'resize', function() {
                self.resize();
            } );
        },

        sum_width: function( $selector, prop ) {
            var prop = ( 'undefined' === typeof( prop ) ) ? 'width' : prop;
            var sum = 0;

            $selector.each(function() {
                var w = 0;

                if ( 'initial' == prop )
                    w = $(this).data( 'initial-width' );
                else if ( 'outer' == prop )
                    w = $(this).outerWidth();
                else if ( 'inner' == prop )
                    w = $(this).innerWidth();
                else
                    w = $(this).width();

                sum += parseInt( w );
            });

            return sum;
        },

        min_width: function() {
            return this.sum_width( this.$cols_fixed, 'initial' );
        },

        should_resize: function() {
            return ( this.$box.find('form').width() > this.min_width() );
        },

        resize: function() {
            if ( ! this.should_resize() )
                return;

            var available_width = this.$box.find('form').innerWidth() - this.min_width();
            var flex_width = Math.floor( available_width / this.$cols_expanding.length ) - 2;

            this.$cols_expanding.each(function() {
                $(this).outerWidth( flex_width );
            });
        }
    };

    if ( $( '#wpbdp-main-box' ).length > 0 )
        wpbdp.main_box.init();

    if ( $('.wpbdp-bar').children().length == 0 && $.trim( $('.wpbdp-bar').text() ) == '' ) {
        $('.wpbdp-bar').remove();
    }

    $( '.wpbdp-listing-contact-form .send-message-button' ).click(function() {
        $( '.wpbdp-listing-contact-form .contact-form-wrapper' ).toggle();
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

            $( window ).on( 'wpbdp_submit_refresh', function( event, submit, section_id ) {
                if ( 'listing_fields' != section_id ) {
                    return;
                }

                t.init();
            } );
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

    resizeIFrame: function(element_id, height) {
        var iframe = jQuery( '#wpbdp-upload-iframe-' + element_id )[0];
        var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;

        if ( iframeWin.document.body ) {
            iframe.height =  height ? height : iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
        }

        if( 0 === jQuery( iframe ).parents( '.wpbdp-social-type-field' ).length ) {
            return;
        }

        if( ! jQuery( iframe ).parent().siblings( '.wpbdp-inner-social-field-option-Other' ).find( 'input' ).is( ':checked' ) ) {
            jQuery( iframe ).parent().hide();
        }

    },

    handleUpload: function(o) {
        var $input = jQuery(o);
        var $form = $input.parent('form');

        $form.submit();
    },

    finishUpload: function(element_id, upload_id, element) {
        var $iframe = jQuery('#wpbdp-upload-iframe-' + element_id);
        // $iframe.contents().find('form').hide();

        var $input = jQuery('input[name="' + element + '"]');
        $input.val(upload_id);

        var $preview = $input.siblings('.preview');
        $preview.find('img').remove();
        $preview.prepend($iframe.contents().find('.preview').html());
        $iframe.contents().find('.preview').remove();
        $iframe.hide();

        $preview.show();
    },

    deleteUpload: function(element_id, element) {
        var $iframe = jQuery('#wpbdp-upload-iframe-' + element_id);
        var $input = jQuery('input[name="' + element + '"]');
        var $preview = $input.siblings('.preview');

        $input.val('');
        $preview.find('img').remove();
        $preview.find('input').val('');

        $preview.hide();
        $iframe.show();

        return false;
    }

};


// {{ Listing submit process.
( function( $ ) {
    var sb = wpbdp.listingSubmit = {
        init: function() {
            if ( $( '.wpbdp-submit-listing-section-listing_images' ).length > 0 )
                sb.images.init();
        },

        init_events: function () {
            $( '#wpbdp-submit-listing' ).on( 'click', '.wpbdp-inner-field-option-select_all', function( e ) {
                var $options = $( this ).parent().find( 'input[type="checkbox"]' );
                $options.prop( 'checked', $( this ).find( 'input' ).is(':checked') );
            } );

            $( '#wpbdp-submit-listing' ).on( 'click', '.wpbdp-inner-social-field-option input', function( e ) {
                var $icon_element = $( this ).parents( '.wpbdp-inner-social-field-option' ).siblings( '.wpbdp-upload-widget' );
                console.log( $icon_element );

                if ( 'Other' !== $( this ).val() ) {
                    $icon_element.hide();
                    return;
                }

                $icon_element.show();
            } );
        }
    };

    var sbImages = sb.images = wpbdp.listingSubmit.images = {
        _initialized: false,
        _admin_nonce: '',
        _slots: 0,
        _slotsRemaining: 0,
        _working: false,

        init: function() {
            this._initialized = true;
            this._admin_nonce = $( '#image-upload-dnd-area' ).attr( 'data-admin-nonce' );

            var t = this;

            // Initialize slot quantities.
            if ( ! this._admin_nonce ) {
                sb.images._slots = parseInt( $( '#image-slots-total' ).text() );
                sb.images._slotsRemaining = parseInt( $( '#image-slots-remaining' ).text() );
            }

            // Handle image deletes.
            $( '#wpbdp-uploaded-images' ).on( 'click', '.wpbdp-image-delete-link', 'click', function( e ) {
                e.preventDefault();
                var url = $( this ).attr('href');

                $.post( url, {}, function( res ) {
                    if ( ! res.success )
                        return;

                    $( '#wpbdp-uploaded-images .wpbdp-image[data-imageid="' + res.data.imageId + '"]' ).fadeOut( function() {
                        $( this ).remove();

                        if ( 1 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length )
                            $( '#wpbdp-uploaded-images .wpbdp-image:first input[name="thumbnail_id"] ').attr( 'checked', 'checked' );

                        if ( ! t._admin_nonce ) {
                            t._slotsRemaining++;
                            $( '#image-slots-remaining' ).text( t._slotsRemaining );
                        }

                        if ( ( t._admin_nonce && 0 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length ) || ( ! t._admin_nonce && t._slotsRemaining == t._slots ) )
                            $( '#no-images-message' ).show();

                        if ( t._admin_nonce || t._slotsRemaining > 0 ) {
                            $( '#image-upload-dnd-area .dnd-area-inside' ).show();
                            $( '#noslots-message' ).hide();
                            $( '#image-upload-dnd-area' ).removeClass('error');
                            $( '#image-upload-dnd-area .dnd-area-inside-error' ).hide();
                        }

                    if ( $( '#wpbdp-listing-fields.postbox' ).length > 0 ) {
                        var $with_count = $( '.wpbdp-admin-tab-nav li a .with-image-count' );
                        var $no_count   = $( '.wpbdp-admin-tab-nav li a .no-image-count' );
                        var n           = $( '#wpbdp-uploaded-images .wpbdp-image' ).length;

                        if ( n ) {
                            $no_count.addClass( 'hidden' );
                            $with_count.removeClass( 'hidden' ).find( 'span' ).text( n );
                        } else {
                            $with_count.addClass( 'hidden' );
                            $no_count.removeClass( 'hidden' );
                        }
                    }

                    } );
                }, 'json' );
            } );

            wpbdp.dnd.setup( $( '#image-upload-dnd-area' ), {
                init: function() {
                    if ( t._admin_nonce || t._slotsRemaining > 0 )
                        return;

                    $( '#image-upload-dnd-area .dnd-area-inside' ).hide();
                    $( '#noslots-message' ).show();
                    $( '#image-upload-dnd-area' ).addClass('error');
                    $( '#image-upload-dnd-area .dnd-area-inside-error' ).show();
                },
                validate: function( data ) {
                    if ( t._admin_nonce )
                        return true;

                    $( this ).siblings( '.wpbdp-msg' ).remove();

                    // if ( t._slotsRemaining < data.files.length ) {
                    //     var errorMsg = $( '<div>' ).addClass('wpbdp-msg error').html( 'Hi there' );
                    //     $( '.area-and-conditions' ).prepend( errorMsg );
                    //
                    //     return false;
                    // }

                    return true;
                },
                done: function( res ) {
                    var uploadErrors = false;

                    if ( ! res.success ) {
                        uploadErrors = [ res.error ];
                    } else {
                        uploadErrors = ( 'undefined' !== typeof res.data.uploadErrors ) ? res.data.uploadErrors : false;
                    }

                    if ( uploadErrors ) {
                        var errorMsg = $( '<div>' ).addClass('wpbdp-msg error').html( uploadErrors );
                        $( '.area-and-conditions' ).prepend( errorMsg );
                        return;
                    }

                    $( '#no-images-message' ).hide();
                    $( '#wpbdp-uploaded-images' ).append( res.data.html );

                    if ( 1 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length ) {
                        $( '#wpbdp-uploaded-images .wpbdp-image:first input[name="thumbnail_id"] ').attr( 'checked', 'checked' );
                    }

                    if ( ! t._admin_nonce ) {
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

                    // On admin, update image count.
                    if ( $( '#wpbdp-listing-fields.postbox' ).length > 0 ) {
                        var $with_count = $( '.wpbdp-admin-tab-nav li a .with-image-count' );
                        var $no_count   = $( '.wpbdp-admin-tab-nav li a .no-image-count' );
                        var n           = $( '#wpbdp-uploaded-images .wpbdp-image' ).length;

                        if ( n ) {
                            $no_count.addClass( 'hidden' );
                            $with_count.removeClass( 'hidden' ).find( 'span' ).text( n );
                        } else {
                            $with_count.addClass( 'hidden' );
                            $no_count.removeClass( 'hidden' );
                        }
                    }
                }
            } );

            $( '#wpbdp-uploaded-images' ).sortable({
                axis: 'y',
                cursor: 'move',
                opacity: 0.9,
                update: function( ev, ui ) {
                    var sorted = $( this ).sortable( 'toArray', { attribute: 'data-imageid' } );
                    var no_images = sorted.length;

                    $.each( sorted, function( i, v ) {
                        $( 'input[name="images_meta[' + v + '][order]"]' ).val( no_images - i );
                    } );
                }
            });
        },
    };

    $( document ).ready( function() {
        if ( 0 == $( '#wpbdp-submit-listing' ).length ) {
            return;
        }

        sb.init_events();

        if ( 0 == $( '.wpbdp-submit-page' ).length )
            return;

        sb.init();
    } );
} )( jQuery );

// }}
