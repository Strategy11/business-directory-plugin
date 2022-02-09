jQuery( function( $ ) {
	var wpbdp_admin_notification_center = {
		notificationContainer: null,
		preAdminNotifications : null,
		adminNotifications: null,
		buttonNotification: null,
		closeButton: null,

		init: function() {
			//We get the notification center
			this.notificationContainer = $( '.wpbdp-bell-notifications' );
	
			//We get all the notifications to display in the modal
			this.preAdminNotifications = $( '.wpbdp-notice, .wpbdp-notice:hidden' );

			// Notifications container
			this.adminNotifications = this.notificationContainer.find( '.wpbdp-bell-notifications-list' );

			//We get the notification button
			this.buttonNotification = $( '.wpbdp-bell-notification-icon' );
	
			//We get the close button
			this.closeButton = $( '.wpbdp-bell-notifications-close' );

			this.onClickNotifications();
			this.initCloseNotifications();
	
			this.parseNotifications();
			this.handleDismissAction();
		},

		onClickNotifications: function() {
			wpbdp_admin_notification_center.buttonNotification.on( 'click', function(e) {
				e.preventDefault();
				wpbdp_admin_notification_center.notificationContainer.toggleClass( 'hidden' );
			});
		},

		initCloseNotifications: function() {
			wpbdp_admin_notification_center.closeButton.on( 'click', function(e) {
				e.preventDefault();
				wpbdp_admin_notification_center.notificationContainer.addClass( 'hidden' );
			});
		},

		parseNotifications: function() {
			if ( wpbdp_admin_notification_center.preAdminNotifications.length < 1 ){
				return true;
			}
			var notifications = [],
				snackbars = [];
			wpbdp_admin_notification_center.preAdminNotifications.each( function() {
				var notification = $(this);
				if ( notification.hasClass( 'wpbdp-upgrade-bar' ) ) {
					return false;
				}
				if ( notification.hasClass( 'wpbdp-notice' ) ) {
					if ( notification.hasClass( 'wpbdp-snackbar-notice' ) ) {
						snackbars.push( notification.html() );
					} else {
						notifications.push( '<li class="wpbdp-bell-notice ' + this.classList + '">' + notification.html() + '</li>' );
					}
				}
				if ( ! notification.hasClass( 'wpbdp-review-notice' ) ) {
					notification.remove();
				}
			});
			notifications = wpbdp_admin_notification_center.removeDuplicates( notifications );
			snackbars = wpbdp_admin_notification_center.removeDuplicates( snackbars );
			wpbdp_admin_notification_center.adminNotifications.append( notifications.join( ' ') );
			if ( notifications.length > 0 ) {
				$( '.wpbdp-bell-notification' ).show();
				wpbdp_admin_notification_center.notificationContainer.removeClass( 'hidden' );
			}
			if ( snackbars.length > 0 ) {
				snackbars.forEach( function( value, index, array ) {
					wpbdp_admin_notification_center.generateSnackBar( value );
				});
			}
		},

		/**
		 * Render the snack bar
		 * 
		 * @param {string} notification 
		 */
		generateSnackBar : function( notification ) {
			var id = Date.now(),
				container_id = 'wpbdp-snackbar-' + id;
			var snackbar = $( '<div>', {
				id: container_id,
				class: 'wpbdp-snackbar'
			});
			snackbar.html( notification );
			$( 'body' ).append(snackbar);
			setTimeout( function(){ snackbar.remove(); }, 2500);
		},

		removeDuplicates : function( arr ) {
			var uniq = {};
			arr.forEach( function( item ) { uniq[item] = true; } );
			return Object.keys( uniq );
		},

		handleDismissAction : function() {
			$( document ).on( 'click', '.wpbdp-bell-notifications-list .notice-dismiss', function( e ) {
				e.preventDefault();
				var $button = $( this ),
					$notice = $button.parent( '.wpbdp-bell-notice' ),
					dismissible_id = $button.data( 'dismissible-id' ),
					nonce = $button.data( 'nonce' );

				wpbdp_admin_notification_center.dismissNotice( $notice, dismissible_id, nonce );
			} );
			$( document ).on( 'click', '.wpbdp-snackbar .notice-dismiss', function( e ) {
				e.preventDefault();
				var $button = $( this ),
					$notice = $button.parent( '.wpbdp-bell-notice' ),
					dismissible_id = $button.data( 'dismissible-id' ),
					nonce = $button.data( 'nonce' );

				wpbdp_admin_notification_center.dismissNotice( $notice, dismissible_id, nonce );
			} );
		},

		hideNotificationCenter : function() {
			if ( wpbdp_admin_notification_center.adminNotifications.find( 'li' ).length < 1 ) {
				wpbdp_admin_notification_center.notificationContainer.addClass( 'hidden' );
				$( '.wpbdp-bell-notification' ).hide();
			}
		},

		dismissNotice : function( $notice, notice_id, nonce ) {
			$.post( ajaxurl, {
                action: 'wpbdp_dismiss_notification',
                id: notice_id,
                nonce: nonce
            }, function() {
                $notice.fadeOut( 'fast', function(){ $notice.remove(); } );
            } );
		}
	};
	wpbdp_admin_notification_center.init();
});