jQuery( function( $ ) {
	var wpbdp_admin_notification_center = {
		notificationContainer: null,
		preAdminNotifications : null,
		adminNotifications: null,
		buttonNotification: null,
		notificationDot: null,
		closeButton: null,

		init: function() {
			//We get the notification center
			this.notificationContainer = $( '.wpbdp-bell-notifications' );
	
			//We get all the notifications to display in the modal
			this.preAdminNotifications = $( '.notice, #message, .wpbdp-notice' );

			// Notifications container
			this.adminNotifications = this.notificationContainer.find( '.wpbdp-bell-notifications-list' );

			//We get the notification button
			this.buttonNotification = $( '.wpbdp-bell-notification-icon' );

			//Notification dot
			this.notificationDot = this.buttonNotification.find( '.wpbdp-bell-notification-dot' );
	
			//We get the close button
			this.closeButton = $( '.wpbdp-bell-notifications-close' );

			this.onClickNotifications();
			this.initCloseNotifications();
	
			setTimeout(function(){
				wpbdp_admin_notification_center.parseNotifications();
			}, 500);
		},

		onClickNotifications: function() {
			wpbdp_admin_notification_center.buttonNotification.on( 'click', function() {
				wpbdp_admin_notification_center.notificationContainer.toggleClass( 'hidden' );
			});
		},

		initCloseNotifications: function() {
			wpbdp_admin_notification_center.closeButton.on( 'click', function() {
				wpbdp_admin_notification_center.notificationContainer.addClass( 'hidden' );
			});
		},

		parseNotifications: function() {
			if ( wpbdp_admin_notification_center.preAdminNotifications.length < 1 ){
				wpbdp_admin_notification_center.notificationDot.removeClass( 'wpbdp-bell-notification-dot-active' );
				return true;
			}
			wpbdp_admin_notification_center.notificationDot.addClass( 'wpbdp-bell-notification-dot-active' );
			wpbdp_admin_notification_center.preAdminNotifications.each( function() {
				var notification = $(this);
				if ( ! notification.hasClass( 'wpbdp-review-notice' ) || ! notification.hasClass( 'wpbdp-notice' ) ) {
					notification.remove();
				}
				if ( notification.hasClass( 'wpbdp-notice' ) ) {
					wpbdp_admin_notification_center.adminNotifications.append( '<li>' + notification.html() + '</li>');
				}
				notification.remove();
			});
		}
	};
	wpbdp_admin_notification_center.init();
});