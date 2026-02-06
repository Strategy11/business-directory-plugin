/**
 * Internal dependencies
 */
import { getQueryParam, navigateToStep } from '../utils';
import addCollapsibleEvents from './collapsibleListener';
import addConsentTrackingButtonEvents from './consentTrackingButtonListener';
import addSkipStepButtonEvents from './skipStepButtonListener';

/**
 * Attaches event listeners for handling user interactions.
 *
 * @return {void}
 */
export function addEventListeners() {
	addSkipStepButtonEvents();
	addCollapsibleEvents();
	addConsentTrackingButtonEvents();
}

/**
 * Responds to browser navigation events (back/forward) by updating the UI to match the step indicated in the URL or history state.
 *
 * @param {PopStateEvent} event The event object associated with the navigation action.
 * @return {void}
 */
window.addEventListener( 'popstate', ( event ) => {
	const stepName = event.state?.step || getQueryParam( 'step' );
	// Navigate to the specified step without adding to browser history
	navigateToStep( stepName, 'replaceState' );
} );
