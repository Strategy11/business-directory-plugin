<?php

namespace WPBDP\Tests\Plugin\Listings;

use Brain\Monkey\Functions;
use Phake;

use WPBDP\Tests\TestCase;

use WPBDP__Listing_Search;

class SearchTest extends TestCase {

    public function test_terms_for_field() {
        $search_tree = array();
        $request = array( 'kw' => 'multiple words' );

        $field_id = rand() + 1;
        $field = Phake::mock( 'WPBDP_Form_Field' );

        Phake::when( $field )->get_id->thenReturn( $field_id );

        Functions\expect( 'wpbdp_get_option' )
            ->once()
            ->with( 'quick-search-fields' )
            ->andReturn( array( $field_id ) );
 
        require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';
        $listing_search = new WPBDP__Listing_Search( $search_tree, $request );

        /* Execution */
        $terms = $listing_search->get_original_search_terms_for_field( $field );

        /* Verification */
        $this->assertEquals( array( 'multiple words' ), $terms );
    }

    public function test_empty_kw_quick_search_parse_request_returns_empty_array() {
        $request = array( 'kw' => '' );
 
        require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';
        $tree = WPBDP__Listing_Search::parse_request( $request );

        /* Verification */
        $this->assertEquals( array(), $tree );
    }
}
