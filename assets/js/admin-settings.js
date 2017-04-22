jQuery(function($) {

    // E-Mail template editors.
    var wpbdp_settings_email = {
        init: function() {
            var self = this;

            $( '.wpbdp-settings-email-preview, .wpbdp-settings-email-edit-btn' ).click(function(e) {
                e.preventDefault();

                var $email = $( this ).parents( '.wpbdp-settings-email' );
                $( this ).hide();
                $email.find( '.wpbdp-settings-email-editor' ).show();
            });

            $( '.wpbdp-settings-email-editor .cancel' ).click(function(e) {
                e.preventDefault();

                var $email = $( this ).parents( '.wpbdp-settings-email' );
                var $editor = $email.find( '.wpbdp-settings-email-editor' );

                // Add-new editor.
                if ( $email.parent().is( '#wpbdp-settings-expiration-notices-add' ) ) {
                    $email.hide();
                    $( '#wpbdp-settings-expiration-notices-add-btn' ).show();
                    return;
                }

                // Sync editor with old values.
                var subject = $editor.find( '.stored-email-subject' ).val();
                var body = $editor.find( '.stored-email-body' ).val();
                $editor.find( '.email-subject' ).val( subject );
                $editor.find( '.email-body' ).val( body );

                if ( $email.hasClass( 'wpbdp-expiration-notice-email' ) ) {
                    var event = $editor.find( '.stored-notice-event' ).val();
                    var reltime = $editor.find( '.stored-notice-relative-time' ).val();

                    $editor.find( '.notice-event' ).val( event );
                    $editor.find( '.notice-relative-time' ).val( reltime );

                    if ( ! reltime ) {
                        reltime = '0 days';
                    }

                    $editor.find( 'select.relative-time-and-event' ).val( event + ',' + reltime );
                }

                // Hide editor.
                $editor.hide();
                $email.find( '.wpbdp-settings-email-preview' ).show();
            });

            $( '.wpbdp-settings-email-editor .delete' ).click(function(e) {
                e.preventDefault();

                var $email = $( this ).parents( '.wpbdp-settings-email' );
                $email.next().remove();
                $email.remove();
                $( 'form#wpbdp-admin-settings' ).get(0).submit();
            });

            // Expiration notices have some additional handling to do.
            $( '.wpbdp-expiration-notice-email select.relative-time-and-event' ).change(function(e) {
                var parts = $( this ).val().split(',');
                var event = parts[0];
                var relative_time = parts[1];

                var $email = $( this ).parents( '.wpbdp-settings-email' );
                $email.find( '.notice-event' ).val( event );
                $email.find( '.notice-relative-time' ).val( relative_time );
            });

            $( '#wpbdp-settings-expiration-notices-add-btn' ).click(function(e) {
                e.preventDefault();

                var $container = $( '#wpbdp-settings-expiration-notices-add .wpbdp-expiration-notice-email' );
                var $editor = $container.find( '.wpbdp-settings-email-editor' );

                $( this ).hide();
                $container.show();
                $editor.show();
            });

            $( '#wpbdp-settings-expiration-notices-add input[type="submit"]' ).click(function(e) {
                var $editor = $( this ).parents( '.wpbdp-settings-email-editor' );

                $editor.find( 'input, textarea, select' ).each( function(i) {
                    var name = $( this ).attr( 'name' );

                    if ( ! name || -1 == name.indexOf( 'new_notice' ) )
                        return;

                    $( this ).prop( 'name', name.replace( 'new_notice', 'wpbdp-expiration-notices' ) );
                } );
            });
        },
    };
    wpbdp_settings_email.init();

});
