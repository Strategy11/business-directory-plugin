jQuery(function($) {
    $( '#wpbdp-admin-page-themes #dismiss-suggested-fields-warning' ).click( function( e ) {
        e.preventDefault();
        $( this ).parents( 'div.error' ).fadeOut( 'fast' );
    } );

    $( '#wpbdp-admin-page-themes form.license-activation' ).submit( function( e ) {
        e.preventDefault();

        var $form = $( this );
        var $msg = $( '.status-message', $form );
        var data = $( this ).serialize();

        $msg.removeClass( 'ok error' );
        $msg.html( $( 'input[type="submit"]', $form ).attr( 'data-l10n' ) );

        $.post( ajaxurl, data, function( res ) {
            if ( ! res.success ) {
                $msg.hide()
                    .html( res.error )
                    .removeClass( 'ok' )
                    .addClass( 'error' )
                    .show();
                return;
            }

            $msg.hide()
                .html( res.message )
                .removeClass( 'error' )
                .addClass( 'ok' )
                .show();

            $( 'input[type="submit"]', $form ).hide();
            $( 'input[name="license"]', $form ).attr( 'readonly', 'readonly' );
        }, 'json' );
    } );

    $( '#wpbdp-admin-page-themes .wpbdp-theme .update-link' ).click( function( e ) {
        e.preventDefault();

        var $theme = $( this ).parents( '.wpbdp-theme' );
        var $info = $( '.wpbdp-theme-update-info', $theme );
        var $msg = $( '.update-message', $info );

        $msg.html( $info.attr( 'data-l10n-updating' ) );

        $.post( ajaxurl, {
            'action': 'wpbdp-themes-update',
            '_wpnonce': $( this ).attr( 'data-nonce' ),
            'theme': $( this ).attr( 'data-theme-id' ) }, function( res ) {
                if ( ! res.success ) {
                    $info.addClass( 'update-error' );
                    $msg.html( res.error );
                    return;
                }

                var $html = $( res.data.html );
                $( '.wpbdp-theme-details-wrapper', $theme ).replaceWith( $( '.wpbdp-theme-details-wrapper', $html ) );
                $info.removeClass( 'update-available' ).addClass( 'theme-updated' );
                $msg.html( $info.attr( 'data-l10n-updated' ) );
        }, 'json' );

    } );

});

