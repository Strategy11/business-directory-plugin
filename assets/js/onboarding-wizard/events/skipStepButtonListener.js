/**
 * Internal dependencies
 */
import { getElements } from '../elements';
import { navigateToNextStep } from '../utils';

/**
 * Manages event handling for the "Skip" step button.
 *
 * @return {void}
 */
function addSkipStepButtonEvents() {
	const { skipStepButtons } = getElements();

	skipStepButtons.forEach( ( skipButton ) => {
		skipButton.addEventListener( 'click', navigateToNextStep );
	} );
}

export default addSkipStepButtonEvents;
