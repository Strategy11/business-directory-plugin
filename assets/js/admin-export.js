jQuery( function ( $ ) {
	const progressBar = new WPBDP_Admin.ProgressBar(
		$( '.step-2 .export-progress' )
	);

	let exportInProgress = false;
	let cancelExport = false;
	let lastState = null;

	let existingToken = null;

	const handleError = function ( msg, res ) {
		if ( msg ) $( '.wpbdp-page-csv-export div.error p' ).text( msg );

		if ( res && res.state ) {
			const state = res.state ? res.state : lastState;

			$.ajax( ajaxurl, {
				data: {
					action: 'wpbdp-csv-export',
					nonce: wpbdp_global.nonce,
					state,
					cleanup: 1,
				},
				type: 'POST',
			} );
		}

		cancelExport = true;
		exportInProgress = false;

		$( '.step-1, .step-2, .step-3' ).hide();
		$( '.wpbdp-page-csv-export div.error' ).show();
		$( '.canceled-export' ).show();

		$( 'html, body' ).animate( { scrollTop: 0 }, 'medium' );
	};

	const advanceExport = function ( state ) {
		if ( ! exportInProgress ) return;

		lastState = state;

		if ( cancelExport ) {
			exportInProgress = false;
			cancelExport = false;

			$( '.step-2' ).fadeOut( function () {
				$( '.canceled-export' ).fadeIn();
			} );

			$.ajax( ajaxurl, {
				data: {
					action: 'wpbdp-csv-export',
					nonce: wpbdp_global.nonce,
					state,
					cleanup: 1,
				},
				type: 'POST',
				dataType: 'json',
				success( res ) {},
			} );
			return;
		}

		$.ajax( ajaxurl, {
			data: {
				action: 'wpbdp-csv-export',
				nonce: wpbdp_global.nonce,
				state,
			},
			type: 'POST',
			dataType: 'json',
			success( res ) {
				if ( ! res || res.error ) {
					exportInProgress = false;
					handleError( res && res.error ? res.error : null, res );
					return;
				}

				$( '.step-2 .listings' ).text(
					res.exported + ' / ' + res.count
				);
				$( '.step-2 .size' ).text( res.filesize );
				progressBar.set( res.exported, res.count );

				if ( res.isDone ) {
					exportInProgress = false;

					$( '.step-2' ).fadeOut( function () {
						const downloadUrl = (
							res.download_url || res.fileurl
						).replace( /\s/g, '' );
						existingToken = res.token;

						$( '.step-3 .download-link a' ).attr(
							'href',
							downloadUrl
						);
						$( '.step-3 .download-link a .filename' ).text(
							res.filename
						);
						$( '.step-3 .download-link a .filesize' ).text(
							res.filesize
						);

						$( '.step-3' ).fadeIn( function () {
							$( '.step-3 .cleanup-link' ).hide();
						} );
					} );
				} else {
					advanceExport( res.state );
				}
			},
			error() {
				handleError();
			},
		} );
	};

	$( document ).on( 'submit', 'form#wpbdp-csv-export-form', function ( e ) {
		e.preventDefault();

		const data =
			$( this ).serialize() +
			'&action=wpbdp-csv-export&nonce=' +
			wpbdp_global.nonce;
		$.ajax( ajaxurl, {
			data,
			type: 'POST',
			dataType: 'json',
			success( res ) {
				if ( ! res || res.error ) {
					exportInProgress = false;
					handleError( res && res.error ? res.error : null, res );
					return;
				}

				$( '.step-1' ).fadeOut( function () {
					exportInProgress = true;
					$( '.step-2 .listings' ).text( '0 / ' + res.count );
					$( '.step-2 .size' ).text( '0 KB' );

					$( '.step-2' ).fadeIn( function () {
						advanceExport( res.state );
					} );
				} );
			},
			error() {
				handleError();
			},
		} );
	} );

	$( 'a.cancel-import' ).on( 'click', function ( e ) {
		e.preventDefault();
		cancelExport = true;
	} );

	$( '.step-3 .download-link a' ).on( 'click', function ( e ) {
		$( '.step-3 .cleanup-link' ).fadeIn();
	} );

	$( '.step-3 .cleanup-link a' ).on( 'click', function ( e ) {
		e.preventDefault();
		$.ajax( ajaxurl, {
			data: {
				action: 'wpbdp-csv-export',
				nonce: wpbdp_global.nonce,
				state: lastState,
				existing_token: existingToken,
				cleanup: 1,
			},
			type: 'POST',
			dataType: 'json',
			success( res ) {
				location.href = '';
			},
		} );
	} );
} );
