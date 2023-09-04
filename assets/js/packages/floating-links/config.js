/**
 * Configuration File.
 * Establishes links and options parameters to be utilized by the S11FloatingLinks class.
 *
 * @class S11FloatingLinks
 */

( ( wp ) => {

	/**
	 * Global variables
	 *
	 * Varraibles: s11FloatingLinksData
	 */
	const { proIsInstalled, navLinks } = s11FloatingLinksData;

	/**
	 * WordPress dependencies
	 */
	const { __ } = wp.i18n;

	/**
	 * SVG definitions for the icons
	 */
	// Icon for Upgrade link
	const upgradeIcon = `
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
			<path stroke="#667085" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m12 4.75 1.75 5.5h5.5l-4.5 3.5 1.5 5.5-4.25-3.5-4.25 3.5 1.5-5.5-4.5-3.5h5.5L12 4.75Z"/>
		</svg>
	`;

	// Icon for Support link
	const supportIcon = `
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
			<path stroke="#667085" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.25 12a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z"/>
			<path stroke="#667085" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.25 12a3.25 3.25 0 1 1-6.5 0 3.25 3.25 0 0 1 6.5 0ZM7 17l2.5-2.5M17 17l-2.5-2.5m-5-5L7 7m7.5 2.5L17 7"/>
		</svg>
	`;

	// Icon for Documentation link
	const documentationIcon = `
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
			<path stroke="#667085" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.924 16.002.482 2.432c.106.537.682.895 1.286.8l1.64-.256c.604-.095 1.007-.607.9-1.145l-.481-2.431m-3.827.6-1.157-5.835c-.106-.538.297-1.05.9-1.145l1.64-.257c.605-.095 1.18.264 1.287.801l1.157 5.836m-3.827.6 3.827-.6M8.75 15.75v2.5a1 1 0 0 0 1 1h1.5a1 1 0 0 0 1-1v-2.5m-3.5 0v-8a1 1 0 0 1 1-1h1.5a1 1 0 0 1 1 1v8m-3.5 0h3.5m-7.5 0v2.5a1 1 0 0 0 1 1h1.5a1 1 0 0 0 1-1v-2.5m-3.5 0v-10a1 1 0 0 1 1-1h1.5a1 1 0 0 1 1 1v10m-3.5 0h3.5"/>
		</svg>
	`;

	// Icon for Notifications link
	const notificationsIcon = `
		<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
			<path stroke="#667085" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 12v-2a5.25 5.25 0 1 0-10.5 0v2l-2 4.25h14.5l-2-4.25ZM9 16.75s0 2.5 3 2.5 3-2.5 3-2.5"/>
		</svg>
	`;

	/**
	 * Define links for the "free" version of the plugin
	 */
	const freeVersionLinks = [
		{
			title: __( 'Upgrade', 'business-directory-plugin' ),
			icon: upgradeIcon,
			url: navLinks.freeVersion.upgrade,
			openInNewTab: true,
		},
		{
			title: __( 'Support', 'business-directory-plugin' ),
			icon: supportIcon,
			url: navLinks.freeVersion.support,
			openInNewTab: true,
		},
		{
			title: __( 'Documentation', 'business-directory-plugin' ),
			icon: documentationIcon,
			url: navLinks.freeVersion.documentation,
			openInNewTab: true,
		},
		{
			title: __( 'Notifications', 'business-directory-plugin' ),
			icon: notificationsIcon,
			url: '#',
			openInNewTab: false,
			class: 's11-notifications-icon',
		},
	];

	/**
	 * Define links for the "pro" version of the plugin
	 */
	const proVersionLinks = [
		{
			title: __( 'Support & Docs', 'business-directory-plugin' ),
			icon: supportIcon,
			url: navLinks.proVersion.support_and_docs,
			openInNewTab: true,
		},
		{
			title: __( 'Notifications', 'business-directory-plugin' ),
			icon: notificationsIcon,
			url: '#',
			openInNewTab: false,
			class: 's11-notifications-icon',
		},
	];

	/**
	 * Define options
	 */
	const options = {
		color: 'rgba(60,75,93,0.8)',
		hoverColor: '#3C4B5D',
		bgHoverColor: 'rgba(109,135,185,0.07)',
		logoIcon: `<svg width="35" height="46" viewBox="0 0 31 40" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.695 27.566c1.986-.404 3.576-1.298 5.055-2.93-1.307 2.344-3.024 3.152-5.055 2.93Zm-16.392 0c-1.985-.404-3.576-1.298-5.06-2.93 1.312 2.344 3.03 3.152 5.06 2.93Z" fill="#566982"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.328 26.389c-3.384.242-8.211-2.823-8.88-5.217-.212-.763.552-.894 1.216-.667 2.66.93 5.126.9 7.583 1.53 1.905.49 2.654 4.167.08 4.354ZM20.672 26.389c3.378.242 8.21-2.823 8.88-5.217.212-.763-.558-.894-1.216-.667-2.655.93-5.127.9-7.578 1.53-1.915.49-2.66 4.167-.086 4.354Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M11.934 21.318c-2.467 1.44-4.224 7.202-3.758 11.515.111 1.02.344 1.339 1.854 1.672 2.639.586 8.297.586 10.936 0 1.514-.333 1.742-.651 1.854-1.672.47-4.318-1.287-10.075-3.759-11.515-1.124-.661-5.997-.661-7.127 0Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4 22.106c-1.834 1.07-2.882 4.848-3.181 6.773-.198 1.257-.269 2.586-.127 3.853.066.606.036.642 1.14.884 2.517.556 8.018.556 10.536 0 1.099-.242 1.074-.278 1.14-.884.278-2.586-.294-5.823-1.404-8.161-.4-.844-1.074-1.98-1.91-2.46-.82-.49-5.369-.49-6.194-.005Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4 22.106c-1.834 1.07-2.882 4.849-3.181 6.773-.198 1.257-.269 2.586-.127 3.853.066.606.036.642 1.14.884 1.256.278 3.262.42 5.268.42V21.741c-1.343 0-2.69.122-3.1.364Z" fill="#566982"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.506 29.151c.06.829.005.824.907 1.051.977.242 2.532.364 4.082.364 1.555 0 3.11-.121 4.083-.364.907-.227.85-.222.912-1.05-.137-2.051-.968-6.03-2.589-7.096-.319-.213-1.362-.319-2.406-.319-1.038 0-2.082.107-2.4.319-1.622 1.065-2.452 5.045-2.59 7.095Z" fill="#EEF3F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M10.506 29.152c.06.828.005.823.907 1.05.977.242 2.532.364 4.082.364v-8.823c-1.038 0-2.082.106-2.4.318-1.622 1.06-2.452 5.04-2.59 7.09Z" fill="#fff"/><path fill-rule="evenodd" clip-rule="evenodd" d="m9.872 36.778-3.94-1.056a.571.571 0 0 1-.406-.353.604.604 0 0 1 .03-.536c.664-1.136 2.001-1.5 3.268-1.292.8.136 1.322.484 1.575 1.08.405.92.264 2.369-.527 2.157Z" fill="#FF822E"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.422 34.263c-.476.242-1.165.924-1.332 1.52l-1.98-.53c-.046-.016-.062-.066-.036-.106.678-1.091 2.142-1.309 3.348-.884Z" fill="#FFAE5C"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.14 35.202c-1.338-.358-2.503-.323-2.609.076-.111.394.886 1.005 2.219 1.364 1.337.358 2.502.323 2.608-.071.112-.4-.881-1.01-2.218-1.369Z" fill="#CF5A0C"/><path fill-rule="evenodd" clip-rule="evenodd" d="m21.685 37.157 3.536-2.03a.59.59 0 0 0 .299-.45.59.59 0 0 0-.178-.51c-.932-.93-2.32-.935-3.485-.404-.744.338-1.155.813-1.246 1.45-.147.99.365 2.353 1.074 1.944Z" fill="#FF822E"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.467 34.611c.521.111 1.367.591 1.681 1.122l1.778-1.026c.046-.025.046-.08.006-.11-.943-.87-2.412-.708-3.465.014Z" fill="#FFAE5C"/><path fill-rule="evenodd" clip-rule="evenodd" d="M22.947 35.187c1.195-.687 2.335-.955 2.538-.601.207.358-.593 1.202-1.793 1.889-1.196.687-2.336.954-2.538.6-.208-.353.597-1.196 1.793-1.888Z" fill="#CF5A0C"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.463 8.288c-1.651.758-3.328 6.874-2.461 11.859.202 1.161.896 2.495 3.155 2.985 4.387.964 12.294.964 16.686 0 2.254-.49 2.948-1.824 3.155-2.985.866-4.985-.81-11.101-2.461-11.859-4.2-1.934-13.88-1.934-18.074 0Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M24.37 9.712c1.595 2.435 1.94 7.51 1.463 10.228-.218 1.242-1.058 1.782-2.249 2.04-4.265.934-11.914.934-16.179 0-1.185-.258-2.026-.798-2.244-2.04-.476-2.723-.136-7.793 1.464-10.228.259-.393.461-.429.892-.59 3.95-1.475 12.005-1.475 15.956 0 .435.161.638.197.896.59Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.63 9.712c-1.6 2.435-1.94 7.51-1.464 10.228.213 1.242 1.059 1.783 2.244 2.04 2.133.47 5.111.702 8.09.702V8.021c-3.004 0-6.003.368-7.978 1.1-.43.162-.633.198-.892.591Z" fill="#566982"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 22.682c-2.228 0-4.462-.131-6.336-.394-1.545-.212-2.543-1.177-2.797-2.697-.334-1.995-.086-5.353.39-7.167.948-3.61 7.274-3.888 8.743-.5 1.47-3.393 7.796-3.11 8.743.5.476 1.814.73 5.177.395 7.167-.258 1.515-1.256 2.48-2.796 2.697-1.88.263-4.113.394-6.342.394Z" fill="#EEF3F8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 11.93v10.752c-2.228 0-4.462-.131-6.336-.394-1.545-.212-2.543-1.177-2.797-2.697-.334-1.995-.086-5.353.39-7.167.948-3.606 7.274-3.888 8.743-.494Z" fill="#fff"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.716 16.43a1.348 1.348 0 0 1 2.695 0c0 .035 0 .075-.005.11h-.755c-.269-.808-1.348-.858-1.935-.11Zm11.564 0a1.348 1.348 0 1 0-2.69.111h.76c.263-.809 1.342-.86 1.93-.112Z" fill="#353D47"/><path d="M1.327 12.344c.076-.293.517-.36 1.322-.485 1.388-.228 3.617-.394 5.765-.374 1.357.015 2.68.096 3.743.207.983.106 1.57.379 2.209 1.227.258-.086.856-.202 1.13-.202l.258.52v1.278l-.254.334c-.202.005-.567.06-.699.227-.066 1.944-.577 4.035-2.006 4.788-2.446 1.283-8.226.823-9.447-1.036a7.663 7.663 0 0 1-1.14-2.939c-.02-.101-.87-.798-.957-1.263-.08-.49-.055-1.772.076-2.282Zm3.075 6.217c1.1 1.161 5.825 1.757 7.978.58 1.165-.63 1.494-2.449 1.494-4.136 0-.722-.694-1.899-1.63-2.03a29.753 29.753 0 0 0-3.217-.227c-1.95-.056-3.89-.046-5.567.318-.542.116-.486.727-.456 1.318.056 1.182.522 3.253 1.398 4.177Z" fill="#353D47"/><path d="M29.673 12.344c-.076-.293-.517-.36-1.322-.485-1.393-.228-3.622-.394-5.77-.374a41.72 41.72 0 0 0-3.743.207c-.983.106-1.57.379-2.209 1.227-.258-.086-.856-.202-1.134-.202l-.259.52v1.278l.259.339c.202.005.572.06.699.227.066 1.944.577 4.035 2.006 4.788 2.446 1.283 8.226.823 9.447-1.036a7.576 7.576 0 0 0 1.134-2.939c.02-.101.876-.798.957-1.263.092-.495.066-1.777-.065-2.287Zm-3.075 6.217c-1.1 1.161-5.82 1.757-7.978.58-1.165-.63-1.49-2.449-1.49-4.136 0-.722.695-1.899 1.632-2.03a29.752 29.752 0 0 1 3.211-.227c1.956-.056 3.896-.046 5.572.318.542.116.486.727.456 1.318-.066 1.182-.532 3.252-1.403 4.177Z" fill="#353D47"/><path fill-rule="evenodd" clip-rule="evenodd" d="m14.026 19.838 1.125-1.363c.187-.232.501-.232.699 0l1.12 1.363h-2.944Z" fill="#FF822E"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 18.535v1.304h-1.134l.968-1.172a.421.421 0 0 1 .167-.132Z" fill="#FFAE5C"/><path fill-rule="evenodd" clip-rule="evenodd" d="m13.621 20.005 1.342 1.293a.786.786 0 0 0 1.074 0l1.348-1.293c.162-.156.086-.368-.137-.368h-3.49c-.223 0-.3.212-.137.368Z" fill="#CF5A0C"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 21.515a.785.785 0 0 1-.537-.212l-1.342-1.293c-.162-.156-.086-.368.137-.368H15.5v1.873Z" fill="#FF822E"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.83 7.202c-.384-1.252-.313-2.58.127-3.333 1.17 1.5 2.73 2.823 4.438 3.606-1.307.01-2.573.005-3.982.096-.374.02-.542-.233-.582-.369Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13.606 5.349a20.647 20.647 0 0 1-.46-.495 3.964 3.964 0 0 0 .12 2.08c.062.187.132.066.122.02-.05-.595.056-.98.218-1.605Z" fill="#566982"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 9.46c-1.469-1.728-1.337-5.223 0-6.778 1.626 2.879 2.928 3.55 4.559 4.611.137.09.106.414-.106.434-2.194.202-3.582.642-4.453 1.733Z" fill="#3F4B5B"/><path fill-rule="evenodd" clip-rule="evenodd" d="M15.875 4.364a6.132 6.132 0 0 1-.416-.632c-.476.98-.592 2.248-.435 3.334.01.086.055.187.066.03.07-.914.303-1.823.785-2.732Z" fill="#566982"/></svg>`,
	};

	// Determine the appropriate links and initialize the S11FloatingLinks class
	const links = proIsInstalled
		? proVersionLinks
		: freeVersionLinks;

	// We need jQuery here because BD bell notifications created by jQuery.
	jQuery( document ).ready(function () {
		// Trigger the 's11_floating_links_set_config' action, passing the links and options
		wp.hooks.doAction( 's11_floating_links_set_config', { links, options } );
	});

})( window.wp );
