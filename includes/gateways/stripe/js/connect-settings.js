( function() {
	function setupStripeConnectListener() {
		onclickPreventDefault( '#wpbdp_disconnect_stripe', handleStripeDisconnectClick );
		onclickPreventDefault( '#wpbdp_reauth_stripe', handleStripeReauthClick );
		onclickPreventDefault( '#wpbdp_connect_with_oauth', handleConnectWithOauth );
		renderStripeConnectSettingsButton();
	}

	function renderStripeConnectSettingsButton() {
		const container = document.getElementById( 'wpbdp_strp_settings_container' );
		if ( null !== container ) {
			postAjax(
				{
					action: 'wpbdp_strp_connect_get_settings_button'
				},
				function( data ) {
					container.innerHTML = data.html;
				}
			);
		}
	}

	function onclickPreventDefault( selector, callback ) {
		jQuery( document ).on( 'click', selector, function( event ) {
			event.preventDefault();
			callback( this );
		});
	}

	function handleStripeDisconnectClick( trigger ) {
		const testMode = isTriggerInTestMode( trigger );

		const spinner = document.createElement( 'span' );
		spinner.className = 'wpbdp-wait wpbdp_visible_spinner';
		spinner.style.margin = 0; // The default 20px margin causes the spinner to look bad.
		trigger.replaceWith( spinner );

		strpSettingsAjaxRequest(
			'wpbdp_stripe_connect_disconnect',
			function() {
				renderStripeConnectSettingsButton();
			},
			testMode
		);
	}

	function handleStripeReauthClick( trigger ) {
		strpSettingsAjaxRequest(
			'wpbdp_stripe_connect_reauth',
			function( data ) {
				if ( 'undefined' !== typeof data.connect_url ) {
					window.location = data.connect_url;
				} else {
					renderStripeConnectSettingsButton();
				}
			},
			isTriggerInTestMode( trigger )
		);
	}

	function handleConnectWithOauth( trigger ) {
		trigger.classList.add( 'wpbdp-loading-button' );
		strpSettingsAjaxRequest(
			'wpbdp_stripe_connect_oauth',
			function( data ) {
				if ( 'undefined' !== typeof data.redirect_url && data.redirect_url ) {
					window.location = data.redirect_url;
				} else {
					renderStripeConnectSettingsButton();
				}
			},
			isTriggerInTestMode( trigger ),
			function( data ) {
				const messageEle = document.getElementById( 'wpbdp_strp_connect_error' );
				if ( messageEle ) {
					messageEle.innerHTML = data.message;
					messageEle.classList.remove( 'wpbdp-hidden' );
				}
				renderStripeConnectSettingsButton();
			}
		);
	}

	function strpSettingsAjaxRequest( action, success, testMode, fail ) {
		const data = {
			action: action,
			testMode: testMode,
			nonce: wpbdp_global.nonce
		};
		postAjax( data, success, fail );
	}

	function postAjax( data, success, fail ) {
		const xmlHttp = new XMLHttpRequest();
		const params = typeof data === 'string' ? data : Object.keys( data ).map(
			function( k ) {
				return encodeURIComponent( k ) + '=' + encodeURIComponent( data[ k ]);
			}
		).join( '&' );

		xmlHttp.open( 'post', ajaxurl, true );
		xmlHttp.onreadystatechange = function() {
			let response;
			if ( xmlHttp.readyState > 3 && xmlHttp.status == 200 ) {
				response = xmlHttp.responseText;
				if ( response !== '' ) {
					response = JSON.parse( response );
					if ( response.success ) {
						if ( 'undefined' === typeof response.data ) {
							response.data = {};
						}
						success( response.data );
					} else if ( fail ) {
						fail( response.data );
					}
				}
			}
		};
		xmlHttp.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
		xmlHttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
		xmlHttp.send( params );
		return xmlHttp;
	}

	function isTriggerInTestMode( trigger ) {
		return parseInt( jQuery( trigger ).closest( '[data-test-mode]' ).attr( 'data-test-mode' ) );
	}

	jQuery( document ).ready( setupStripeConnectListener );
}() );
