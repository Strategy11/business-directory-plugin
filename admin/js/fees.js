jQuery(function($) {
    var $form = $( 'form#wpbdp-fee-form' );

    if ( 0 == $form.length )
        return;

    var update_form_ui = function() {
        // Listing run.
        var fee__days = $( 'input[name="_days"]:checked', $form ).val();

        if ( 0 == fee__days ) {
            $( 'input[name="fee[days]"]', $form ).prop( 'disabled', true )
                                                 .val( '0' );
        } else {
            $( 'input[name="fee[days]"]', $form ).prop( 'disabled', false )
                                                 .focus();
        }

        // Category Policy.
        var $pricing = $( 'input[name="fee[pricing_model]"]', $form );
        var limit_categories = $( 'select[name="limit_categories"]', $form ).val() == '1',
            pricing          = $pricing.filter( ':checked' ).val();
        var $category_chooser = $( '#limit-categories-list', $form );

        if ( limit_categories ) {
            $category_chooser.removeClass( 'hidden' );

            if ( $( '.term-cb:checked', $category_chooser ).length > 0 ) {
                $pricing.filter( '[value="variable"]' ).parent().show();
            } else {
                $pricing.filter( '[value="variable"]' ).parent().hide();
                pricing = $pricing.val(['flat']).val();
            }
        } else {
            $category_chooser.addClass( 'hidden' );
            $pricing.filter( '[value="variable"]' ).parent().show();
        }

        // Show pricing details.
        $( '.fee-pricing-details' ).not( '.pricing-details-' + pricing ).addClass( 'hidden' );
        $( '.fee-pricing-details.pricing-details-' + pricing, $form ).removeClass( 'hidden' );

        // Show only the price-rows that match the category selection.
        if ( 'variable' == pricing ) {
            var $rows = $( '.wpbdp-variable-pricing-configurator-row', $form );

            if ( ! limit_categories ) {
                $rows.removeClass( 'hidden' );
            } else {
                $rows.addClass( 'hidden' );
                $category_chooser.find( '.term-cb:checked' ).each(function() {
                    $rows.filter( '[data-term-id="' + $( this ).val() + '"]' ).removeClass( 'hidden' );
                });
            }
        }
    };
    update_form_ui();

    $( 'input[name="fee[days]"]', $form ).blur( function() {
        var val = parseInt( $.trim( $( this ).val() ), 10 );
        $( this ).val( isNaN( val ) ? '0' : Math.max( 0, Math.round( val ) ) );
    } );
    $( 'input[name="_days"],' +
       'select[name="limit_categories"],' +
       'input[name="fee[pricing_model]"],' +
       '#limit-categories-list .term-cb'
    ).change( update_form_ui );

    $form.submit(function() {
        $( 'input[name="fee[days]"]', $form ).prop( 'disabled', false );
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
