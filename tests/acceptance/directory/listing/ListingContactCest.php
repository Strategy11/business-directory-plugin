<?php

/**
 * Test listing contact form
 */
class ListingContactCept {

    private $listing = false;

	public function _before( AcceptanceTester $I ) {
        $listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Public Listing',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
            $this->listing = $listing;
        }
	}


    public function contactForm( AcceptanceTester $I ) {
        $this->tester->wantToTest( 'Test the listing contact form' );
        if ( $this->listing ) {
            $listing_id = $this->listing->get_id();
            $slug = sanitize_title( $this->listing_title );
		    $I->amOnPage( '/business-directory/' . $slug );
            $I->seeInTitle( $this->listing->get_title() );

            $I->see( 'Send Message to listing owner', 'h3' );
            $I->click( 'Send' );
            $I->seeElement( '.wpbdp-error' ); //Error has been triggered
            $I->fillField( 'commentauthorname', "Sample User" );
            $I->fillField( 'commentauthoremail', "hello@example.com" );
            $I->fillField( 'commentauthorphone', "1234567890" );
            $I->fillField( 'commentauthormessage', "This is just to say hello" );
            $I->click( 'Send' );
            $I->see( 'Your message has been sent.', 'div' );
        } else {
            $this->assertFalse( $this->listing, 'Error creating listing' );
        }
    }
}
