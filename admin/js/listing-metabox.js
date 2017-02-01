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
        var plan = $.parseJSON($('select[name="listing_plan[fee_id]"]').find('option:selected').attr('data-plan-info'));
        var expiration = $('input[name="listing_plan[expiration_date]"]').val();
        var images = $('input[name="listing_plan[fee_images]"]').val();
        var is_sticky = $('input[name="listing_plan[is_sticky]"]').is(':checked');

        $('#wpbdp-listing-plan-prop-label').html(
            wpbdpListingMetaboxL10n.planDisplayFormat.replace('{{plan_id}}', plan.id)
                                                     .replace('{{plan_label}}', plan.label));
        $('#wpbdp-listing-plan-prop-expiration').html(expiration ? expiration : wpbdpListingMetaboxL10n.noExpiration);
        $('#wpbdp-listing-plan-prop-images').html(images);
        $('#wpbdp-listing-plan-prop-is_sticky').html(is_sticky ? wpbdpListingMetaboxL10n.yes : wpbdpListingMetaboxL10n.no);
    };

    // Plan change.
    $metabox_tab.find('select[name="listing_plan[fee_id]"]').change(function(e) {
        var plan = $.parseJSON( $( this ).find( 'option:selected' ).attr( 'data-plan-info' ) );

        $('input[name="listing_plan[expiration_date]"]').val(plan.expiration_date);
        $('input[name="listing_plan[fee_images]"]').val(plan.images);
        $('input[name="listing_plan[is_sticky]"]').prop( 'checked', plan.sticky );

        updateText();
    });

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
            updateText();
        }

        $editor.hide();
        $display.show();
        $dd.find('.edit-value-toggle').show();
    });

} );
