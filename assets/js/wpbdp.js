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
    if ( $( '.wpbdp-js-select2' ).length > 0 && $.fn.selectWoo ) {
        $( '.wpbdp-js-select2' ).selectWoo();
		document.body.classList.add( 'wpbdp-has-select2' );
    }

    // Move the featured badge to the theme h1.
    var sticky = $( '.wpbdp-listing-single .wpbdp-sticky-tag' );
    if ( sticky.length ) {
        $( 'h1:first' ).append( sticky );
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
            prop = ( 'undefined' === typeof( prop ) ) ? 'width' : prop;
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

    if ( $('.wpbdp-bar').children().length == 0 && $('.wpbdp-bar').text().trim() == '' ) {
        $('.wpbdp-bar').remove();
    }

    $( '.wpbdp-listing-contact-form .send-message-button' ).on( 'click', function() {
		$( this ).removeClass( 'wpbdp-show-on-mobile' ).hide();
		$( '.wpbdp-listing-contact-form .contact-form-wrapper' ).removeClass( 'wpbdp-hide-on-mobile' );
    });

    $( '.wpbdp-listings-sort-options select' ).on( 'change', function(e) {
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
                defaultDate: $e.val(),
		        beforeShow: function() {
					$( '#ui-datepicker-div' ).addClass( 'wpbdp-datepicker' );
		        },
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
        $preview.siblings().show();

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
                // console.log( $icon_element );

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

						// Clear thumbnail after delete.
						var thumbInput = $( '#wpbdp-listing-fields-images input#_thumbnail_id' );
						if ( typeof thumbInput !== 'undefined' && ( parseInt( thumbInput.val() ) === res.data.imageId ) ) {
							thumbInput.val( '' );
						}

                        if ( ! t._admin_nonce ) {
                            t._slotsRemaining++;
                            $( '#image-slots-remaining' ).text( t._slotsRemaining );
							if ( t._slotsRemaining < t._slots ) {
								$( '#image-upload-dnd-area' ).show();
							}
                        }

                        if ( ( t._admin_nonce && 0 == $( '#wpbdp-uploaded-images .wpbdp-image' ).length ) || ( ! t._admin_nonce && t._slotsRemaining == t._slots ) )
                            $( '#current-images-header' ).show();

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
                    $( '.image-upload-wrapper .error').remove();
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
                        $( errorMsg ).insertAfter( $( '.area-and-conditions' ) );
                        $( '#image-upload-dnd-area .dnd-area-inside' ).show();
                        return;
                    }

                    $( '#current-images-header' ).hide();
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

            $( 'input#wpbdp_media_manager' ).on( 'click', function( e ) {

                e.preventDefault();
                var image_frame;
                var url = $( this ).attr( 'data-action' );

                if( image_frame ){
                    image_frame.open();
                }
                // Define image_frame as wp.media object
                image_frame = wp.media(
                    {
                        title: 'Select Media',
                        multiple : false,
                        library : {
                            type : 'image',
                        }
                    }
                );

                image_frame.on( 'close', function() {
                    // On close, get selections and save to the hidden input
                    // plus other AJAX stuff to refresh the image preview
                    var selection =  image_frame.state().get( 'selection' );
                    var gallery_ids = new Array();
                    var i = 0;
                    selection.each( function( attachment ) {
                        gallery_ids[i] = attachment['id'];
                        i++;
                    });
                    var ids = gallery_ids.join(",");

                    if ( ! ids ) {
                        return;
                    }

                    $.post( url, { image_ids: ids }, function( res ) {
                        if ( ! res.success ) {
                            errors = [ res.data.errors ];
                        } else {
                            errors = ( 'undefined' !== typeof res.data.errors ) ? res.data.errors : false;
                        }

                        if ( errors ) {
                            var errorMsg = $( '<div>' ).addClass('wpbdp-msg error').html( errors );
                            $( res.data.errorElement ).prepend( errorMsg );
                            return;
                        }

                        $( res.data.errorElement + ' .wpbdp-msg.error' ).remove();

                        if ( 'listing_field' === res.data.source ) {

                            if ( ! res.data.inputElement ) {
                                return;

                            }

                            var $input = $('input[name="' + res.data.inputElement + '"]');
                            $input.val( res.data.media_id );

                            var $preview = $input.siblings('.preview');
                            $preview.find('img').remove();
                            $preview.prepend( res.data.html );

                            $preview.siblings().hide();
                            $preview.show();
                            return;
                        }

                        $( '#current-images-header' ).hide();
                        $( res.data.previewElement ).append( res.data.html );
                    });
                });

                image_frame.open();
            });

            $( '#wpbdp-uploaded-images' ).sortable({
                axis: 'y',
                cursor: 'move',
                opacity: 0.9,
                update: function( ev, ui ) {
                    var sorted = $( this ).sortable( 'toArray', { attribute: 'data-imageid' } ),
						no_images = $( this ).find( '.wpbdp-image' ).length;
                    $.each( sorted, function( i, v ) {
						$( 'input[name="images_meta[' + v + '][order]"]' ).attr( 'value', no_images - i );

						if ( 0 === i ) {
							var thumb = $('input[name="_thumbnail_id"]');
							if ( thumb.length > 0) {
								thumb.val(v) ;
							}
						}
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

/**
 * Advanced Search Modal.
 *
 * @since 6.4
 */
( function ( $ ) {
    var openClass = 'wpbdp-has-modal';
	$html = $( 'html' );
	$body = $( 'body' );

	$( '.wpbdp-advanced-search-link' ).on( 'click', function(event) {
		event.preventDefault();

		$searchPage = $( '.wpbdp-search-page.wpbdp-modal' );

		if ( $searchPage.length > 0 ) {
			$html.toggleClass( openClass );
			return;
		}

		$body.append( '<div class="wpbdp-loader-wrapper"><span class="wpbdp-spinner"></span></div>' );
		var search = location.search.substring(1);
		var qs = parse_query_string(search);
		qs.action = "wpbdp_ajax";
		qs.handler = "search__get_search_content";
		$.ajax( wpbdp_global.ajaxurl, {
			data: qs,
			type: 'POST',
			success: function( response ) {
				$( response.data )
					.addClass( 'wpbdp-modal' )
					.appendTo( $body );

				showResetBtn();

				$html.addClass( openClass );
				$body.find( '.wpbdp-loader-wrapper' ).remove();
			}
		});
	} );

	function showResetBtn() {
		var data, showReset,
			searchTerms = document.getElementById( 'wpdbp-searched-terms' );
		if ( ! searchTerms ) {
			return;
		}

		// Pass along the current search.
		data = searchTerms.getAttribute( 'data-search-terms' );
		data = JSON.parse( data );
		for ( var key in data ) {
			if ( data.hasOwnProperty( key ) ) {
				var input = $( '[name="listingfields[' + key + ']"]' );
				if ( input.length > 0 ) {
					showReset = true;
				}
			}
		}
		if ( showReset ) {
			$( '.wpbdp-modal .reset' ).show();
		}
	}

	$( document ).on( 'click', '.wpbdp-modal-close, .wpbdp-modal-overlay', function() {
		$html.removeClass( openClass );
	} );

	$( document ).on( 'click', '#wpbdp-search-form .reset', function(e) {
		e.preventDefault();
		$( '#wpbdp-search-form [name^="listingfields"][type!="radio"][type!="checkbox"]' ).val('');
		$( '#wpbdp-search-form [type="radio"]' ).attr('checked', false);
		$( '#wpbdp-search-form [type="checkbox"]' ).attr('checked', false);
	} );

} )( jQuery );

/**
 * Zipcode Search
 *
 * @since 6.4
 */
// TODO: After updating to the new design and releasing a new version,
//       we can transfer the Zipcode codes to the ZIP Search add-on.
//       Currently, due to the codes being production-ready,
//       these codes are available here.
function parse_query_string(query) {
	var vars = query.split("&");
	var query_string = {};
	for (var i = 0; i < vars.length; i++) {
		var pair = vars[i].split("=");
		var key = decodeURIComponent(pair.shift());
		var value = decodeURIComponent(pair.join("="));
		// If first entry with this name
		if (typeof query_string[key] === "undefined") {
			query_string[key] = value;
			// If second entry with this name
		} else if (typeof query_string[key] === "string") {
			var arr = [query_string[key], value];
			query_string[key] = arr;
			// If third or later entry with this name
		} else {
			query_string[key].push(value);
		}
	}
	return query_string;
}
