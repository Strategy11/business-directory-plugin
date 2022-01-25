jQuery( function( $ ) {
	/**
	 * Admin layout
	 */
	 var WPBDPAdmin_Layout = {
		$nav_toggle : null,
		$layout_container : null,
		$menu_items : null,
		$menu_state : null,
		$header : null,

		init: function() {
			WPBDPAdmin_Layout.$nav_toggle = $( '.wpbdp-nav-toggle' );
			WPBDPAdmin_Layout.$layout_container = $( '.wpbdp-admin-row' );
			WPBDPAdmin_Layout.$menu_items = WPBDPAdmin_Layout.$layout_container.find( '.wpbdp-nav-item a' );
			WPBDPAdmin_Layout.$header = WPBDPAdmin_Layout.$layout_container.find( '.wpbdp-content-area-header ' );
			WPBDPAdmin_Layout.$menu_state = window.localStorage.getItem( '_wpbdp_admin_menu' );
			WPBDPAdmin_Layout.$nav_toggle.click( WPBDPAdmin_Layout.onNavToggle );
			WPBDPAdmin_Layout.headerScoll();
			WPBDPAdmin_Layout.initTaxonomyModal();
			WPBDPAdmin_Layout.initDeletePlanModal();
			WPBDPAdmin_Layout.initDeleteFieldModal();
			if ( WPBDPAdmin_Layout.$menu_state && WPBDPAdmin_Layout.$menu_state == 'minimized' ) {
				WPBDPAdmin_Layout.$layout_container.addClass( 'minimized' );
			}
		},

		onNavToggle: function( e ) {
			e.preventDefault();
			WPBDPAdmin_Layout.$layout_container.toggleClass( 'minimized' );
			if ( WPBDPAdmin_Layout.$layout_container.hasClass( 'minimized' ) ) {
				window.localStorage.setItem( '_wpbdp_admin_menu', 'minimized' );
				WPBDPAdmin_Layout.$menu_items.addClass( 'wpbdp-nav-tooltip' );
			} else {
				window.localStorage.removeItem( '_wpbdp_admin_menu' );
				WPBDPAdmin_Layout.$menu_items.removeClass( 'wpbdp-nav-tooltip' );
			}
		},

		headerScoll : function() {
			var sticky = WPBDPAdmin_Layout.$header.offset().top;
			if ( ! window.matchMedia( 'screen and (max-width: 768px)' ).matches ) {
				WPBDPAdmin_Layout.headerSetScrollClass( sticky );
				window.onscroll = function() {
					WPBDPAdmin_Layout.headerSetScrollClass( sticky );
				};
			}
		},

		headerSetScrollClass : function( sticky ) {
			if ( window.pageYOffset > sticky ) {
				WPBDPAdmin_Layout.$header.addClass( 'wpbdp-header-scroll' );
			} else {
				WPBDPAdmin_Layout.$header.removeClass( 'wpbdp-header-scroll' );
			}
		},

		initTaxonomyModal : function() {
			var modal = WPBDPAdmin_Layout.initModal( '#wpbdp-add-taxonomy-form' );
			if ( modal === false ) {
				return;
			}
			$( document ).on( 'click', '.wpbdp-add-taxonomy-form', function( e ) {
				e.preventDefault();
				$( '#wpbdp-add-taxonomy-form .term-slug-wrap' ).addClass( 'hidden' );
				$( '#wpbdp-add-taxonomy-form .term-description-wrap' ).addClass( 'hidden' );
				modal.dialog( 'open' );
			})
		},

		initDeletePlanModal : function() {
			var modal = WPBDPAdmin_Layout.initModal( '#wpbdp-fee-delete-modal' );
			if ( modal === false ) {
				return;
			}
			$( document ).on( 'click', '.wpbdp-admin-fee-delete', function( e ) {
				e.preventDefault();
				var $elem = $( this ),
					$id = $elem.attr( 'data-id' ),
					$name = $elem.attr( 'data-name' ),
					$form = $( '#wpbdp-fee-delete-modal form' );
				$form.find( 'input[name="id"]' ).val( $id );
				$form.find( '.plan-name' ).html( $name );
				modal.dialog( 'open' );
			})
		},

		initDeleteFieldModal : function() {
			var modal = WPBDPAdmin_Layout.initModal( '#wpbdp-field-delete-modal' );
			if ( modal === false ) {
				return;
			}
			$( document ).on( 'click', '.wpbdp-admin-field-delete', function( e ) {
				e.preventDefault();
				var $elem = $( this ),
					$id = $elem.attr( 'data-id' ),
					$name = $elem.attr( 'data-name' ),
					$form = $( '#wpbdp-field-delete-modal form' );
				$form.find( 'input[name="id"]' ).val( $id );
				$form.find( '.field-name' ).html( $name );
				modal.dialog( 'open' );
			})
		},

		initModal : function( id, width ) {
			var $info = $( id );
			if ( $info.length < 1 ) {
				return false;
			}

			if ( typeof width === 'undefined' ) {
				width = '550px';
			}

			$info.dialog({
				dialogClass: 'wpbdp-admin-dialog',
				modal: true,
				autoOpen: false,
				closeOnEscape: true,
				width: width,
				resizable: false,
				draggable: false,
				open: function() {
					$( '.ui-dialog-titlebar' ).addClass( 'hidden' ).removeClass( 'ui-helper-clearfix' );
					$( '#wpwrap' ).addClass( 'wpbdp-overlay' );
					$( '.ui-widget-overlay' ).addClass( 'wpbdp-modal-overlay' );
					$( '.wpbdp-admin-dialog' ).removeClass( 'ui-widget ui-widget-content ui-corner-all' );
					$info.removeClass( 'ui-dialog-content ui-widget-content' );
					WPBDPAdmin_Layout.onCloseModal( $info );
				},
				close: function() {
					$( '#wpwrap' ).removeClass( 'wpbdp-overlay' );
					$( '.ui-widget-overlay' ).removeClass( 'wpbdp-modal-overlay' );
					$( '.spinner' ).css( 'visibility', 'hidden' );

					this.removeAttribute( 'data-option-type' );
					const optionType = document.getElementById( 'bulk-option-type' );
					if ( optionType ) {
						optionType.value = '';
					}
				}
			});

			return $info;
		},

		onCloseModal : function ( $modal ) {
			const closeModal = function( e ) {
				e.preventDefault();
				$modal.dialog( 'close' );
			};
			$( '.ui-widget-overlay' ).on( 'click', closeModal );
			$modal.on( 'click', 'a.dismiss, .dismiss-button', closeModal );
		}
	};

	/**
	 * Tables handle checked states
	 */
	 var WPBDPAdmin_Tables = {
		$bodyCheckboxes : null,
		$selectAllCheckboxes : null,

		init: function() {
			WPBDPAdmin_Tables.$bodyCheckboxes = $( '.wpbdp-admin-page .wp-list-table tbody .check-column input[type="checkbox"]' );
			WPBDPAdmin_Tables.$selectAllCheckboxes = $( '.wpbdp-admin-page .wp-list-table thead .check-column input[type="checkbox"], .wpbdp-admin-page .wp-list-table tfoot .check-column input[type="checkbox"]' );
			WPBDPAdmin_Tables.checkBoxToggle();
			WPBDPAdmin_Tables.selectAllChecked();
		},

		checkBoxToggle : function() {
			WPBDPAdmin_Tables.$bodyCheckboxes.on( 'click', function(){
				WPBDPAdmin_Tables.handleClick( $( this ) );
			});
		},

		selectAllChecked : function() {
			WPBDPAdmin_Tables.$selectAllCheckboxes.on( 'click',  function() {
				var checked = $( this ).is( ':checked' );
				WPBDPAdmin_Tables.$bodyCheckboxes.each( function() {
					WPBDPAdmin_Tables.handleClick( $( this ), checked );
				});
			});
		},

		handleClick : function( checkbox, checked ) {
			var $parent = checkbox.closest( 'tr' );
			if ( typeof checked === 'undefined') {
				checked = checkbox.is( ':checked' );
			}
			WPBDPAdmin_Tables.toggleClass( checked, $parent );
		},

		toggleClass : function( checked, $parent ) {
			if ( checked ) {
				$parent.addClass( 'wpbdp-row-selected' );
			} else {
				$parent.removeClass( 'wpbdp-row-selected' );
			}
		}
	}

	WPBDPAdmin_Layout.init();
	WPBDPAdmin_Tables.init();
});
