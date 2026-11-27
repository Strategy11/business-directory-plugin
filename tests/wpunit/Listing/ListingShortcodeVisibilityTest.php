<?php
/**
 * Visibility tests for the listing detail shortcodes.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;

/**
 * Tests that listing detail shortcodes only render listings the viewer may see.
 */
class ListingShortcodeVisibilityTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Form fields are only loaded once the plugin knows its db version, which is
	 * read before the test install runs.
	 *
	 * @since x.x
	 */
	protected function after_setup() {
		$db_version = new \ReflectionProperty( 'WPBDP', '_db_version' );
		$db_version->setAccessible( true );
		$db_version->setValue( wpbdp(), get_option( 'wpbdp-db-version' ) );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function hiddenStatusProvider() {
		return array(
			'pending' => array( 'pending' ),
			'draft'   => array( 'draft' ),
			'private' => array( 'private' ),
			'trash'   => array( 'trash' ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider hiddenStatusProvider
	 *
	 * @param string $status Listing status.
	 */
	public function testVisitorDoesNotSeeUnpublishedListingById( $status ) {
		$listing = $this->create_listing( $status );

		$this->assertStringNotContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['id'] ) );
		$this->assertStringNotContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['slug'] ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider hiddenStatusProvider
	 *
	 * @param string $status Listing status.
	 */
	public function testContributorDoesNotSeeOtherUsersUnpublishedListing( $status ) {
		$listing = $this->create_listing( $status );
		wp_set_current_user( $this->create_user( 'contributor' ) );

		$this->assertStringNotContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['id'] ) );
		$this->assertStringNotContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['slug'] ) );
	}

	/**
	 * @since x.x
	 */
	public function testVisitorSeesPublishedListing() {
		$listing = $this->create_listing( 'publish' );

		$this->assertStringContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['id'] ) );
		$this->assertStringContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['slug'] ) );
	}

	/**
	 * The listing page itself already decided the viewer may see it.
	 *
	 * @since x.x
	 */
	public function testOwnerSeesOwnPrivateListingOnItsPage() {
		$owner_id = $this->create_user( 'subscriber' );
		$listing  = $this->create_listing( 'private', $owner_id );
		wp_set_current_user( $owner_id );
		$this->go_to( get_permalink( $listing['id'] ) );

		$this->assertTrue( is_singular( WPBDP_POST_TYPE ) );
		$this->assertStringContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['id'] ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdministratorSeesPendingListing() {
		$listing = $this->create_listing( 'pending' );
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$this->assertStringContainsString( $listing['marker'], $this->render( 'businessdirectory-details', $listing['id'] ) );
	}

	/**
	 * @since x.x
	 */
	public function testNonListingIdRendersNothing() {
		$post_id = self::factory()->post->create( array( 'post_content' => 'not-a-listing-marker' ) );

		$this->assertStringNotContainsString( 'not-a-listing-marker', $this->render( 'businessdirectory-details', $post_id ) );
	}

	/**
	 * @since x.x
	 *
	 * @param string     $shortcode  Shortcode name and extra attributes.
	 * @param int|string $id_or_slug Listing id or slug.
	 *
	 * @return string
	 */
	private function render( $shortcode, $id_or_slug ) {
		return do_shortcode( '[' . $shortcode . ' id="' . $id_or_slug . '"]' );
	}

	/**
	 * @since x.x
	 *
	 * @param string $status    Listing status.
	 * @param int    $author_id Listing author.
	 *
	 * @return array Listing id, slug and description marker.
	 */
	private function create_listing( $status, $author_id = 1 ) {
		$marker = 'visibility-marker-' . $status . '-' . wp_generate_password( 8, false );
		$slug   = 'visibility-listing-' . $status . '-' . wp_generate_password( 6, false );
		$id     = wp_insert_post(
			array(
				'post_author'  => $author_id,
				'post_type'    => WPBDP_POST_TYPE,
				'post_status'  => 'trash' === $status ? 'publish' : $status,
				'post_title'   => 'Visibility listing ' . $status,
				'post_name'    => $slug,
				'post_content' => $marker,
			)
		);
		$this->assertTrue( is_int( $id ) );

		if ( 'trash' === $status ) {
			wp_trash_post( $id );
			$slug = get_post_field( 'post_name', $id );
		}

		return compact( 'id', 'slug', 'marker' );
	}

	/**
	 * @since x.x
	 *
	 * @param string $role User role.
	 *
	 * @return int
	 */
	private function create_user( $role ) {
		return self::factory()->user->create( array( 'role' => $role ) );
	}
}
