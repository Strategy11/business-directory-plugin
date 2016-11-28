jQuery(function($) {

    var $color_picker = $( '#wpbdp-fee-form #fee-bgcolor-picker' );
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
