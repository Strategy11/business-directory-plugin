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

	/**
	 * @param {string} action
	 * @param {function} success
	 * @param {number} testMode
	 * @param {function|undefined} fail
	 */
	function strpSettingsAjaxRequest( action, success, testMode, fail ) {
		const data = {
			action: action,
			testMode: testMode,
			nonce: wpbdp_global.nonce
		};
		postAjax( data, success, fail );
	}

	/**
	 * @param {object} data
	 * @param {function} success
	 * @param {function|undefined} fail
	 * @return {void}
	 */
	function postAjax( data, success, fail ) {
		fetch( ajaxurl, {
			method: 'POST',
			body: new URLSearchParams( data )
		})
		.then( response => response.json() )
		.then( response => {
			if ( response.success ) {
				success( response.data || {} );
			} else if ( fail ) {
				fail( response.data );
			}
		})
		.catch( error => {
			if ( fail ) {
				fail({ message: error.message });
			}
		});
	}

	function isTriggerInTestMode( trigger ) {
		return parseInt( jQuery( trigger ).closest( '[data-test-mode]' ).attr( 'data-test-mode' ) );
	}

	jQuery( document ).ready( setupStripeConnectListener );
}() );
