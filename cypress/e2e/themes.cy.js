describe( 'Check BD themes', function() {

	it( 'admin can activate a theme', () => {
		cy.activateTheme( 'no_theme' );
		cy.activateTheme( 'default' );
	})
})