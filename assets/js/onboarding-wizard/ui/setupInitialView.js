/**
 * Internal dependencies
 */
import { getElements } from '../elements';
import { HIDE_JS_CLASS, STEPS } from '../shared';
import { getQueryParam, removeQueryParam, navigateToStep } from '../utils';

/**
 * Initializes the onboarding wizard's UI, sets up the initial step based on certain conditions,
 * and applies necessary UI enhancements for a smoother user experience.
 *
 * @return {void}
 */
export default function setupInitialView() {
	navigateToInitialStep();
	fadeInPageElements();
}

/**
 * Determines the initial step in the onboarding process and navigates to it, considering the installation
 * status of Formidable Pro and specific query parameters.
 *
 * @private
 * @return {void}
 */
function navigateToInitialStep() {
	const initialStepName = determineInitialStep();

	clearOnboardingQueryParams();
	navigateToStep( initialStepName, 'replaceState' );
}

/**
 * Determines the initial step based on the current state, such as whether Formidable Pro is installed
 * and the presence of specific query parameters. Also handles the removal of unnecessary steps.
 *
 * @private
 * @return {string} The name of the initial step to navigate to.
 */
function determineInitialStep() {
	return getQueryParam( 'step' ) || STEPS.INITIAL;
}

/**
 * Clears specific query parameters related to the onboarding process.
 *
 * @private
 * @return {void}
 */
function clearOnboardingQueryParams() {
	removeQueryParam( 'key' );
	removeQueryParam( 'success' );
}

/**
 * Smoothly fades in the background and container elements of the page for a more pleasant user experience.
 *
 * @private
 * @return {void}
 */
function fadeInPageElements() {
	const { onboardingWizardPage, container } = getElements();

	onboardingWizardPage.classList.remove( HIDE_JS_CLASS );
	container.classList.toggle( 'wpbdp-fadein-up' );
}