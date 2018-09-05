jQuery( function( $ ) {
    // Datepicker for expiration date editing.
    var _addNeverButton = function( instance ) {
        setTimeout( function() {
            var $buttonPane = $(instance.dpDiv).find( '.ui-datepicker-buttonpane' );

            if ( $buttonPane.find( '.ui-datepicker-never' ).length > 0 )
                return;

            var $button = $( '<button>', {
                text: 'Never Expires',
                click: function() {
                    $(instance.input).val('');
                    $(instance.input).datepicker( 'hide' );
                },
            }).addClass( 'ui-datepicker-never ui-state-default ui-priority-primary ui-corner-all' );

            $buttonPane.append($button);
        }, 1 );
    };

    $( '#wpbdp-listing-metabox-plan-info input[name="listing_plan[expiration_date]"]' ).datepicker({
        dateFormat: 'yy-mm-dd',
        showButtonPanel: true,
        beforeShow: function( input, instance ) {
            _addNeverButton( instance );
        },
        onChangeMonthYear: function( year, month, instance ) {
            _addNeverButton( instance );
        }
    });

    var $metabox_tab = $('#wpbdp-listing-metabox-plan-info');

    // Makes sure texts displayed inside the metabox are in sync with the settings.
    var updateText = function() {
        var plan_id = $( 'input[name="listing_plan[fee_id]"]').val();
        var expiration = $('input[name="listing_plan[expiration_date]"]').val();
        var images = $('input[name="listing_plan[fee_images]"]').val();

        var $plan = $('select#wpbdp-listing-plan-select').find('option[value="' + plan_id + '"]');
        if ( $plan.length > 0) {
            var plan_data = $.parseJSON($plan.attr('data-plan-info'));

            $('#wpbdp-listing-plan-prop-label').html(
                wpbdpListingMetaboxL10n.planDisplayFormat.replace('{{plan_id}}', plan_data.id)
                                                         .replace('{{plan_label}}', plan_data.label));
            $( '#wpbdp-listing-plan-prop-amount' ).html( plan_data.amount ? plan_data.amount : '-' );
            $( '#wpbdp-listing-plan-prop-is_sticky' ).html( plan_data.sticky ? wpbdpListingMetaboxL10n.yes : wpbdpListingMetaboxL10n.no );
            $( '#wpbdp-listing-plan-prop-is_recurring' ).html( plan_data.recurring ? wpbdpListingMetaboxL10n.yes : wpbdpListingMetaboxL10n.no );
        }

        $('#wpbdp-listing-plan-prop-expiration').html(expiration ? expiration : wpbdpListingMetaboxL10n.noExpiration);
        $('#wpbdp-listing-plan-prop-images').html(images);
    };

    // Properties editing.
    $metabox_tab.find('a.edit-value-toggle').click(function(e) {
        e.preventDefault();

        var $dd = $(this).parents('dd');
        var $editor = $dd.find('.value-editor');
        var $display = $dd.find('.display-value');
        var $input = $editor.find('input[type="text"], input[type="checkbox"], select');
        var current_value = $input.is(':checkbox') ? $input.is(':checked') : $input.val();

        $input.data('before-edit-value', current_value);

        $(this).hide();
        $display.hide();
        $editor.show();
    });
    $metabox_tab.find('.value-editor a.update-value, .value-editor a.cancel-edit').click(function(e) {
        e.preventDefault();

        var $dd = $(this).parents('dd');
        var $editor = $dd.find('.value-editor');
        var $display = $dd.find('.display-value');
        var $input = $editor.find('input[type="text"], input[type="checkbox"], select');

        if ( $(this).is( '.cancel-edit' ) ) {
            var prev_value = $input.data('before-edit-value');

            if ($input.is(':checkbox'))
                $input.prop('checked', prev_value);
            else
                $input.val(prev_value);
        } else {
            // Plan changes are handled in a special way.
            if ($input.is('#wpbdp-listing-plan-select')) {
                var plan = $.parseJSON( $input.find( 'option:selected' ).attr( 'data-plan-info' ) );

                $metabox_tab.find('input[name="listing_plan[fee_id]"]').val(plan.id);
                $metabox_tab.find('input[name="listing_plan[expiration_date]"]').val(plan.expiration_date);
                $metabox_tab.find('input[name="listing_plan[fee_images]"]').val(plan.images);
            }

            updateText();
        }

        $editor.hide();
        $display.show();
        $dd.find('.edit-value-toggle').show();
    });

    $payments_tab = $('#wpbdp-listing-metabox-payments');

    $payments_tab.mouseover( function () {
        $( this ).find('.payment-delete-action').css( 'left', '0');
    }).mouseout( function () {
        $( this ).find('.payment-delete-action').css( 'left', '-9999em' );
    });

    $payments_tab.find('a[name="delete-payments"]').click( function (e) {
        e.preventDefault();
        $.post( ajaxurl, { 'action': 'wpbdp-clear-payment-history', 'listing_id': $( this ).attr( 'data-id' ) }, function (res) {
            if ( ! res.success ) {
                if ( res.data.error )
                    $('#wpbdp-listing-payment-message').addClass('error').html(res.data.error).fadeIn();

                return;
            }

            $( '.wpbdp-payment-items', $payments_tab ).fadeOut( 'fast', function() {
                $( this ).html( '' );
                $( '#wpbdp-listing-payment-message', $payments_tab ).html( res.data.message ).fadeIn();
            } );

        } );
    });

} );
