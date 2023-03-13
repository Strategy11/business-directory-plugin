// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add('login', (username, password) => {
	cy.session([username, password], () => {
		cy.visit( '/wp-login.php' )
		cy.wait( 1000 )
		cy.get( '#user_login' ).type( username )
		cy.get( '#user_pass' ).type( password )
		cy.get( '#wp-submit' ).click();
		cy.url().should('contain', '/wp-admin')
	})
})

Cypress.Commands.add('loginAdmin', () => {
	cy.login( Cypress.env( 'wpUser' ), Cypress.env( 'wpPassword' ) )
})

Cypress.Commands.add('loginEditor', () => {
	cy.login( 'editor', 'editor' )
})

Cypress.Commands.add('loginAuthor', () => {
	cy.login( 'author', 'author' )
})

Cypress.Commands.add('loginSubscriber', () => {
	cy.login( 'subscriber', 'subscriber' )
})

Cypress.Commands.add('activateTheme', (theme) => {
	cy.login( Cypress.env( 'wpUser' ), Cypress.env( 'wpPassword' ) )
	cy.visit( '/wp-admin/admin.php?page=wpbdp-themes' )
	cy.get( '.wpbdp-theme.' + theme ).then( ( $btn ) => {
		if ( $btn.hasClass( 'wpbdp-addon-active' ) ) {
			// If the theme is already active, leave it be.
		} else {
			cy.get( '.wpbdp-theme.' + theme + ' input[type="submit"]' ).click()
		}
		cy.get( '.wpbdp-theme.wpbdp-addon-active' )
			.should('have.length', 1)
			.should('have.class', theme )
	})
})