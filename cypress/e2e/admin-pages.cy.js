describe( 'Check admin menu', function() {

	it( 'user roles include BD nav', () => {
		cy.loginAdmin()
		cy.visit( '/wp-admin/admin.php?page=wpbdp_settings' )
		cy.get('#toplevel_page_wpbdp_admin').should('have.length', 1)
		cy.get('#toplevel_page_wpbdp_admin li').should('have.length', 7)

		cy.loginEditor()
		cy.visit( '/wp-admin/' )
		cy.get('#toplevel_page_wpbdp_admin li').should('have.length', 2)

		cy.loginAuthor()
		cy.visit( '/wp-admin/' )
		cy.get('#toplevel_page_wpbdp_admin li').should('have.length', 2)

		cy.loginSubscriber()
		cy.visit( '/wp-admin/' )
		cy.get('#toplevel_page_wpbdp_admin').should('have.length', 0)
	})

	it( 'admin can submit a listing', () => {
		var $listingUrl = '/wp-admin/edit.php?post_type=wpbdp_listing';
		cy.loginAdmin()
		cy.visit( $listingUrl )
		cy.get( '.wpbdp-content-area-header-actions a' ).click()
		cy.url().should('contain', 'post-new.php?post_type=wpbdp_listing')

		// Set the title.
		cy.get( '.wp-block-post-title' ).type( 'Test Listing' )

		// Set the plan.
		cy.get( '#wpbdp-listing-plan-select' ).next( 'p' ).children( 'a.update-value' ).click()

		// Add a paragraph block.
		cy.get( '.block-editor-default-block-appender__content' ).click()
		cy.get( '.wp-block-paragraph' ).type( 'Test paragraph' )

		// Save the post.
		cy.get( '.editor-post-publish-button__button' ).click()
		cy.get( '.editor-post-publish-button' ).click() // Click again to confirm.

		cy.visit( $listingUrl )
		cy.get( '.page-title:first .row-title' ).should( 'have.text', 'Test Listing' )
	})
})