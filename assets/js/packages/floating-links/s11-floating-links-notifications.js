/**
 * Class representing showing notifications.
 *
 * @class S11FLNotifications
 */
class S11FLNotifications {

	/**
	 * Create a new S11FloatingLinksNotifications instance.
	 * Set up floating links and configurations if WordPress notice elements are present.
	 *
	 * @constructor
	 */
	constructor() {
		if ( ! this.getWpNoticeElements() ) {
			return;
		}

		wp.hooks.addAction( 's11_floating_links_init', 'S11FloatingLinks', ( S11FloatingLinks ) => {
			this.floatingLinks = S11FloatingLinks;
			this.initComponent();
		});
	}

	/**
	 * Retrieves WordPress notice elements and stores them in a property of the instance.
	 * If there are no such elements, the method returns false. Otherwise, it returns true.
	 *
	 * @memberof S11FLNotifications
	 *
	 * @returns {boolean} Whether any WordPress notice elements were found.
	 */
	getWpNoticeElements() {
		const allNoticeElements = Array.from( document.querySelectorAll( '.notice') );
		// Filter ':hidden' notices
		this.wpNoticeElements = allNoticeElements.filter( ( el ) => el.offsetParent === null );

		return this.wpNoticeElements.length > 0;
	}

	/**
	 * Initialize the S11FLNotifications component.
	 * This involves setting up the notifications, creating the required DOM elements.
	 *
	 * @memberof S11FLNotifications
	 */
	initComponent() {
		// Prepare the notifications based on the WordPress notice elements.
		this.setNotifications();

		// Set the reference to the notifications icon element.
		this.setIconElement();

		// Create the wrapper, list, and count elements, and append them to the relevant parent elements.
		this.createWrapper();
		this.createList();
		this.createCount();
	}

	/**
	 * Create a wrapper element, and append it to the Floating Links wrapper element.
	 *
	 * @memberof S11FLNotifications
	 */
	createWrapper() {
		// Create the wrapper element
		this.wrapperElement = document.createElement( 'div' );
		this.wrapperElement.classList.add( 's11-notifications', 's11-hidden' );

		// Add the wrapper to the Floating Links wrapper element
		this.floatingLinks.wrapperElement.appendChild( this.wrapperElement );
	}

	/**
	 * Create a list element, and append it to the wrapper element.
	 *
	 * @memberof S11FLNotifications
	 */
	createList() {
		// Create the list element
		this.listElement = document.createElement( 'ul' );
		this.listElement.classList.add( 's11-notifications-list' );

		// Append the list element to the wrapper element
		this.wrapperElement.appendChild( this.listElement );
	}

	/**
	 * Create a count element, and add it to the icon element.
	 *
	 * @memberof S11FLNotifications
	 */
	createCount() {
		// Create the count element
		this.countElement = document.createElement( 'span' );
		this.countElement.classList.add( 's11-notifications-count' );
		this.countElement.textContent = this.count;

		// Append the count element to the icon element
		this.floatingLinks.iconButtonElement.appendChild( this.countElement );

		// Create a copy of the count element
		const countElementCopy = this.countElement.cloneNode( true );
		this.iconElement.appendChild( countElementCopy );
	}

	/**
	 * Retrieve and set the reference to the notifications icon element.
	 *
	 * @memberof S11FLNotifications
	 */
	setIconElement() {
		this.iconElement = this.floatingLinks.navMenuElement.querySelector( '.s11-notifications-icon' );
	}

	/**
     * Process the WordPress notice elements, and store them in the notifications array.
     * Invalid or inline notices are not processed.
     *
     * @memberof S11FLNotifications
     */
	setNotifications() {
		this.notifications = []

		this.wpNoticeElements.forEach( ( noticeElement ) => {
			const mainMessage = noticeElement.id === 'message';
			const isValidNotice =
				( noticeElement.classList.contains( 'wpbdp-notice' ) || mainMessage ) &&
				! noticeElement.classList.contains( 'wpbdp-inline-notice' );

			if ( ! isValidNotice ) {
				return;
			}

			// Add the processed notice to the notifications array if it is dismissible and not the main message
			if ( noticeElement.classList.contains( 'is-dismissible' ) && ! mainMessage ) {
				// If the notification is for missing premium, make the notification dismissible
				if ( noticeElement.dataset.dismissibleId === 'missing_premium' ) {
					const dismissElement = notificationElement.querySelector( '.notice-dismiss' );
					dismissElement.setAttribute( 'data-dismissible-id', this.dataset.dismissibleId );
					dismissElement.setAttribute( 'data-nonce', this.dataset.nonce );
				}

				// Convert the noticeElement to a string with the necessary classes and HTML, and add it to the notifications array
				this.notifications.push( `<li class="s11-notifications-notice ${noticeElement.classList}">${noticeElement.innerHTML}</li>` );
			}

			// Remove the original notice element from the DOM
			noticeElement.remove();
		});

		// Set the count of notifications
		this.count = this.notifications.length;
	}
}

// Initialize Floating Links Notifications
new S11FLNotifications();
