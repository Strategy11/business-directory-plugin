<?php

/**
 * Crude test for creating directories
 */
class DirectoryCrudCept {

    private $listing_title = '';

    public function _before( AcceptanceTester $I ) {
		$I->wantTo('log in to site');
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=wpbdp_listing' );
        $this->listing_title = 'Sample Test Listing';
    }

    public function _after( AcceptanceTester $I ) {

    }

    public function createDirectory( AcceptanceTester $I ) {
        $i->amGoingTo( 'Create a new listing' );
		$I->click( 'Add New Listing' );
		$I->fillField( 'post_title', $this->listing_title );
		//Fields do not have proper names. Very hard to determine

        foreach ( wpbdp_get_form_fields( array( 'association' => 'meta' ) ) as $field ) {
            $field_id = $field->get_id();
            if ( strtolower( $field->get_label() ) === 'email' ) {
                $I->fillField( array( 'name' => 'listingfields['.$field_id.']' ), 'jon@example.com' );
            }
        }
        $I->click( 'Publish' );
        $I->see( 'Post published.', 'p' );
        $I->click( array( 'link' => 'View post') );
        $I->seeInTitle( $this->listing_title );
    }

    public function editDirectory( AcceptanceTester $I ) {
        $slug       = sanitize_title( $this->listing_title );
        $listing_id = wpbdp_get_post_by_id_or_slug( $slug, 'id', 'id' );
        $i->amGoingTo( 'Edit a listing' );
        if ( $listing_id ) {
            $I->amOnPage( '/wp-admin/post.php?post='. $listing_id .'&action=edit' );
            $I->seeInCurrentUrl('edit');
            $I->see( 'Update', 'input' );
            $I->click( 'Update' );
            $I->see( 'Post updated.', 'p' );
        } else {
            $I->dontSee( 'Edit Listing' );  
        }
    }

    public function viewDirectory( AcceptanceTester $I ) {
        $slug = sanitize_title( $this->listing_title );
        $I->amOnPage( '/business-directory/' . $slug );
        $I->seeInTitle( $this->listing_title );
    }

    public function deleteDirectory( AcceptanceTester $I ) {
        $I->amOnPage( '/wp-admin/edit.php?post_type=wpbdp_listing' );
        $listing_id = wpbdp_get_post_by_id_or_slug( $slug, 'id', 'id' );
        $i->amGoingTo( 'Delete a listing' );
        if ( $listing_id ) {
            $I->click( '#post-'.$listing_id.' a.submitdelete');
        }
    }
}