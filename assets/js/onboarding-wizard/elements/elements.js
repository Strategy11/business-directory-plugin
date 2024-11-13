/**
 * Internal dependencies
 */
import { PREFIX } from '../shared';
import { createPageElements } from '.';

export const { getElements, addElements } = createPageElements( {
	onboardingWizardPage: document.getElementById( `${ PREFIX }-wizard-page` ),
	container: document.getElementById( `${ PREFIX }-container` ),
	rootline: document.getElementById( `${ PREFIX }-rootline` ),
	steps: document.querySelectorAll( `.${ PREFIX }-step` ),

	skipStepButtons: document.querySelectorAll( `.${ PREFIX }-skip-step` ),
	consentTrackingButton: document.getElementById(
		`${ PREFIX }-consent-tracking`
	),

	collapsible: document.querySelector( '.wpbdp-collapsible' ),
} );
