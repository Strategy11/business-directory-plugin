<?php

namespace WBBDP\Tests\Plugin\Compatibitliy;

use Brain\Monkey\Functions;
use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP__CPT_Compat_Mode;

class CPTCompatModeTest extends TestCase {

	/**
	 * Test for https://github.com/drodenbaugh/BusinessDirectoryPlugin/issues/2843
	 */
	public function test_maybe_change_current_view_triggers_a_404_when_listing_slug_or_id_doesnt_match() {
		Functions\expect( 'wpbdp_get_option' )
			->atLeast()->once()
			->with( 'permalinks-directory-slug' )
			->andReturn( 'wpbdp_listing' );

		Functions\expect( 'wpbdp_get_option' )
			->atLeast()->once()
			->andReturn( null );

		Functions\expect( 'get_query_var' )
			->atLeast()->once()
			->with( '_wpbdp_listing' )
			->andReturn( 'business-name-that-doesnt-exist' );

		Functions\when( 'wpbdp_get_post_by_id_or_slug' )->justReturn( null );

		$GLOBALS['wp_query'] = Mockery::mock();

		$GLOBALS['wp_query']->shouldReceive( 'set_404' );
		$GLOBALS['wp_query']->shouldReceive( 'set' )->with( 'p', null );
		$GLOBALS['wp_query']->shouldReceive( 'set' )->with( 'page_id', null );

		// Execution
		require_once WPBDP_PATH . 'includes/compatibility/class-cpt-compat-mode.php';
		$compat = new WPBDP__CPT_Compat_Mode();

		$new_view = $compat->maybe_change_current_view( 'something' );

		// Verification
		$this->assertNull( $new_view );
	}
}
