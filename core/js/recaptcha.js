wpbdp_recaptcha = function() {
    var $ = jQuery;

    if ( 0 == $( '.wpbdp-recaptcha' ).length )
        return;

    $( '.wpbdp-recaptcha' ).each(function(i, v) {
        var $captcha = $(v);

        grecaptcha.render( $captcha[0], { 'sitekey': $captcha.attr( 'data-key' ),
                                          'theme': 'light' } );
    });
};
