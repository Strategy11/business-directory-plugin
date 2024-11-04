/**
 * Internal dependencies
 */
import { getElements } from '../elements';
import { navigateToNextStep } from '../utils';

/**
 * Manages event handling for the "Allow & Continue" button in the "Never miss an important update" step.
 *
 * @return {void}
 */
function addConsentTrackingButtonEvents() {
	const { consentTrackingButton } = getElements();

	consentTrackingButton.addEventListener(
		'click',
		onConsentTrackingButtonClick
	);
}

/**
 * Handles the click event on the "Allow & Continue" button in the "Never miss an important update" step.
 *
 * @private
 * @param {Event} event The event object
 * @return {void}
 */
const onConsentTrackingButtonClick = async ( event ) => {
	event.preventDefault();

	const formData = new FormData();
	formData.append( 'action', 'wpbdp_onboarding_consent_tracking' );
	formData.append( 'nonce', wpbdp_global.nonce );

	let data;

	try {
		const response = await fetch( ajaxurl, {
			method: 'POST',
			body: formData,
		} );

		data = await response.json();
	} catch ( error ) {
		console.error( 'Error:', error );
	}

	if ( ! data.success ) {
		console.error( data || 'Request failed' );
		return;
	}

	navigateToNextStep( data );
};

export default addConsentTrackingButtonEvents;
