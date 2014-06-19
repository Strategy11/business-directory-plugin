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

    /* Manage Fees */
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

    $('form#wpbdp-fee-form').submit(function(){
        // alert($('form#wpbdp-fee-form input[name="fee[days]"]').val());
        // return false;
        $('form input[name="fee[days]"]').removeAttr('disabled');
        return true;
    });


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

    $('#BusinessDirectory_listinginfo .listing-metabox-tabs li.selected a').click();


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

    $('input[id="doaction"]').click(function(e) {
        var $selected_option = $('select[name="action"] option:selected');
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

    /* CSV import */
    $('form#wpbdp-csv-import-form input.assign-listings-to-user').change(function(e){
        if ( $(this).is(':checked') ) {
            $('form#wpbdp-csv-import-form .default-user-selection').show();
            //$('form#wpbdp-csv-import-form select.default-user').hide('disabled');
        } else {
            $('form#wpbdp-csv-import-form .default-user-selection').hide();
            //$('form#wpbdp-csv-import-form select.default-user').attr('disabled', 'disabled');
        }

    }).change();

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
                $('#wpbdp-modal-dialog').html(res.data.html);
                tb_show('', '#TB_inline?inlineId=wpbdp-modal-dialog');
            }
        }, 'json' );
    };

    // Initialize payments.
    $(document).ready(function(){ payments._initialize(); });

})(jQuery);

(function($) {
    var metabox = WPBDP_Admin.listingMetabox;

    metabox._initialize = function() {
        // Expiration date changing.
        $('#listing-metabox-generalinfo, #listing-metabox-fees').each(function(i, v) {
            var $tab = $(v);
            $tab.find('.expiration-date-info .datepicker').each(function(i, v) {
                var $dp = $(v);
                var $changeLink = $dp.siblings('a.expiration-change-link');

                $dp.hide().datepicker({
                    dateFormat: 'yy-mm-dd',
                    defaultDate: $changeLink.attr('data-date'),
                    onSelect: function(newDate) {
                        if (newDate) {
                            var $expirationDate = $(this).siblings('.expiration-date');
                            var $spinner = $(this).parents('.listing-category').find('.spinner:first');

                            $expirationDate.text('--'); $spinner.show();

                            $.post(ajaxurl, {action: 'wpbdp-listing_set_expiration', renewal_id: $changeLink.attr('data-renewal_id'), expiration_date: newDate}, function(res) {
                                    if (res && res.success)
                                    $spinner.hide();
                                    $expirationDate.text(res.data.formattedExpirationDate).show();
                                }, 'json');
                        }

                        $(this).hide();
                        
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
        if ( 0 == $('body.directory-admin_page_wpbdp_admin_settings').length )
            return;

        s.init();
    });
})(jQuery);
/* }} */
