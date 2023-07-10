/**
 * Class representing the functionality to show notifications.
 *
 * @class S11FLNotifications
 */
class S11FLNotifications {

	/**
	 * Create a new S11FLNotifications instance.
	 *
	 * @constructor
	 */
	constructor() {
		wp.hooks.addAction( 's11_floating_links_init', 'S11FloatingLinks', ( S11FloatingLinks ) => {
			this.floatingLinks = S11FloatingLinks;
			this.initComponent();

			// Trigger the 's11_floating_links_notifications_init' action
			wp.hooks.doAction( 's11_floating_links_notifications_init', this );
		});
	}

	/**
	 * Initializes the S11FLNotifications component by setting up the notifications and creating the necessary DOM elements.
	 * This involves setting up the notifications, creating the required DOM elements.
	 *
	 * @memberof S11FLNotifications
	 */
	initComponent() {
		// Set the references to the necessary DOM elements
		this.setIconElement();
		this.setWrapperElement();
		this.setDismissButtons();
		this.setHideButton();

		// Create and append the count element
		this.createCount();

		// Add event listeners for user interaction
		this.addClickEventListener();
	}

	/**
	 * Retrieves and sets the reference to the notifications icon element.
	 *
	 * @memberof S11FLNotifications
	 */
	setIconElement() {
		const navMenuElement = this.floatingLinks.navMenuElement;
		this.iconElement = navMenuElement.querySelector( '.s11-notifications-icon' );
	}

	/**
	 * Retrieves and sets the reference to the wrapper element. Also modifies the element's classList.
	 *
	 * @memberof S11FLNotifications
	 */
	setWrapperElement() {
		this.wrapperElement = document.querySelector( '.wpbdp-bell-notifications' );
		this.wrapperElement.classList.remove( 'hidden' );

		this.setNoticeElements();
		this.setCount();

		if ( this.count < 1 ) {
			this.wrapperElement.classList.add( 's11-fadeout' );
		} else {
			this.wrapperElement.classList.add( 's11-fadein', 's11-visible' );
		}
	}

	/**
	 * Retrieves and sets the reference to the notice elements within the wrapper element.
	 *
	 * @memberof S11FLNotifications
	 */
	setNoticeElements() {
		this.noticeElements = this.wrapperElement.querySelectorAll( '.wpbdp-bell-notice' );
	}

	/**
	 * Counts the notice elements and sets the count property.
	 *
	 * @memberof S11FLNotifications
	 */
	setCount() {
		this.count = this.noticeElements.length;
	}

	/**
	 * Retrieves and sets the reference to the dismiss buttons within the wrapper element.
	 *
	 * @memberof S11FLNotifications
	 */
	setDismissButtons() {
		this.dismissButtonElements = this.wrapperElement.querySelectorAll(
			'.wpbdp-notice.is-dismissible > .notice-dismiss, .wpbdp-notice .wpbdp-notice-dismiss'
		);
	}

	/**
	 * Retrieves and sets the reference to the hide button within the wrapper element.
	 *
	 * @memberof S11FLNotifications
	 */
	setHideButton() {
		this.hideButtonElement = this.wrapperElement.querySelector( '.wpbdp-bell-notifications-close' );
	}

	/**
	 * Creates a count element and adds it to the icon element. Also creates a clone and adds it to another icon element.
	 *
	 * @memberof S11FLNotifications
	 */
	createCount() {
		if ( this.count < 1 ) {
			this.iconElement.remove();

			return;
		}

		// Create the count element
		this.countElement = document.createElement( 'span' );
		this.countElement.classList.add( 's11-notifications-count' );
		this.countElement.textContent = this.count;

		// Append the count element to the icon element
		this.floatingLinks.iconButtonElement.appendChild( this.countElement );

		// Create a copy of the count element
		const countElementClone = this.countElement.cloneNode( true );
		this.iconElement.appendChild( countElementClone );
	}

	/**
	 * Adds click event listeners to the icon element, dismiss buttons, and hide button
	 *
	 * @memberof S11FLNotifications
	 */
	addClickEventListener() {
		this.iconElement?.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			this.wrapperElement.classList.toggle( 's11-visible' );
			this.floatingLinks.toggleFade( this.wrapperElement );
			this.floatingLinks.toggleFade( this.floatingLinks.navMenuElement );
			this.floatingLinks.switchIconButton( this.floatingLinks.closeIcon );
		});

		this.dismissButtonElements.forEach( ( dismissButton ) => {
			dismissButton.addEventListener( 'click', ( event ) => {
				const countElements = this.floatingLinks.wrapperElement.querySelectorAll( '.s11-notifications-count' );
				this.count--;

				if ( this.count === 0 ) {
					this.iconElement.remove();
					this.floatingLinks.iconButtonElement.querySelector( '.s11-notifications-count' ).remove();
					this.wrapperElement.classList.remove( 's11-visible' );
					this.floatingLinks.toggleFade( this.wrapperElement );
				}

				countElements.forEach( ( countElement ) => {
					countElement.textContent = this.count;
				});
			});
		});

		this.hideButtonElement.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			this.floatingLinks.toggleFade( this.wrapperElement );
			this.wrapperElement.classList.remove( 's11-visible' );
		});
	}
}

// Initialize Floating Links Notifications
new S11FLNotifications();
