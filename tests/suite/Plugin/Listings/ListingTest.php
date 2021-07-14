<?php

use Brain\Monkey\Functions;

use function Patchwork\always;

use WPBDP\Tests\TestCase;

class ListingTest extends TestCase {

    public function setup() {
        parent::setup();
    }

    /**
     * Setting $append to true is necessary to avoid clearing the value of
     * the post_title, post_content, post_excerpt and any other Post attribute
     * associated with a field that has no value assigned in the $state object.
     *
     * See https://github.com/drodenbaugh/BusinessDirectoryPlugin/issues/2884.
     */
    public function test_update_calls_set_field_values_with_append_set_to_true() {
        // The update method no longer exists. The test needs to be updated to
        // work with wpbdp_save_listing().
        $this->markTestSkipped();

        $state = (object) array( 'fields' => array() );
        $post_data = array( 'post_title' => 'Test Listing' );
        $append = null;

        $this->redefine( 'WPBDP_Listing::get_post_data_from_state', always( $post_data ) );
        $this->redefine( 'WPBDP_Listing::set_field_values', function( $a, $b ) use ( &$append ) {
            $append = $b;
        } );

        Functions\expect( 'wp_update_post' )->once();

        $listing = new WPBDP_Listing( null );

        // Execution
        $listing->update( $state );

        // Verification
        $this->assertTrue( $append );
    }
}
