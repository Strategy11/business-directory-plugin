jQuery(function($) {
    var $form = $( 'form#wpbdp-fee-form' );

    if ( 0 == $form.length )
        return;


    $form.submit(function(){
        $('form input[name="fee[days]"]').removeAttr('disabled');
        return true;
    });

    // Limit categories and variable pricing handling.
    $form.find('#limit-categories-list .term-cb').change(function(e) {
        var $dest = $( '.wpbdp-variable-pricing-configurator-row[data-term-id="' + $(this).val() + '"]' );

        if ( $(this).is(':checked') ) {
            $( 'input[name="fee[pricing_model]"][value="variable"]' ).prop( 'disabled', false );
            $dest.removeClass('hidden');
        } else {
            $( 'input[name="fee[pricing_model]"][value="variable"]' ).prop( 'disabled', $( '#limit-categories-list .term-cb' ).filter( ':checked' ).length == 0 );
            $dest.addClass('hidden');
        }
    });
    $form.find('select[name="limit_categories"]').change(function(e) {
        var val = $(this).val();

        if ( '0' == val ) {
            $('#wpbdp-fee-form #limit-categories-list .term-cb').prop('checked', false);
            $( 'input[name="fee[pricing_model]"][value="variable"]' ).prop( 'disabled', false );
            $('.wpbdp-variable-pricing-configurator-row').removeClass('hidden');
        } else {
            $( 'input[name="fee[pricing_model]"][value="variable"]' ).prop( 'disabled', true );
            $('.wpbdp-variable-pricing-configurator-row').addClass('hidden');
        }

    });

    // Fee duration.
    $form.find('input[name="_days"]').change(function(){
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

    // Color picker.
    var $color_picker = $form.find( '#fee-bgcolor-picker' );
    var $color_picker_field = $color_picker.find( '#fee-bgcolor-value' );

    // Initial color.
    if ( $color_picker_field.val() ) {
        $color_picker_field.css( 'color', $color_picker_field.val() );
        $color_picker_field.css( 'background-color', $color_picker_field.val() );
    }

    $color_picker_field.iris({
        mode: 'hsl',
        hide: false,
        width: 200,
        palettes: true,
        border: false,
        target: '#fee-bgcolor-picker-iris',
        change: function(event, ui) {
            $(this).css( 'color', ui.color.toString() );
            $(this).css( 'background-color', ui.color.toString() );
        }
    }).focus(function() {
        $color_picker.find( '.color-selection' ).show();
    });

    $color_picker.find( 'a.iris-square-value' ).click(function(e) {
        e.preventDefault();
        $color_picker.find( '.color-selection' ).hide();
    });
    $( 'a.reset-btn', $color_picker ).click(function(e) {
        e.preventDefault();
        $color_picker_field.val( '' ).css( 'background-color', '#ffffff' );
        $color_picker.find( '.color-selection' ).hide();
    });
    $( 'a.close-btn', $color_picker ).click(function(e) {
        e.preventDefault();
        $color_picker.find( '.color-selection' ).hide();
    });

});
