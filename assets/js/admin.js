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
                    $( '#wpbdp-fieldsettings input[name="field[allow_iframes]"]' ).removeAttr( 'checked' );
                    $( this ).parents( '.iframe-confirm' ).hide();
                }
            })

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
                $( 'select#field-validator option' ).not( '[value="url"]' ).attr( 'disabled', 'disabled' ).removeAttr( 'selected' );
                $( 'select#field-validator option[value="url"]' ).attr( 'selected', 'selected' );
            } else {
                $( 'select#field-validator option' ).removeAttr( 'disabled' );
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
                    $('select#field-validator option[value="word_number"]').removeAttr('disabled');
                } else {
                    $('#wpbdp_word_count').hide();
                }
            } else {
                $('#wpbdp_word_count').hide();
                $('select#field-validator option[value="word_number"]').attr('disabled', 'disabled').removeAttr('selected');
            }
        }
    };


    $(document).ready(function(){
        WPBDPAdmin_FormFields.init();
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

    $( 'select[name="fee_order[method]"], select[name="fee_order[order]"]' ).change(function(e) {
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

        if ($('#wpbdp-modal-dialog').length == 0) {
            $('body').append($('<div id="wpbdp-modal-dialog"></div>'));
        }
    };

    payments.viewPaymentDetails = function(id) {
        $.get( ajaxurl, { 'action': 'wpbdp-payment-details', 'id': id }, function(res) {
            if (res && res.success) {
                if ($('#wpbdp-modal-dialog').length == 0) {
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
    if ( 0 == $( '.wpbdp-admin-page-uninstall' ).length ) {
        return;
    }

    var $warnings = $( '#wpbdp-uninstall-messages' );
    var $confirm_button = $( '#wpbdp-uninstall-proceed-btn' );
    var $form = $( '#wpbdp-uninstall-capture-form' );

    $( '#wpbdp-uninstall-proceed-btn' ).click(function(e) {
        e.preventDefault();
        $warnings.fadeOut( 'fast', function() {
            $form.fadeIn( 'fast' );
        } );
    });
    
    $( '#wpbdp-uninstall-capture-form' ).submit(function() {
        var $no_reason_error = $( '.wpbdp-validation-error.no-reason' ).hide();
        var $no_text_error   = $( '.wpbdp-validation-error.no-reason-text' ).hide();
        var $reason_checked = $( 'input[name="uninstall[reason_id]"]:checked' );

        if ( 0 == $reason_checked.length ) {
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
        if ( $('body.wp-admin.widgets-php').length == 0 ) {
            return;
        }

        $( 'body.wp-admin.widgets-php' ).on( 'change', 'input.wpbdp-toggle-images', function() {
            var checked = $(this).is(':checked');

            if ( checked ) {
                $(this).parents('.widget').find('.thumbnail-width-config, .thumbnail-height-config').fadeIn('fast');
            } else {
                $(this).parents('.widget').find('.thumbnail-width-config, .thumbnail-height-config').fadeOut('fast');
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
                        $(this).html( '<p>' + res.message + '</p>' )
                        $(this).removeClass('error')
                        $(this).addClass('updated')
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
        var dismissNotice = function( $notice, notice_id, nonce ) {
            $.post( ajaxurl, {
                action: 'wpbdp_dismiss_notification',
                id: notice_id,
                nonce: nonce
            }, function() {
                $notice.fadeOut( 'fast', function(){ $notice.remove(); } );
            } );
        };

        $( '#wpbody-content' )
            .on( 'click', '.wpbdp-notice.dismissible > .notice-dismiss', function( e ) {
                e.preventDefault();

                var $notice = $( this ).parent( '.wpbdp-notice' );
                var dismissible_id = $( this ).data( 'dismissible-id' );
                var nonce = $( this ).data( 'nonce' );

                dismissNotice( $notice, dismissible_id, nonce );
            } )
            .on( 'click', '.wpbdp-notice.is-dismissible > .notice-dismiss', function( e ) {
                e.preventDefault();

                var $notice = $( this ).parent( '.wpbdp-notice' );
                var dismissible_id = $notice.data( 'dismissible-id' );
                var nonce = $notice.data( 'nonce' );

                dismissNotice( $notice, dismissible_id, nonce );
            } );
    });
})(jQuery);

// Some utilities for our admin forms.
jQuery(function( $ ) {

    $( '.wpbdp-js-toggle' ).change(function() {
        var name = $(this).attr('name');
        var value = $(this).val();
        var is_checkbox = $(this).is(':checkbox');
        var is_radio = $(this).is(':radio');
        var is_select = $(this).is('select');
        var toggles = $(this).attr('data-toggles');

        if ( is_select ) {
            var $option = $( this ).find( ':selected' );
            var toggles = $option.attr( 'data-toggles' );

            if ( ! toggles || 'undefined' == typeof toggles )
                toggles = '';
        }

        if ( toggles ) {
            var $dest = ( toggles.startsWith('#') || toggles.startsWith('-') ) ? $(toggles) : $( '#' + toggles + ', .' + toggles );

            if ( 0 == $dest.length || ( ! is_radio && ! is_checkbox && ! is_select ) )
                return;

            if ( is_checkbox && $(this).is(':checked') ) {
                $dest.toggleClass('hidden');
                return;
            }
        }

        if ( is_select ) {
            var other_opts = $( this ).find( 'option' ).not( '[value="' + value + '"]' );
        } else {
            var other_opts = $('input[name="' + name + '"]').not('[value="' + value + '"]');
        }

        other_opts.each(function() {
            var toggles_i = $(this).attr('data-toggles');

            if ( ! toggles_i )
                return;

            var $dest_i = ( toggles_i.startsWith('#') || toggles_i.startsWith('-') ) ? $(toggles_i) : $( '#' + toggles_i + ', .' + toggles_i );
            $dest_i.addClass('hidden');
        });

        if ( toggles ) {
            $dest.toggleClass('hidden');
        }
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
});
