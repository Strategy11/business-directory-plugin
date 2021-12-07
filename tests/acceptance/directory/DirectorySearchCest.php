<?php

/**
 * Crude test for searching listings
 */
class DirectorySearchCest {

	private $listing_ids = array();

	public function _before( AcceptanceTester $I ) {
		$this->generateListings();
	}


	protected function _after() {
		foreach ( $this->listing_ids as $listing_id ) {
			$listing = wpbdp_get_listing( $listing_id );
			$listing->delete();
		}
		$this->listing_ids = array();
	}

	public function testListingSearch( AcceptanceTester $I ) {
		$I->wantTo( 'Test Listing Search' );
		for ( $i = 1; $i <= 10; $i++ ) {
			$title = 'Sample Listing ' . $i;
			$slug  = sanitize_title( $title );
			$I->amOnPage( '/directory/' . $slug );
			$I->see( $title, 'h1' );
			$I->seeInTitle( $title );
		}

		$I->amOnPage( '/directory' );
		$I->fillField( 'ks', 'Sample Listing' );
		$I->click( array( 'input' => 'Find Listings' ) );
		$I->see( 'Search Results (9)', 'h3' );
	}

	/**
	 * Generate test listings
	 */
	private function generateListings() {
		wpbdp_set_option( 'new-post-status', 'publish' );

		for ( $i = 1; $i <= 10; $i++ ) {

			$listing_id = wp_insert_post(
				array(
					'post_author' => 1,
					'post_type'   => WPBDP_POST_TYPE,
					'post_status' => 'pending_payment',
					'post_title'  => 'Sample Listing ' . $i,
				)
			);

			$listing = wpbdp_get_listing( $listing_id );

			$listing->set_fee_plan( 1 );

			$payment = $listing->generate_or_retrieve_payment();

			// Execute
			$payment->status = 'completed';
			$payment->save();

			$this->listing_ids[] = $listing_id;
		}
	}
}
