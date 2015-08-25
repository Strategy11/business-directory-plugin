jQuery(function($) {
    $( '#wpbdp-admin-page-themes #dismiss-suggested-fields-warning' ).click( function( e ) {
        e.preventDefault();
        $( this ).parents( 'div.error' ).fadeOut( 'fast' );
    } );
});

