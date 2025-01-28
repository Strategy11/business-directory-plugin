/**
 * Initialize CSV import validation.
 */
export default function initializeCsvImportValidation() {
	// Add error listener to file inputs.
	const inputs = [ 'input[name=csv-file]', 'input[name=images-file]' ];

	const getParentElements = ( input ) => {
		const mainWrapper = input.closest( '.wpbdp-setting-row' );
		const labelWrapper = mainWrapper.querySelector(
			'.wpbdp-setting-label'
		);

		return {
			mainWrapper,
			labelWrapper,
			label: labelWrapper.querySelector( 'label' ),
		};
	};

	const addFileSizeError = ( input ) => {
		const parentElements = getParentElements( input );

		parentElements.label.style.color = 'red';

		const error = document.createElement( 'p' );
		error.classList.add( 'wpbdp-csv-import-error' );
		error.textContent = window.wpbdp_admin_import.error_label;
		parentElements.labelWrapper.appendChild( error );

		document
			.querySelectorAll( '.submit input[type=submit]' )
			.forEach( ( el ) => ( el.disabled = true ) );
	};

	const resetFileSizeErrors = ( input ) => {
		const parentElements = getParentElements( input );

		parentElements.label.style.color = '';
		parentElements.mainWrapper
			.querySelectorAll( '.wpbdp-csv-import-error' )
			.forEach( ( el ) => el.remove() );

		const errors = document.querySelectorAll( '.wpbdp-csv-import-error' );

		if ( errors.length === 0 ) {
			document
				.querySelectorAll( '.submit input[type=submit]' )
				.forEach( ( el ) => ( el.disabled = false ) );
		}
	};

	inputs.forEach( ( input ) => {
		const fileInput = document.querySelector( input );

		if ( ! fileInput ) {
			return;
		}

		fileInput.addEventListener( 'change', () => {
			resetFileSizeErrors( fileInput );

			const file = fileInput.files[ 0 ];

			if ( file && file.size > window.wpbdp_admin_import.maxFileSize ) {
				addFileSizeError( fileInput );
			}
		} );
	} );
}
