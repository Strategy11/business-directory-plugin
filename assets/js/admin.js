var WPBDP_associations_fieldtypes = {};

(function($) {

    /* Form Fields */
    var WPBDPAdmin_FormFields = {
        $f_association: null,
        $f_fieldtype: null,

        init: function() {
            WPBDPAdmin_FormFields.$f_association = $('form#wpbdp-formfield-form select#field-association');
            WPBDPAdmin_FormFields.$f_association.change( WPBDPAdmin_FormFields.onAssociationChange );

            WPBDPAdmin_FormFields.$f_fieldtype = $('form#wpbdp-formfield-form select#field-type');
            WPBDPAdmin_FormFields.$f_fieldtype.change( WPBDPAdmin_FormFields.onFieldTypeChange );

            WPBDPAdmin_FormFields.$f_validator = $( 'form#wpbdp-formfield-form select#field-validator' );
            WPBDPAdmin_FormFields.$f_validator.change( WPBDPAdmin_FormFields.onFieldValidatorChange );

            $( '#wpbdp-fieldsettings .iframe-confirm a' ).click(function(e) {
                e.preventDefault();

                if ( $( this ).hasClass( 'yes' ) ) {
                    $( this ).parents( '.iframe-confirm' ).hide();
                } else {
                    $( '#wpbdp-fieldsettings input[name="field[allow_iframes]"]' ).prop( 'checked', false );
                    $( this ).parents( '.iframe-confirm' ).hide();
                }
            });

            $( '#wpbdp-fieldsettings input[name="field[allow_iframes]"]' ).change(function() {
                if ( $( this ).is(':checked') ) {
                    $( '.iframe-confirm' ).show();
                } else {
                    $( '.iframe-confirm' ).hide();
                }
            });

            $( '#wpbdp-formfield-form input[name="field[display_flags][]"][value="search"]' ).change(function(){
                $( '.if-display-in-search' ).toggle( $( this ).is( ':checked' ) );
            });

            $('table.formfields tbody').sortable({
                placeholder: 'wpbdp-draggable-highlight',
                handle: '.wpbdp-drag-handle',
                axis: 'y',
                cursor: 'move',
                opacity: 0.9,
                update: function( event, ui ) {
                    var sorted_items = [];
                    $( this ).find( '.wpbdp-drag-handle' ).each( function( i, v ) {
                        sorted_items.push( $( v ).attr('data-field-id') );
                    } );

                    if ( sorted_items )
                        $.post( ajaxurl, { 'action': 'wpbdp-formfields-reorder', 'order': sorted_items } );
                }
            });

            $( '#wpbdp-formfield-form select[name="limit_categories"]' ).change( function(){
                var form = $( this ).parents( 'form' ).find( '#limit-categories-list' );
                if ( $( this ).val() === "1" ) {
                    form.removeClass( 'hidden' );
                } else {
                    form.addClass( 'hidden' );
                }
            });
        },

        onFieldTypeChange: function() {
            var $field_type = $(this).find('option:selected');

            if ( !$field_type.length )
                return;

            var field_type = $field_type.val();

            $( 'select#field-validator' ).prop( 'disabled', false );

            // URL fields can only have the 'url' validator.
            if ( 'url' == field_type ) {
                $( 'select#field-validator option' ).not( '[value="url"]' ).prop( 'disabled', true ).prop( 'selected', false );
                $( 'select#field-validator option[value="url"]' ).prop( 'selected', true );
            } else {
                $( 'select#field-validator option' ).prop( 'disabled', false );
            }

            // Twitter fields can not have a validator.
            if ( 'social-twitter' == field_type ) {
                $( 'select#field-validator' ).prop( 'disabled', true );
            }

            var request_data = {
                action: "wpbdp-renderfieldsettings",
                association: WPBDPAdmin_FormFields.$f_association.find('option:selected').val(),
                field_type: field_type,
                field_id: $('#wpbdp-formfield-form input[name="field[id]"]').val()
            };

            $.post( ajaxurl, request_data, function(response) {
                if ( response.ok && response.html ) {
                    $('#wpbdp-fieldsettings-html').html(response.html);
                    $('#wpbdp-fieldsettings').show();
                } else {
                    $('#wpbdp-fieldsettings-html').empty();
                    $('#wpbdp-fieldsettings').hide();
                }
            }, 'json' );

            WPBDPAdmin_FormFields.onFieldValidatorChange();
        },

        onAssociationChange: function() {
            $f_fieldtype = WPBDPAdmin_FormFields.$f_fieldtype;

            var association = $(this).val();
            var valid_types = WPBDP_associations_fieldtypes[ association ];
            var private_option = $( '#wpbdp_private_field' );

            $f_fieldtype.find('option').prop('disabled', false);

            $f_fieldtype.find('option').each(function(i,v){
                if ( $.inArray( $(v).val(), valid_types ) < 0 ) {
                    $(v).prop('disabled', true);
                }
            });

            $f_fieldtype.change();

            if ( 0 <= [ 'title', 'content', 'category'].indexOf( association ) ) {
                private_option.find( 'input' ).prop( 'disabled', true );
                private_option.hide();
            } else {
                private_option.find( 'input' ).prop( 'disabled', false );
                private_option.show();
            }

            var form = $(this).parents('form').find( '.limit-categories' );

            if ( 0 <= ['title', 'category'].indexOf( association ) ) {
                form.addClass( 'hidden' );
            } else {
                form.removeClass( 'hidden' );
            }
        },

        onFieldValidatorChange: function() {
            var $field_validator = $(this).find('option:selected');
            var field_type = WPBDPAdmin_FormFields.$f_fieldtype.find( 'option:selected' ).val();

            if ('textfield' === field_type || 'textarea' === field_type) {
                if ( 'word_number' ===  $field_validator.val() ) {
                    $('#wpbdp_word_count').show();
                    $('select#field-validator option[value="word_number"]').prop( 'disabled', false );
                } else {
                    $('#wpbdp_word_count').hide();
                }
            } else {
                $('#wpbdp_word_count').hide();
                $('select#field-validator option[value="word_number"]').prop( 'disabled', true ).prop( 'selected', false );
            }
        }
    };

    /**
     * Admin layout
     */
    var WPBDPAdmin_Layout = {
        $nav_toggle : null,
        $layout_container : null,
        $menu_items : null,
        $menu_container : null,
        $content_area : null,
        $menu_state : null,

        init: function() {
            WPBDPAdmin_Layout.$nav_toggle = $( '.wpbdp-nav-toggle' );
            WPBDPAdmin_Layout.$layout_container = $( '.wpbdp-admin-row' );
            WPBDPAdmin_Layout.$menu_items = WPBDPAdmin_Layout.$layout_container.find( '.wpbdp-nav-item a' );
            WPBDPAdmin_Layout.$menu_container = WPBDPAdmin_Layout.$layout_container.find( '.wpbdp-menu-area' );
            WPBDPAdmin_Layout.$content_area = WPBDPAdmin_Layout.$layout_container.find( '.wpbdp-content-area' );
            WPBDPAdmin_Layout.$menu_state = window.localStorage.getItem( '_wpbdp_admin_menu' );
            WPBDPAdmin_Layout.$nav_toggle.click( WPBDPAdmin_Layout.onNavToggle );
            WPBDPAdmin_Layout.layoutResize();
            WPBDPAdmin_Layout.initTaxonomyModal();
            $(window).on('resize', function(){
                WPBDPAdmin_Layout.layoutResize();
            });
            if ( WPBDPAdmin_Layout.$menu_state && WPBDPAdmin_Layout.$menu_state == 'minimized' ) {
                WPBDPAdmin_Layout.$layout_container.addClass( 'minimized' );
            }
        },

        onNavToggle: function( e ) {
            e.preventDefault();
            WPBDPAdmin_Layout.$layout_container.toggleClass( 'minimized' );
            if ( WPBDPAdmin_Layout.$layout_container.hasClass( 'minimized' ) ) {
                window.localStorage.setItem( '_wpbdp_admin_menu', 'minimized' );
                WPBDPAdmin_Layout.$menu_items.addClass( 'wpbdp-nav-tooltip' );
            } else {
                window.localStorage.removeItem( '_wpbdp_admin_menu' );
                WPBDPAdmin_Layout.$menu_items.removeClass( 'wpbdp-nav-tooltip' );
            }
        },

        layoutResize : function() {
            WPBDPAdmin_Layout.$content_area.css( 'height', $( document ).height() );
            if ( window.matchMedia( 'screen and (max-width: 768px)' ).matches ) {
                WPBDPAdmin_Layout.$menu_container.css( 'height', $( window ).height() );
            } else {
                WPBDPAdmin_Layout.$menu_container.css( 'height', 'auto' );
            }
        },

        initTaxonomyModal : function() {
            var modal = WPBDPAdmin_Layout.initModal( '#wpbdp-add-taxonomy-form' );
            if ( modal === false ) {
                return;
            }
            $( document ).on( 'click', '.wpbdp-add-taxonomy-form', function( e ) {
                e.preventDefault();
                $( '#wpbdp-add-taxonomy-form .term-slug-wrap' ).addClass( 'hidden' );
                $( '#wpbdp-add-taxonomy-form .term-description-wrap' ).addClass( 'hidden' );
                modal.dialog( 'open' );
            })
        },

        initModal : function( id, width ) {
            var $info = $( id );
            if ( $info.length < 1 ) {
                return false;
            }

            if ( typeof width === 'undefined' ) {
                width = '550px';
            }

            $info.dialog({
                dialogClass: 'wpbdp-admin-dialog',
                modal: true,
                autoOpen: false,
                closeOnEscape: true,
                width: width,
                resizable: false,
                draggable: false,
                open: function() {
                    $( '.ui-dialog-titlebar' ).addClass( 'hidden' ).removeClass( 'ui-helper-clearfix' );
                    $( '#wpwrap' ).addClass( 'wpbdp-overlay' );
                    $( '.ui-widget-overlay' ).addClass( 'wpbdp-modal-overlay' );
                    $( '.wpbdp-admin-dialog' ).removeClass( 'ui-widget ui-widget-content ui-corner-all' );
                    $info.removeClass( 'ui-dialog-content ui-widget-content' );
                    WPBDPAdmin_Layout.onCloseModal( $info );
                },
                close: function() {
                    $( '#wpwrap' ).removeClass( 'wpbdp-overlay' );
                    $( '.ui-widget-overlay' ).removeClass( 'wpbdp-modal-overlay' );
                    $( '.spinner' ).css( 'visibility', 'hidden' );
    
                    this.removeAttribute( 'data-option-type' );
                    const optionType = document.getElementById( 'bulk-option-type' );
                    if ( optionType ) {
                        optionType.value = '';
                    }
                }
            });
    
            return $info;
        },

        onCloseModal : function ( $modal ) {
            const closeModal = function( e ) {
                e.preventDefault();
                $modal.dialog( 'close' );
            };
            $( '.ui-widget-overlay' ).on( 'click', closeModal );
            $modal.on( 'click', 'a.dismiss, .dismiss-button', closeModal );
        }
    };

	/**
	 * Tables handle checked states
	 */
	var WPBDPAdmin_Tables = {
		$bodyCheckboxes : null,
		$selectAllCheckboxes : null,

		init: function() {
			WPBDPAdmin_Tables.$bodyCheckboxes = $( '.wpbdp-admin-page .wp-list-table tbody .check-column input[type="checkbox"]' );
			WPBDPAdmin_Tables.$selectAllCheckboxes = $( '.wpbdp-admin-page .wp-list-table thead .check-column input[type="checkbox"], .wpbdp-admin-page .wp-list-table tfoot .check-column input[type="checkbox"]' );
			WPBDPAdmin_Tables.checkBoxToggle();
			WPBDPAdmin_Tables.selectAllChecked();
		},

		checkBoxToggle : function() {
			WPBDPAdmin_Tables.$bodyCheckboxes.on( 'click', function(){
				WPBDPAdmin_Tables.handleClick( $( this ) );
			});
		},

		selectAllChecked : function() {
			WPBDPAdmin_Tables.$selectAllCheckboxes.on( 'click',  function() {
				var checked = $( this ).is( ':checked' );
				WPBDPAdmin_Tables.$bodyCheckboxes.each( function() {
					WPBDPAdmin_Tables.handleClick( $( this ), checked );
				});
			});
		},

		handleClick : function( checkbox, checked ) {
			var $parent = checkbox.closest( 'tr' );
			if ( typeof checked === 'undefined') {
				checked = checkbox.is( ':checked' );
			}
			WPBDPAdmin_Tables.toggleClass( checked, $parent );
		},

		toggleClass : function( checked, $parent ) {
			if ( checked ) {
				$parent.addClass( 'wpbdp-row-selected' );
			} else {
				$parent.removeClass( 'wpbdp-row-selected' );
			}
		}
	}

    $(document).ready(function(){
        WPBDPAdmin_FormFields.init();
        WPBDPAdmin_Layout.init();
		WPBDPAdmin_Tables.init();
    });
})(jQuery);


jQuery(document).ready(function($){

    // {{ Manage Fees.
    $('.wpbdp-admin-page-fees .wp-list-table.fees tbody').sortable({
        placeholder: 'wpbdp-draggable-highlight',
        handle: '.wpbdp-drag-handle',
        axis: 'y',
        cursor: 'move',
        opacity: 0.9,
        update: function( event, ui ) {
            var rel_rows = $( '.free-fee-related-tr' ).remove();
            $( 'tr.free-fee' ).after( rel_rows );

            var sorted_items = [];
            $( this ).find( '.wpbdp-drag-handle' ).each( function( i, v ) {
                sorted_items.push( $( v ).attr('data-fee-id') );
            } );

            if ( sorted_items )
                $.post( ajaxurl, { 'action': 'wpbdp-admin-fees-reorder', 'order': sorted_items } );
        }
    });

	$( document ).on( 'click', '.fee-order-submit', function( e ) {
		e.preventDefault();
		$.ajax({
			url: ajaxurl,
			data: $(this).parent('form').serialize(),
			dataType: 'json',
			type: 'POST',
			success: function(res) {
				if ( res.success )
					location.reload();
			}
		});
	});

    if ( 'custom' == $('select[name="fee_order[method]"]').val() ) {
        $( '.wpbdp-admin-page-fees .wp-list-table .wpbdp-drag-handle' ).show();
    }
    // }}


    /* Ajax placeholders */

    $('.wpbdp-ajax-placeholder').each(function(i,v){
        wpbdp_load_placeholder($(v));
    });

    /*
     * Admin bulk actions
     */

    $('input#doaction, input#doaction2').click(function(e) {
        var action_name = ( 'doaction' == $(this).attr('id') ) ? 'action' : 'action2';
        var $selected_option = $('select[name="' + action_name + '"] option:selected');
        var action_val = $selected_option.val();

        if (action_val.split('-')[0] == 'listing') {
            var action = action_val.split('-')[1];

            if (action != 'sep0' && action != 'sep1' && action != 'sep2') {
                var $checked_posts = $('input[name="post[]"]:checked');
                var uri = $selected_option.attr('data-uri');

                $checked_posts.each(function(i,v){
                    uri += '&post[]=' + $(v).val();
                });

                window.location.href = uri;

                return false;
            }
        }

        return true;
    });

    /* Form fields form preview */
    $('.wpbdp-admin.wpbdp-page-formfields-preview form input[type="submit"]').click(function(e){
        e.preventDefault();
        alert('This form is just a preview. It doesn\'t work.');
    });

    /* Debug info page */
    $('#wpbdp-admin-debug-info-page a.nav-tab').click(function(e){
        e.preventDefault();

        $('#wpbdp-admin-debug-info-page a.nav-tab').not(this).removeClass('nav-tab-active');

        var $selected_tab = $(this);
        $selected_tab.addClass( 'nav-tab-active' );

        $( '.wpbdp-debug-section' ).hide();
        $( '.wpbdp-debug-section[data-id="' + $(this).attr('href') + '"]' ).show();
    });

    if ( $('#wpbdp-admin-debug-info-page a.nav-tab').length > 0 )
        $('#wpbdp-admin-debug-info-page a.nav-tab').get(0).click();

    /* Transactions */
    $( '.wpbdp-page-admin-transactions .column-actions a.details-link' ).click(function(e){
        e.preventDefault();
        var $tr = $(this).parents('tr');
        var $details = $tr.find('div.more-details');

        var $tr_details = $tr.next('tr.more-details-row');
        if ( $tr_details.length > 0 ) {
            $tr_details.remove();
            $(this).text( $(this).text().replace( '-', '+' ) );
            return;
        } else {
            $(this).text( $(this).text().replace( '+', '-' ) );
        }

        $tr.after( '<tr class="more-details-row"><td colspan="7">' + $details.html() + '</td></tr>' ).show();
    });

	$( document ).on( 'change', '.wpbdp-inner-file', function(){
		var files = $(this)[0].files[0];
		if ( files ) {
			$(this).parent().find('.wpbdp-inner-file-name').html(files.name);
		}
	});

});

function wpbdp_load_placeholder($v) {
    var action = $v.attr('data-action');
    var post_id = $v.attr('data-post_id');
    var baseurl = $v.attr('data-baseurl');

    $v.load(ajaxurl, {"action": action, "post_id": post_id, "baseurl": baseurl});
}


var WPBDP_Admin = {};
WPBDP_Admin.payments = {};

// TODO: integrate this into $.
WPBDP_Admin.ProgressBar = function($item, settings) {
    $item.empty();
    $item.html('<div class="wpbdp-progress-bar"><span class="progress-text">0%</span><div class="progress-bar"><div class="progress-bar-outer"><div class="progress-bar-inner" style="width: 0%;"></div></div></div>');

    this.$item = $item;
    this.$text = $item.find('.progress-text');
    this.$bar = $item.find('.progress-bar');

    this.set = function( completed, total ) {
        var pcg = Math.round( 100 * parseInt( completed) / parseInt( total ) );
        this.$text.text(pcg + '%');
        this.$bar.find('.progress-bar-inner').attr('style', 'width: ' + pcg + '%;');
    };
};

(function($) {
    WPBDP_Admin.dialog = {};
    var dialog = WPBDP_Admin.dialog;

        // if ($('#wpbdp-modal-dialog').length == 0) {
        //     $('body').append($('<div id="wpbdp-modal-dialog"></div>'));
        // }
})(jQuery);



(function($) {
    var payments = WPBDP_Admin.payments;

    payments._initialize = function() {
        $('#BusinessDirectory_listinginfo a.payment-details-link').click(function(e) {
            e.preventDefault();
            payments.viewPaymentDetails( $(this).attr('data-id') );
        });

        if ($('#wpbdp-modal-dialog').length === 0) {
            $('body').append($('<div id="wpbdp-modal-dialog"></div>'));
        }
    };

    payments.viewPaymentDetails = function(id) {
        $.get( ajaxurl, { 'action': 'wpbdp-payment-details', 'id': id }, function(res) {
            if (res && res.success) {
                if ($('#wpbdp-modal-dialog').length === 0) {
                    $('body').append($('<div id="wpbdp-modal-dialog"></div>'));
                }

                $('#wpbdp-modal-dialog').html(res.data.html);
                tb_show('', '#TB_inline?inlineId=wpbdp-modal-dialog');

                // Workaround WP bug https://core.trac.wordpress.org/ticket/27473.
                $( '#TB_window' ).width( $( '#TB_ajaxContent' ).outerWidth() );

                if ( $( '#TB_window' ).height() > $( '#TB_ajaxContent' ).outerHeight() )
                    $( '#TB_ajaxContent' ).height( $( '#TB_window' ).height() );

                $('#wpbdp-modal-dialog').remove();
            }
        }, 'json' );
    };

    // Initialize payments.
    $(document).ready(function(){ payments._initialize(); });

})(jQuery);

/* {{ Settings. */
(function($) {
    var s = WPBDP_Admin.settings = {
        init: function() {
            var t = this;

            $( '#wpbdp-settings-quick-search-fields' ).on( 'change', ':checkbox', function() {
                var $container = $( '#wpbdp-settings-quick-search-fields' );
                var text_fields = $container.data( 'text-fields' );
                var selected = $container.find( ':checkbox:checked' ).map(function(){ return parseInt($(this).val()); }).get();
                var show_warning = false;

                if ( selected.length > 0 && text_fields.length > 0 ) {
                    for ( var i = 0; i < text_fields.length; i++ ) {
                        if ( $.inArray( text_fields[i], selected ) > -1 ) {
                            show_warning = true;
                            break;
                        }
                    }
                }

                if ( show_warning ) {
                    $( '#wpbdp-settings-quick-search-fields .text-fields-warning' ).fadeIn( 'fast' );
                } else {
                    $( '#wpbdp-settings-quick-search-fields .text-fields-warning' ).fadeOut( 'fast' );
                }
            });

            $( '#wpbdp-settings-currency select' ).on( 'change', function () {
                if ( 'AED' === $( this ).val() ) {
                    $( '#wpbdp-settings-currency .wpbdp-setting-description' ).show();
                } else {
                    $( '#wpbdp-settings-currency .wpbdp-setting-description' ).hide();
                }
            } );

            $( '#wpbdp-settings-currency select' ).change();
        }
    };

    $(document).ready(function(){
        if ( $( '#wpbdp-admin-page-settings' ).length > 0 ) {
            s.init();
        }
    });
})(jQuery);
/* }} */

/* {{ Uninstall. */
jQuery(function($) {
    if ( 0 === $( '.wpbdp-admin-page-uninstall' ).length ) {
        return;
    }

    var $warnings = $( '#wpbdp-uninstall-messages' );
    var $confirm_button = $( '#wpbdp-uninstall-proceed-btn' );
    var $form = $( '#wpbdp-uninstall-capture-form' );

    $confirm_button.click(function(e) {
        e.preventDefault();
        $warnings.fadeOut( 'fast', function() {
            $form.fadeIn( 'fast' );
        } );
    });
    
    $form.submit(function() {
        var $no_reason_error = $( '.wpbdp-validation-error.no-reason' ).hide();
        var $no_text_error   = $( '.wpbdp-validation-error.no-reason-text' ).hide();
        var $reason_checked = $( 'input[name="uninstall[reason_id]"]:checked' );

        if ( 0 === $reason_checked.length ) {
            $no_reason_error.show();
            return false;
        }

        if ( '0' == $reason_checked.val() ) {
            var $reason_text = $( 'textarea[name="uninstall[reason_text]"]' );
            var reason_text = $.trim( $reason_text.val() );

            $reason_text.removeClass( 'invalid' );

            if ( ! reason_text ) {
                $no_text_error.show();
                $reason_text.addClass( 'invalid' );

                return false;
            }
        }

        return true;
    });

    $( 'form#wpbdp-uninstall-capture-form input[name="uninstall[reason_id]"]' ).change(function(e) {
        var val = $(this).val();

        if ( '0' == val ) {
            $( 'form#wpbdp-uninstall-capture-form .custom-reason' ).fadeIn();
        } else {
            $( 'form#wpbdp-uninstall-capture-form .custom-reason' ).fadeOut( 'fast', function() {
                $(this).val('');
            } );
        }
    });
    

});

// {{ Widgets.
(function($) {
    $(document).ready(function() {
        if ( $('body.wp-admin.widgets-php').length === 0 ) {
            return;
        }

        $( 'body.wp-admin.widgets-php' ).on( 'change', 'input.wpbdp-toggle-images', function() {
            var checked = $(this).is(':checked');

            if ( checked ) {
                $(this).parents('.widget').find('.thumbnail-width-config').fadeIn('fast');
            } else {
                $(this).parents('.widget').find('.thumbnail-width-config').fadeOut('fast');
            }
        });

    });
})(jQuery);
// }}

// {{ Create main page warning.
(function($) {
    $(document).ready(function() {
        $( 'a.wpbdp-create-main-page-button' ).click(function(e) {
            e.preventDefault();
            var $msg = $(this).parents('div.error');

            $.ajax({
                'url': ajaxurl,
                'data': { 'action': 'wpbdp-create-main-page',
                          '_wpnonce': $(this).attr('data-nonce') },
                'dataType': 'json',
                success: function(res) {
                    if ( ! res.success )
                        return;

                    $msg.fadeOut( 'fast', function() {
                        $(this).html( '<p>' + res.message + '</p>' );
                        $(this).removeClass('error');
                        $(this).addClass('updated');
                        $(this).fadeIn( 'fast' );
                    } );
                }
            });
        });
    });
})(jQuery);
// }}

// Dismissible Messages
(function($) {
    $(function(){
        var dismissNotice = function( $notice, $button ) {
            $.post( ajaxurl, {
                action: 'wpbdp_dismiss_notification',
                id: $button.data( 'dismissible-id' ),
                nonce: $button.data( 'nonce' )
            }, function() {
                $notice.fadeOut( 'fast', function(){ 
                    $notice.remove();
                } );
            } );
        };

		$( '#wpbody-content' ).on( 'click', '.wpbdp-notice.is-dismissible > .notice-dismiss, .wpbdp-notice .wpbdp-notice-dismiss', function( e ) {
			e.preventDefault();
			var $button = $( this ),
				$notice = $button.closest( '.wpbdp-notice' ),
				link = $button.attr( 'href' );

			if ( link ) {
				window.open( link, '_blank').focus();
			}
			dismissNotice( $notice, $button );
		});
	});
})(jQuery);

// Install addons
function wpbdpAddons() {
	function activateAddon( e ) {
		e.preventDefault();
		installOrActivate( this, 'wpbdp_activate_addon' );
	}

	function installAddon( e ) {
		e.preventDefault();
		installOrActivate( this, 'wpbdp_install_addon' );
	}

	function installOrActivate( clicked, action ) {
		// Remove any leftover error messages, output an icon and get the plugin basename that needs to be activated.
		jQuery( '.wpbdp-addon-error' ).remove();
		var button = jQuery( clicked );
		var plugin = button.attr( 'rel' );
		var el = button.parent();
		var message = el.parent().find( '.addon-status-label' );

		button.addClass( 'wpbdp-loading-button' );

		// Process the Ajax to perform the activation.
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			cache: false,
			dataType: 'json',
			data: {
				action: action,
				nonce: wpbdp_global.nonce,
				plugin: plugin
			},
			success: function( response ) {
				var error = extractErrorFromAddOnResponse( response );

				if ( error ) {
					addonError( error, el, button );
					return;
				}

				afterAddonInstall( response, button, message, el );
			},
			error: function() {
				button.removeClass( 'wpbdp-loading-button' );
			}
		});
	}

	function installAddonWithCreds( e ) {
		// Prevent the default action, let the user know we are attempting to install again and go with it.
		e.preventDefault();

		// Now let's make another Ajax request once the user has submitted their credentials.
		var proceed = jQuery( this ),
			el = proceed.parent().parent(),
			plugin = proceed.attr( 'rel' );

		proceed.addClass( 'wpbdp-loading-button' );

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: true,
			cache: false,
			dataType: 'json',
			data: {
				action: 'wpbdp_install_addon',
				nonce: wpbdp_global.nonce,
				plugin: plugin,
				hostname: el.find( '#hostname' ).val(),
				username: el.find( '#username' ).val(),
				password: el.find( '#password' ).val()
			},
			success: function( response ) {
				var error = extractErrorFromAddOnResponse( response );

				if ( error ) {
					addonError( error, el, proceed );
					return;
				}

				afterAddonInstall( response, proceed, message, el );
			},
			error: function() {
				proceed.removeClass( 'wpbdp-loading-button' );
			}
		});
	}

	function afterAddonInstall( response, button, message, el ) {
		// The Ajax request was successful, so let's update the output.
		button.css({ 'opacity': '0' });
		message.text( 'Active' );
		jQuery( '#wpbdp-oneclick' ).hide();
		jQuery( '#wpbdp-addon-status' ).text( response ).show();
		jQuery( '#wpbdp-upgrade-modal h2' ).hide();
		jQuery( '#wpbdp-upgrade-modal .wpbdp-lock-icon' ).addClass( 'wpbdp-lock-open-icon' );
		jQuery( '#wpbdp-upgrade-modal .wpbdp-lock-icon use' ).attr( 'xlink:href', '#wpbdp-lock-open-icon' );

		// Proceed with CSS changes
		el.parent().removeClass( 'wpbdp-addon-not-installed wpbdp-addon-installed' ).addClass( 'wpbdp-addon-active' );
		button.removeClass( 'wpbdp-loading-button' );

		// Maybe refresh import and SMTP pages
		var refreshPage = document.querySelectorAll( '.wpbdp-admin-page-import, #wpbdp-admin-smtp, #wpbdp-welcome' );
		if ( refreshPage.length > 0 ) {
			window.location.reload();
		}
	}

	function extractErrorFromAddOnResponse( response ) {
		var $message, text;

		if ( typeof response !== 'string' ) {
			if ( typeof response.success !== 'undefined' && response.success ) {
				return false;
			}

			if ( response.form ) {
				if ( jQuery( response.form ).is( '#message' ) ) {
					return {
						message: jQuery( response.form ).find( 'p' ).html()
					};
				}
			}

			return response;
		}

		return false;
	}

	function addonError( response, el, button ) {
		if ( response.form ) {
			jQuery( '.wpbdp-inline-error' ).remove();
			button.closest( '.wpbdp-card' )
				.html( response.form )
				.css({ padding: 5 })
				.find( '#upgrade' )
					.attr( 'rel', button.attr( 'rel' ) )
					.on( 'click', installAddonWithCreds );
		} else {
			el.append( '<div class="wpbdp-addon-error wpbdp_error_style"><p><strong>' + response.message + '</strong></p></div>' );
			button.removeClass( 'wpbdp-loading-button' );
			jQuery( '.wpbdp-addon-error' ).delay( 4000 ).fadeOut();
		}
	}

	return {
		init: function() {
			jQuery( document ).on( 'click', '.wpbdp-install-addon', installAddon );
			jQuery( document ).on( 'click', '.wpbdp-activate-addon', activateAddon );
		}
	};
}

wpbdpAddonBuild = wpbdpAddons();

jQuery( document ).ready( function( $ ) {
	wpbdpAddonBuild.init();
});

// Some utilities for our admin forms.
jQuery(function( $ ) {

    $( '.wpbdp-js-toggle' ).change(function() {
        var other_opts,
			name = $(this).attr('name');
        var value = $(this).val();
        var is_checkbox = $(this).is(':checkbox');
        var is_radio = $(this).is(':radio');
        var is_select = $(this).is('select');
        var toggles = $(this).attr('data-toggles');

        if ( is_select ) {
            var $option = $( this ).find( ':selected' );
            toggles = $option.attr( 'data-toggles' );

            if ( ! toggles || 'undefined' == typeof toggles )
                toggles = '';
        }

        if ( toggles ) {
            var $dest = ( toggles.startsWith('#') || toggles.startsWith('-') ) ? $(toggles) : $( '#' + toggles + ', .' + toggles );

            if ( 0 === $dest.length || ( ! is_radio && ! is_checkbox && ! is_select ) )
                return;

            if ( is_checkbox && $(this).is(':checked') ) {
                $dest.toggleClass('hidden');
                return;
            }
        }

        if ( is_select ) {
            other_opts = $( this ).find( 'option' ).not( '[value="' + value + '"]' );
        } else {
            other_opts = $('input[name="' + name + '"]').not('[value="' + value + '"]');
        }

        other_opts.each(function() {
            var toggles_i = $(this).attr('data-toggles');

            if ( ! toggles_i )
                return;

            var $dest_i = ( toggles_i.startsWith('#') || toggles_i.startsWith('-') ) ? $(toggles_i) : $( '#' + toggles_i + ', .' + toggles_i );
            $dest_i.addClass('hidden');
        });
    });

});

//
// {{ Admin tab selectors.
//
jQuery(function($) {
    $('.wpbdp-admin-tab-nav a').click(function(e) {
        e.preventDefault();

        var $others = $( this ).parents( 'ul' ).find( 'li a' );
        var $selected = $others.filter( '.current' );

        $others.removeClass( 'current' );
        $( this ).addClass( 'current' );

        var href = $( this ).attr('href');
        var $content = $( href );

        if ( $selected.length > 0 )
            $( $selected.attr( 'href' ) ).hide();

        $content.show().focus();
    });

    $( '.wpbdp-admin-tab-nav' ).each(function(i, v) {
        $(this).find('a:first').click();
    });
});
//
// }}
//

jQuery( function( $ ) {
	$( document ).on( 'click', '.wpbdp-admin-confirm', function( e ) {
		// e.preventDefault();

		var message = $( this ).data( 'confirm' );
		if ( ! message || 'undefined' == typeof message )
			message = 'Are you sure you want to do this?';

		var confirm = window.confirm( message );
		if ( ! confirm ) {
			e.stopImmediatePropagation();
			return false;
		}

		return true;
	} );

	$( document ).on( 'click', '.wpbdp-admin-ajax', function( e ) {
		e.preventDefault();
		var $btn = $(this),
			data = $btn.data( 'ajax' ),
			message = $btn.data( 'confirm' ),
			$target = $($btn.data( 'target' ));
		var confirm = window.confirm( message );
		if ( ! confirm ) {
			return false;
		}
		$btn.prop( 'disabled', true );
		$.post(
			ajaxurl,
			data,
			function( res ) {
				if ( res.success ) {
					$target.removeClass( 'error' ).addClass( 'updated' ).find('p').html( res.message ).show();
					$target.fadeOut( 1000 );
				} else {
					$target.removeClass( 'updated' ).addClass( 'error' ).find('p').html( res.error ).show();
				}
				$btn.prop( 'disabled', false );
			},
			'json'
		);
	});
});

/*
 * Highlight Directory menu.
 */
function wpbdpSelectSubnav() {
        var wpbdpMenu = jQuery( '#toplevel_page_wpbdp_admin' );
        jQuery( wpbdpMenu ).removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
        jQuery( '#toplevel_page_wpbdp_admin a.wp-has-submenu' ).removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
}
