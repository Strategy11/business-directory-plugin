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
                } else {
                    $( '.iframe-confirm' ).hide();
                }
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
        },

        onFieldTypeChange: function() {
            var $field_type = $(this).find('option:selected');

            if ( !$field_type.length )
                return;

            var field_type = $field_type.val();

            // URL fields can only have the 'url' validator.
            if ( 'url' == field_type ) {
                $( 'select#field-validator option' ).not( '[value="url"]' ).attr( 'disabled', 'disabled' ).removeAttr( 'selected' );
                $( 'select#field-validator option[value="url"]' ).attr( 'selected', 'selected' );
            } else {
                $( 'select#field-validator option' ).removeAttr( 'disabled' );
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
        },

        onAssociationChange: function() {
            $f_fieldtype = WPBDPAdmin_FormFields.$f_fieldtype;

            var association = $(this).find('option:selected').val();
            var valid_types = WPBDP_associations_fieldtypes[ association ];

            $f_fieldtype.find('option').removeAttr('disabled');

            $f_fieldtype.find('option').each(function(i,v){
                if ( $.inArray( $(v).val(), valid_types ) < 0 ) {
                    $(v).attr('disabled', 'disabled');
                }
            });

            if ( $f_fieldtype.find('option:selected').attr('disabled') == 'disabled' ) {
                $f_fieldtype.find('option').removeAttr('selected');
                $f_fieldtype.find('option[value="' + valid_types[0] + '"]').attr('selected', 'selected');
            }
        }
    };


    $(document).ready(function(){
        WPBDPAdmin_FormFields.init();
    });

})(jQuery);


jQuery(document).ready(function($){

    // {{ Manage Fees.
    $('form#wpbdp-fee-form input[name="_days"]').change(function(){
        var value = $(this).val();

        // alert(value);

        if (value == 0) {
            $('form input#wpbdp-fee-form-days-n').attr('disabled', true);
            $('form input[name="fee[days]"]').val('0');
        } else {
            $('form input#wpbdp-fee-form-days-n').removeAttr('disabled');
            $('form input[name="fee[days]"]').val($('form input#wpbdp-fee-form-days-n').val());
            $('form input#wpbdp-fee-form-days-n').focus();
        }

        return true;
    });

    $('.wpbdp-page-admin-fees .wp-list-table.fees tbody').sortable({
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

    $('form#wpbdp-fee-form').submit(function(){
        // alert($('form#wpbdp-fee-form input[name="fee[days]"]').val());
        // return false;
        $('form input[name="fee[days]"]').removeAttr('disabled');
        return true;
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
        $( '.wpbdp-page-admin-fees .wp-list-table .wpbdp-drag-handle' ).show();
    }
    // }}


    /* Listing Info Metabox */

    $('#BusinessDirectory_listinginfo .listing-metabox-tabs a').click(function(e){
        e.preventDefault();

        var href = $(this).attr('href');

        var $selected = $(this).parent('li').siblings('.selected');

        if ($selected.length > 0) {
            if ($selected.find('a:first').attr('href') == href) {
                return;
            } else {
                // hide current tab (if any)
                $selected.removeClass('selected');
                $($selected.find('a:first').attr('href')).hide();
            }
        }

        // show new tab
        $(this).parent('li').addClass('selected');
        $(href).show();
        $('.listing-fee-expiration-datepicker').hide();
    });

    var url_tab = $(location).attr( 'hash' );
    if ( url_tab && $( url_tab ).length > 0 ) {
        $( '#BusinessDirectory_listinginfo a[href="' + url_tab  + '"]' ).click();
    } else {
        $('#BusinessDirectory_listinginfo .listing-metabox-tabs li.selected a').click();
    }


    /* Listing info metabox / fees */

    $('#listing-metabox-fees a.assignfee-link').click(function(e){
        e.preventDefault();
        $(this).siblings('.assignfee').show();
    });

    $('#listing-metabox-fees .assignfee .close-handle').click(function(e){
        e.preventDefault();
        $(this).parent('.assignfee').hide();
    });

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
WPBDP_Admin.listingMetabox = {};

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

(function($) {
    var metabox = WPBDP_Admin.listingMetabox;

    metabox._initialize = function() {
        // Hack from
        // http://stackoverflow.com/questions/3961963/beforeshow-event-not-firing-on-jqueryui-datepicker.
        $.extend( $.datepicker, {
            _inlineDatepicker2: $.datepicker._inlineDatepicker,
            // Override the _inlineDatepicker method
            _inlineDatepicker: function (target, inst) {
                // Call the original
                this._inlineDatepicker2(target, inst);
                var beforeShow = $.datepicker._get(inst, 'beforeShow');

                if (beforeShow) {
                    beforeShow.apply(target, [target, inst]);
                }
            }
        });

        // Expiration date changing.
        var _addNeverButton = function( instance ) {
            setTimeout( function() {
                var $buttonPane = $(instance).find( '.ui-datepicker-buttonpane' );

                if ( $buttonPane.find( '.ui-datepicker-never' ).length > 0 )
                return;

            var $button = $( '<button>', {
                text: 'Never Expires',
                click: function() {
                    _updateExpiration( $(instance), 'never' );
                    return false;
                },
            }).addClass( 'ui-datepicker-never ui-state-default ui-priority-primary ui-corner-all' );

            $buttonPane.append($button);
            }, 1 );
        };

        var _updateExpiration = function( $instance, newDate ) {
            if ( ! newDate )
                return;

            var $changeLink = $instance.siblings('a.expiration-change-link');
            var $expirationDate = $instance.siblings('.expiration-date');
            var $spinner = $instance.parents('.listing-category').find('.spinner:first');

            $expirationDate.text('--');
            $spinner.show();

            $instance.hide();
            _addNeverButton($instance.get(0));

            $.post(ajaxurl, {action: 'wpbdp-listing_set_expiration', renewal_id: $changeLink.attr('data-renewal_id'), expiration_date: newDate}, function(res) {
                if (res && res.success) {
                    $spinner.hide();
                    $expirationDate.text(res.data.formattedExpirationDate).show();
                }
            }, 'json');
        };

        $('#listing-metabox-generalinfo, #listing-metabox-fees').each(function(i, v) {
            var $tab = $(v);
            $tab.find('.expiration-date-info .datepicker').each(function(i, v) {
                var $dp = $(v);
                var $changeLink = $dp.siblings('a.expiration-change-link');

                $dp.hide().datepicker({
                    dateFormat: 'yy-mm-dd',
                    defaultDate: $changeLink.attr('data-date'),
                    showButtonPanel: true,
                    beforeShow: function( input ) {
                        _addNeverButton( input );
                    },
                    onChangeMonthYear: function( year, month, instance ) {
                        _addNeverButton(instance.input);
                    },
                    onSelect: function(newDate) {
                        _updateExpiration( $(this), newDate );
                    }
                });
            });

            $tab.find('a.expiration-change-link').click(function(e) {
                e.preventDefault();

                var renewal_id = $(this).attr('data-renewal_id');
                $('.expiration-date-info .datepicker').not('.renewal-' + renewal_id ).hide();
                $('.expiration-date-info .datepicker.renewal-' + renewal_id).toggle();
            });
        });

        // Listing category deletion.
        $('.listing-category a.category-delete').click(function(e) {
            e.preventDefault();

            var listingID = $(this).attr('data-listing');
            var categoryID = $(this).attr('data-category');

            if ( !listingID || !categoryID ) {
                return;
            }

            var $category = $('.listing-category-' + categoryID);
            $.post(ajaxurl, {action: 'wpbdp-listing_remove_category', 'listing': listingID, 'category': categoryID}, function(res) {
                if (res && res.success) {
                    $('input[name="tax_input[wpbdp_category][]"][value="' + categoryID + '"]').attr('checked', false);
                    $category.fadeOut(function(){ $(this).remove(); });
                }
            }, 'json');
        });

        // Listing category fee change.
        $('.listing-category a.category-change-fee').click(function(e) {
            e.preventDefault();

            if ($('#wpbdp-modal-dialog').length == 0) {
                $('body').append($('<div id="wpbdp-modal-dialog"></div>'));
            }

            $.post(ajaxurl, {'action': 'wpbdp-listing_change_fee', 'renewal': $(this).attr('data-renewal')}, function(res) {
                if (res && res.success) {
                    $('#wpbdp-modal-dialog').html(res.data.html);
                    tb_show('', '#TB_inline?inlineId=wpbdp-modal-dialog');
                    $('#wpbdp-modal-dialog').remove();
                }
            }, 'json');
        });
    };

    $(document).ready(function(){
        if ( $('#listing-metabox-fees').length > 0 ) {
            metabox._initialize();
        }
    });

})(jQuery);

/* {{ Settings. */
(function($) {
    var s = WPBDP_Admin.settings = {
        _whenTrueActivateChilds: {},

        init: function() {
            var t = this;

            // E-mail template editors.
            $( '.wpbdp-settings-email' ).each(function(i, v) {
                var $email = $(v);
                var $preview = $email.find('.short-preview');
                var $editor = $email.find('.editor');
                var $subject = $editor.find('.subject-text');
                var $body = $editor.find('.body-text');
                var data = {'subject': '', 'body': ''};

                $preview.click(function(e) {
                    data['subject'] = $subject.val();
                    data['body'] = $body.val();

                    $preview.hide();
                    $editor.show();
                });

                $( '.cancel', $editor ).click(function(e) {
                    e.preventDefault();

                    $subject.val(data['subject']);
                    $body.val(data['body']);

                    $editor.hide();
                    $preview.show();
                });

                $( '.save', $editor ).click(function(e) {
                    e.preventDefault();
                    $('form#wpbdp-admin-settings #submit').click();
                });

                $( '.preview-email', $editor ).click(function(e) {
                    e.preventDefault();

                    var subject = $subject.val();
                    var body = $body.val();

                    $.ajax({
                        url: ajaxurl,
                        data: { 'action': 'wpbdp-admin-settings-email-preview',
                                'nonce': $editor.attr('data-preview-nonce'),
                                'setting': $email.attr('data-setting'),
                                'subject': subject,
                                'body': body },
                        dataType: 'json',
                        type: 'POST',
                        success: function(res) {
                            if ( ! res.success ) {
                                return;
                            }

                            if ( 0 == $( '#wpbdp-modal-dialog' ).length )
                                $( 'body' ).append( '<div id="wpbdp-modal-dialog" style="display: none;"></div>' );

                            $( '#wpbdp-modal-dialog' ).html(res.data.html);
                            tb_show( '', '#TB_inline?inlineId=wpbdp-modal-dialog' );
                            $( '#wpbdp-modal-dialog' ).remove();
                        }
                    });

                });

            });

            $('select#quick-search-fields').change(function() {
                var selected = $(this).find( 'option.textfield:selected' ).length;

                if ( selected > 0 ) {
                    $('span.text-fields-warning').fadeIn('fast');
                } else {
                    $('span.text-fields-warning').fadeOut('fast');
                }
            });

            $.each( this._whenTrueActivateChilds, function( p, chs ) {
                $('input[name="wpbdp-' + p + '"]').change(function(e) {
                    t.handleToggle( p );
                });

                t.handleToggle( p );
            } );
        },

        handleToggle: function( setting ) {
            var childs = this._whenTrueActivateChilds[ setting ];

            if ( 'undefined' === typeof( childs ) )
                return;

            var checked = $( 'input[name="wpbdp-' + setting + '"]').is(':checked');

            $.each( this._whenTrueActivateChilds[ setting ], function( i, c ) {
                var $c = $( '[name="wpbdp-' + c + '"], [name="wpbdp-' + c + '[]"]' );
                var $row = $c.parents( 'tr' );

                // FIXME: 'disabled' fields result in the setting being "cleared" in the backend. Why?
                if ( checked ) {
//                    $c.removeAttr( 'disabled' );
                    $c.removeAttr( 'contenteditable' );
                    $row.removeClass('disabled');
                } else {
//                    $c.attr( 'disabled', 'disabled' );
                    $c.attr( 'contenteditable', 'false' );
                    $row.addClass('disabled');
                }
            } );
        },

        add_requirement: function( setting, parent_, req ) {
            if ( 'undefined' === typeof this._whenTrueActivateChilds[ parent_ ] )
                this._whenTrueActivateChilds[ parent_ ] = [];

            this._whenTrueActivateChilds[ parent_ ].push( setting );
        }
    };

    $(document).ready(function(){
        if ( 0 == $('.wpbdp-page-admin-settings').length )
            return;

        s.init();
    });
})(jQuery);
/* }} */

/* {{ Uninstall. */
(function($) {
    var u = WPBDP_Admin.uninstall = {
        init: function() {
            $( 'form#wpbdp-uninstall-capture-form input[name="uninstall[reason_id]"]' ).change(function(e) {
                var val = $(this).val();

                if ( '0' == val ) {
                    $( 'form#wpbdp-uninstall-capture-form textarea[name="uninstall[reason_text]"]' ).fadeIn();
                } else {
                    $( 'form#wpbdp-uninstall-capture-form textarea[name="uninstall[reason_text]"]' ).fadeOut( 'fast', function() {
                        $(this).val('');
                    } );
                }
            });
        }
    };

    $(document).ready(function(){
        if ( $( '.wpbdp-page-admin-uninstall' ).length > 0 )
            u.init();
    });
})(jQuery);
/* }} */

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

// {{ Settings - License Activation.
(function($) {
    var l = WPBDP_Admin.licensing = {
        init: function() {
            $( 'input.license-activate' ).click(function(){
                var module = $(this).parent( '.license-activation' ).attr( 'data-module-id' );
                var license = $( 'input[type="text"]#license-key-' + module ).val();
                l.activation_change( module, license, 'activate' );
            });

            $( 'input.license-deactivate' ).click(function(){
                var module = $(this).parent( '.license-activation' ).attr( 'data-module-id' );
                var license = $( 'input[type="text"]#license-key-' + module );
                l.activation_change( module, '', 'deactivate' );
            });
        },

        activation_change: function(module, license, action) {
            var $container = $( '.license-activation[data-module-id="' + module + '"]' );
            var $msg = $( '.status-message', $container );
            var nonce = $( 'input[name="nonce"]', $container ).val();

            $msg.removeClass('ok error');

            $msg.html( $( 'input.license-' + action, $container ).attr('data-L10n') );

            $.post( ajaxurl, { 'action': 'wpbdp-' + action + '-license', 'module': module, 'key': license, 'nonce': nonce }, function(res) {
                if ( res.success ) {
                    $msg.hide()
                        .html(res.message)
                        .removeClass('error')
                        .addClass('ok')
                        .show();

                    $('input.license-' + action, $container).hide();
                    $('input[type="button"]', $container).not( '.license-' + action ).show();

                    if ( 'activate' == action )
                        $( 'input[type="text"]#license-key-' + module ).attr('readonly', 'readonly');
                    else
                        $( 'input[type="text"]#license-key-' + module ).removeAttr('readonly');
                } else {
                    $msg.hide()
                        .html(res.error)
                        .removeClass('ok')
                        .addClass('error')
                        .show();

                    if ( 'deactivate' == action )
                        $( 'input[type="text"]#license-key-' + module ).removeAttr('readonly');
                }
            }, 'json' );
        }

    };

    $(document).ready(function() {
        if ( $( 'input.license-activate, input.license-deactivate' ).length > 0 )
            l.init();

        if ( $( '.wpbdp-license-expired-warning' ).length > 0 ) {
            $( '.wpbdp-license-expired-warning .dismiss' ).click(function (e) {
                e.preventDefault();

                var module_id = $(this).attr('data-module');
                var nonce = $(this).attr('data-nonce');
                var $warning = $(this).parents('.wpbdp-license-expired-warning');

                $.post( ajaxurl, {'action': 'wpbdp-license-expired-warning-dismiss', 'nonce': nonce, 'module': module_id}, function(res) {
                    if ( res.success ) {
                        $warning.fadeOut( 'fast' );
                    }
                }, 'json' );
            });
        }
    });
})(jQuery);
// }}

// Dismissible Messages
(function($) {
    $(function(){
        $( '.wpbdp-notice.dismissible > .notice-dismiss' ).click( function( e ) {
            e.preventDefault();

            var $notice = $( this ).parent( '.wpbdp-notice' );
            var dismissible_id = $( this ).data( 'dismissible-id' );
            var nonce = $( this ).data( 'nonce' );

            $.post( ajaxurl,
                    { action: 'wpbdp_dismiss_notification', id: dismissible_id, nonce: nonce },
                    function() {
                        $notice.fadeOut( 'fast', function(){ $notice.remove(); } );
                    }
            );
        } );
    });
})(jQuery);
