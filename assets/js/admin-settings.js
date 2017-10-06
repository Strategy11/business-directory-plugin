jQuery(function($) {
    var wpbdp_settings_dep_handling = {
        init: function() {
            var self = this;

            if ( 'undefined' == typeof wpbdp_settings_data || 'undefined' == typeof wpbdp_settings_data.requirements || ! wpbdp_settings_data.requirements ) {
                return;
            }

            this.data  = wpbdp_settings_data.requirements;
            this.watch = wpbdp_settings_data.watch;
            this.selector = 'input[name="wpbdp_settings[SID]"], input[name="wpbdp_settings[SID][]"], select[name="wpbdp_settings[SID]"], select[name="wpbdp_settings[SID][]"]';

            $.each( this.watch, function( setting_id, affected_settings ) {
                $( self.selector.replace( 'SID', setting_id ) ).change(function() {
                    $.each( affected_settings, function(i, v) {
                        self.check_requirements( v );
                    } );
                });
            } );

            $.each( this.data, function( setting_id, reqs ) {
                self.check_requirements( setting_id );
            } );
        },

        check_requirements: function( setting_id ) {
            var reqs     = this.data[ setting_id ];
            var $setting = $( '#wpbdp-settings-' + setting_id );
            var $row     = $setting.parents( 'tr' );

            var passes = true;

            for ( var i = 0; i < reqs.length; i++ ) {
                var rel_setting  = reqs[ i ].setting_id;
                var operator     = reqs[ i ].operator;
                var req_value    = reqs[ i ].req_value;
                var value        = reqs[ i ].current_value;
                var $rel_setting = $( '#wpbdp-settings-' + rel_setting );

                if ( $rel_setting.length > 0 ) {
                    // Use current value of related setting.
                    var $field = $rel_setting.find( this.selector.replace( 'SID', rel_setting ) );

                    if ( $field.length > 0 ) {
                        if ( $field.is( 'input[type="checkbox"]' ) ) {
                            var $checked = $field.filter( ':checked' );

                            if ( $checked.length > 0 ) {
                                value = $checked.val();
                            } else {
                                value = false;
                            }
                        } else if ( $field.is( 'input[type="radio"]' ) ) {
                            console.log( rel_setting, 'radio' );
                        } else if ( $field.is( 'select' ) ) {
                            console.log( rel_setting, 'select' );
                        }
                    }
                }

                if ( '=' == operator ) {
                    passes = ( value == req_value );
                } else if ( '!=' == operator ) {
                    passes = ( value != req_value );
                }

                if ( ! passes ) {
                    break;
                }
            }

            if ( passes ) {
                $row.removeClass( 'wpbdp-setting-disabled' );
            } else {
                $row.addClass( 'wpbdp-setting-disabled' );
            }
        }
    };
    wpbdp_settings_dep_handling.init();

    /**
     * License activation/deactivation.
     */
    var wpbdp_settings_licensing = {
        init: function() {
            var self = this;

            if ( 0 == $( '.wpbdp-settings-type-license_key' ).length ) {
                return;
            }

            $( '.wpbdp-license-key-activate-btn, .wpbdp-license-key-deactivate-btn' ).click(function(e) {
                e.preventDefault();

                var $button  = $(this);
                var $setting = $(this).parents( '.wpbdp-license-key-activation-ui' );
                var $msg     = $setting.find( '.wpbdp-license-key-activation-status-msg' );
                var $spinner = $setting.find( '.spinner' );
                var activate = $(this).is( '.wpbdp-license-key-activate-btn' );
                var $field   = $setting.find( 'input.wpbdp-license-key-input' );
                var data     = $setting.data( 'licensing' );

                $msg.hide();
                $button.data( 'original_label', $(this).val() );
                $button.val( $(this).data( 'working-msg' ) );
                $button.prop( 'disabled', true );

                if ( activate ) {
                    data['action'] = 'wpbdp_activate_license';
                } else {
                    data['action'] = 'wpbdp_deactivate_license';
                }

                data['license_key'] = $field.val();

                $.post(
                    ajaxurl,
                    data,
                    function( res ) {
                        if ( res.success ) {
                            $msg.removeClass( 'status-error' ).addClass( 'status-success' ).html( res.message ).show();

                            if ( activate ) {
                                $setting.removeClass( 'wpbdp-license-status-invalid' ).addClass( 'wpbdp-license-status-valid' );
                            } else {
                                $setting.removeClass( 'wpbdp-license-status-valid' ).addClass( 'wpbdp-license-status-invalid' );
                            }

                            $field.prop( 'readonly', activate ? true : false );
                        } else {
                            $msg.removeClass( 'status-success' ).addClass( 'status-error' ).html( res.error ).show();
                            $setting.removeClass( 'wpbdp-license-status-valid' ).addClass( 'wpbdp-license-status-invalid' );
                            $field.prop( 'readonly', false );
                        }

                        $button.val( $button.data( 'original_label' ) );
                        $button.prop( 'disabled', false );
                    },
                    'json'
                );
            });
        }
    };
    wpbdp_settings_licensing.init();

    /**
     * E-Mail template editors.
     */
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
                $( '#wpbdp-admin-page-settings form' ).get(0).submit();
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

//     $(document).ready(function() {
//         if ( $( 'input.license-activate, input.license-deactivate' ).length > 0 )
//             l.init();
//
//         if ( $( '.wpbdp-license-expired-warning' ).length > 0 ) {
//             $( '.wpbdp-license-expired-warning .dismiss' ).click(function (e) {
//                 e.preventDefault();
//
//                 var module_id = $(this).attr('data-module');
//                 var nonce = $(this).attr('data-nonce');
//                 var $warning = $(this).parents('.wpbdp-license-expired-warning');
//
//                 $.post( ajaxurl, {'action': 'wpbdp-license-expired-warning-dismiss', 'nonce': nonce, 'module': module_id}, function(res) {
//                     if ( res.success ) {
//                         $warning.fadeOut( 'fast' );
//                     }
//                 }, 'json' );
//             });
//         }
//     });
// })(jQuery);
// // }}
//
