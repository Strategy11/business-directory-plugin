jQuery(function($) {
    var wpbdp_checkout = {
        init: function() {
            var $form = $( 'form#wpbdp-checkout-form' );

            if ( 0 == $form.length )
                return;

            var self = this;
            this.$form = $form;
            this.$container = this.$form.find( '#wpbdp-checkout-form-fields' );

            this.payment_key = this.$form.find( 'input[name="payment"]' ).val();
            this.nonce = this.$form.find( 'input[name="_wpnonce"]' ).val();

            this.working = false;

            // Gateway changes.
            this.$form.find( 'input[name="gateway"]' ).change(function() {
                if ( self.working )
                    return;

                var gateway = self.$form.find( 'input[name="gateway"]:checked' ).val();

                if ( ! gateway )
                    return;

                self.load_gateway( gateway );
            });

            this.$form.find( 'input[name="gateway"]' ).trigger( 'change' );
        },

        load_gateway: function( gateway_id ) {
            var self = this;
            self.$container.html('');
            self.working = true;

            var url = wpbdp_global.ajaxurl;
            url    += url.indexOf( '?' ) > 0 ? '&' : '?';
            url    += 'payment=' + self.payment_key + '&'
            url    += 'gateway=' + gateway_id;

            $.post( url, { action: 'wpbdp_ajax', handler: 'checkout__load_gateway', _wpnonce: self.nonce }, function( res ) {
                self.$container.removeClass().addClass( 'wpbdp-payment-gateway-' + gateway_id + '-form-fields' );
                self.$container.html( res );
                self.working = false;

                $( window ).trigger( 'wpbdp-payment-gateway-loaded', gateway_id );

                const submitButtonSelector = $( '.wpbdp-checkout-submit input[type="submit"]:visible' );

                if ( 'stripe' === gateway_id ) {
                    submitButtonSelector.on( 'click', self.sendToStripe );
                } else {
                    submitButtonSelector.off( 'click', self.sendToStripe );
                }
            } );
        },

        sendToStripe: function( event ) {
            if ( 'stripe' !== $( 'form#wpbdp-checkout-form [name="gateway"]:checked' ).val() ) {
                return false;
            }

            event.preventDefault();

            const configurationElement = document.querySelector( 'form#wpbdp-checkout-form #wpbdp-stripe-checkout-configuration' );
            if ( ! configurationElement ) {
                return false;
            }

            const configuration = $.parseJSON( configurationElement.dataset.configuration );
            const stripe        = Stripe( configuration.key, { stripeAccount: configuration.accountId } );

            stripe.redirectToCheckout({
                // Make the id field from the Checkout Session creation API response
                // available to this file, so you can provide it as parameter here
                // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
                sessionId: configuration.sessionId
            }).then(function ( result ) {
                // If `redirectToCheckout` fails due to a browser or network
                // error, display the localized error message to your customer
                // using `result.error.message`.
            });

            return false;
        }
    };

    wpbdp_checkout.init();


    // Payment receipt print.
    $( document ).on( 'click', '.wpbdp-payment-receipt-print', function(e) {
        e.preventDefault();
        PrintElem( $( '.wpbdp-payment-receipt' ) );
    });

    function PrintElem(elem)
    {
        var mywindow = window.open('', 'PRINT', 'height=400,width=600');

        mywindow.document.write('<html>');
        mywindow.document.write( $( 'head' ).html() );
        mywindow.document.write('</html><body >');
        mywindow.document.write( $( elem ).wrap('<p/>').parent().html() );
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10*/

        mywindow.onload = function() {
            mywindow.print();
            mywindow.close();
        }

        return true;
    }
});

