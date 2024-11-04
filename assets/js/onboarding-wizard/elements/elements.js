/**
 * Internal dependencies
 */
import { PREFIX } from '../shared';
import { createPageElements } from '.';

export const { getElements, addElements } = createPageElements( {
	onboardingWizardPage: document.getElementById( `${ PREFIX }-wizard-page` ),
	container: document.getElementById( `${ PREFIX }-container` ),
	steps: document.querySelectorAll( `.${ PREFIX }-step` ),
	skipStepButtons: document.querySelectorAll( `.${ PREFIX }-skip-step` ),
	successStep: document.getElementById( `${ PREFIX }-success-step` ),
} );
