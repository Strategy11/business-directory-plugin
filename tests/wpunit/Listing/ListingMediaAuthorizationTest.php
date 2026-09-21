<?php
/**
 * Authorization tests for listing Media Library attach and image delete.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Listing_Image;

/**
 * Tests for listing media authorization.
 */
class ListingMediaAuthorizationTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		$this->clear_request();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testContributorCannotAttachForeignMediaLibraryImage() {
		$admin_id       = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$contributor_id = $this->get_or_create_user( 'contributor', 'media-contributor@example.com' );
		$listing_id     = $this->create_listing( $contributor_id );
		$admin_post_id  = $this->create_regular_post( $admin_id );
		$image_id       = $this->create_image_attachment( $admin_id, $admin_post_id );

		wp_set_current_user( $contributor_id );
		$response = $this->dispatch_media_image( $listing_id, $image_id );

		$this->assertFalse( $response['success'] );
		$this->assertSame( $admin_post_id, (int) wp_get_post_parent_id( $image_id ) );
		$this->assertNotNull( get_post( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAuthorCannotAttachAdministratorImage() {
		$admin_id      = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$author_id     = $this->get_or_create_user( 'author', 'media-author@example.com' );
		$listing_id    = $this->create_listing( $author_id );
		$admin_post_id = $this->create_regular_post( $admin_id );
		$image_id      = $this->create_image_attachment( $admin_id, $admin_post_id );

		wp_set_current_user( $author_id );
		$response = $this->dispatch_media_image( $listing_id, $image_id );

		$this->assertFalse( $response['success'] );
		$this->assertSame( $admin_post_id, (int) wp_get_post_parent_id( $image_id ) );
		$this->assertNotNull( get_post( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testMixedSelectionIsRejectedAtomically() {
		$admin_id      = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$author_id     = $this->get_or_create_user( 'author', 'media-author@example.com' );
		$listing_id    = $this->create_listing( $author_id );
		$admin_post_id = $this->create_regular_post( $admin_id );
		$foreign_id    = $this->create_image_attachment( $admin_id, $admin_post_id );
		$own_id        = $this->create_image_attachment( $author_id, 0 );

		wp_set_current_user( $author_id );
		$response = $this->dispatch_media_image( $listing_id, array( $foreign_id, $own_id ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( $admin_post_id, (int) wp_get_post_parent_id( $foreign_id ) );
		$this->assertSame( 0, (int) wp_get_post_parent_id( $own_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testReparentedForeignImageCannotBeDeletedAfterUpgrade() {
		$admin_id       = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$contributor_id = $this->get_or_create_user( 'contributor', 'media-contributor@example.com' );
		$listing_id     = $this->create_listing( $contributor_id );
		$image_id       = $this->create_image_attachment( $admin_id, $this->create_regular_post( $admin_id ) );

		wp_update_post(
			array(
				'ID'          => $image_id,
				'post_parent' => $listing_id,
			)
		);

		wp_set_current_user( $contributor_id );
		$response = $this->dispatch_image_delete( $listing_id, $image_id );

		$this->assertFalse( $response['success'] );
		$this->assertNotNull( get_post( $image_id ) );
		$this->assertSame( $listing_id, (int) wp_get_post_parent_id( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testNonImageAttachmentIsRejected() {
		$admin_id   = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$listing_id = $this->create_listing( $admin_id );
		$file_id    = $this->create_file_attachment( $admin_id, 0 );

		wp_set_current_user( $admin_id );
		$response = $this->dispatch_media_image( $listing_id, $file_id );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, (int) wp_get_post_parent_id( $file_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testCrossListingAttachmentIsRejected() {
		$admin_id         = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$listing_id       = $this->create_listing( $admin_id );
		$other_listing_id = $this->create_listing( $admin_id );
		$image_id         = $this->create_image_attachment( $admin_id, $other_listing_id );

		wp_set_current_user( $admin_id );
		$response = $this->dispatch_media_image( $listing_id, $image_id );

		$this->assertFalse( $response['success'] );
		$this->assertSame( $other_listing_id, (int) wp_get_post_parent_id( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdministratorCanAttachOwnUnattachedImage() {
		$admin_id   = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$listing_id = $this->create_listing( $admin_id );
		$image_id   = $this->create_image_attachment( $admin_id, 0 );

		wp_set_current_user( $admin_id );
		$response = $this->dispatch_media_image( $listing_id, $image_id );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $listing_id, (int) wp_get_post_parent_id( $image_id ) );
		$this->assertStringContainsString( 'wpbdp-image-delete-link', $response['data']['html'] );
	}

	/**
	 * @since x.x
	 */
	public function testGuestCanDeleteOwnedListingImage() {
		$listing_id = wp_insert_post(
			array(
				'post_author' => 0,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'auto-draft',
				'post_title'  => 'Guest listing media test',
			)
		);
		$this->assertTrue( is_int( $listing_id ) );

		$listing   = wpbdp_get_listing( $listing_id );
		$token     = $listing->get_submit_token();
		$image_id  = $this->create_image_attachment( 0, $listing_id );

		wp_set_current_user( 0 );
		$_REQUEST['listing_submit_token'] = $token;
		$_GET['listing_submit_token']     = $token;

		$response = $this->dispatch_image_delete( $listing_id, $image_id );

		$this->assertTrue( $response['success'] );
		$this->assertNull( get_post( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testContributorWithoutUploadFilesDoesNotReceiveMediaSelector() {
		$contributor_id = $this->get_or_create_user( 'contributor', 'media-contributor@example.com' );
		$listing_id     = $this->create_listing( $contributor_id );

		wp_set_current_user( $contributor_id );
		set_current_screen( 'edit' );

		$html = wpbdp_render(
			'submit-listing-images-upload-form',
			array(
				'listing_id'      => $listing_id,
				'admin'           => true,
				'conditions'      => array(),
				'slots'           => 1,
				'slots_available' => 1,
			),
			false
		);

		set_current_screen( 'front' );

		$this->assertStringNotContainsString( 'wpbdp_media_manager', $html );
		$this->assertStringNotContainsString( 'image-from-media', $html );
	}

	/**
	 * @since x.x
	 */
	public function testNormalizeImageIdsAcceptsCommaSeparatedValues() {
		$this->assertSame( array( 12, 34 ), WPBDP_Listing_Image::normalize_image_ids( '12,34' ) );
		$this->assertSame( array( 12 ), WPBDP_Listing_Image::normalize_image_ids( array( '12', '0', 12 ) ) );
	}

	/**
	 * @since x.x
	 *
	 * @param int       $listing_id Listing ID.
	 * @param int|int[] $image_ids  Attachment ID or IDs.
	 *
	 * @return array
	 */
	private function dispatch_media_image( $listing_id, $image_ids ) {
		global $wpbdp;

		$this->clear_request();
		$_REQUEST['listing_id'] = $listing_id;
		$_REQUEST['image_ids']  = $image_ids;
		$_REQUEST['_wpnonce']   = wp_create_nonce( 'listing-' . $listing_id . '-image-from-media' );
		$_GET['listing_id']     = $listing_id;
		$_GET['_wpnonce']       = $_REQUEST['_wpnonce'];

		return $this->dispatch_ajax(
			function () use ( $wpbdp ) {
				$wpbdp->ajax_listing_media_image();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int $listing_id Listing ID.
	 * @param int $image_id   Attachment ID.
	 *
	 * @return array
	 */
	private function dispatch_image_delete( $listing_id, $image_id ) {
		global $wpbdp;

		$_REQUEST['listing_id'] = $listing_id;
		$_REQUEST['image_id']   = $image_id;
		$_REQUEST['_wpnonce']   = wp_create_nonce( 'delete-listing-' . $listing_id . '-image-' . $image_id );
		$_GET['listing_id']     = $listing_id;
		$_GET['image_id']       = $image_id;
		$_GET['_wpnonce']       = $_REQUEST['_wpnonce'];

		return $this->dispatch_ajax(
			function () use ( $wpbdp ) {
				$wpbdp->ajax_listing_submit_image_delete();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param callable $callback AJAX method.
	 *
	 * @return array
	 */
	private function dispatch_ajax( $callback ) {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_ajax_die_handler' ) );
		add_filter( 'wp_die_handler', array( $this, 'get_ajax_die_handler' ) );
		ob_start();

		try {
			$callback();
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_ajax_die_handler' ) );
		remove_filter( 'wp_die_handler', array( $this, 'get_ajax_die_handler' ) );

		$output   = ob_get_clean();
		$response = json_decode( $output, true );

		$this->assertIsArray( $response );
		$this->assertArrayHasKey( 'success', $response );

		return $response;
	}

	/**
	 * Return a wp_die handler that throws instead of exiting.
	 *
	 * @since x.x
	 *
	 * @return callable
	 */
	public function get_ajax_die_handler() {
		return array( $this, 'handle_ajax_die' );
	}

	/**
	 * Convert an AJAX wp_die into an exception so the suite can continue.
	 *
	 * @since x.x
	 *
	 * @param mixed $message Die message.
	 *
	 * @return void
	 */
	public function handle_ajax_die( $message ) {
		throw new \WPDieException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * @since x.x
	 */
	private function clear_request() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * @since x.x
	 *
	 * @param string $role  User role.
	 * @param string $email User email.
	 *
	 * @return int
	 */
	private function get_or_create_user( $role, $email ) {
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			$user->set_role( $role );
			return (int) $user->ID;
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => sanitize_user( str_replace( '@', '-', $email ) ),
				'user_pass'  => 'password',
				'user_email' => $email,
				'role'       => $role,
			)
		);
		$this->assertTrue( is_int( $user_id ) );

		return $user_id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Listing author.
	 *
	 * @return int
	 */
	private function create_listing( $author_id ) {
		$listing_id = wp_insert_post(
			array(
				'post_author' => $author_id,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing media auth test',
			)
		);
		$this->assertTrue( is_int( $listing_id ) );

		return $listing_id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Post author.
	 *
	 * @return int
	 */
	private function create_regular_post( $author_id ) {
		$post_id = wp_insert_post(
			array(
				'post_author'  => $author_id,
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => 'Admin post with image',
				'post_content' => 'Inline image',
			)
		);
		$this->assertTrue( is_int( $post_id ) );

		return $post_id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Attachment author.
	 * @param int $parent_id Parent post ID.
	 *
	 * @return int
	 */
	private function create_image_attachment( $author_id, $parent_id ) {
		return $this->create_attachment( $author_id, $parent_id, 'image/jpeg' );
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Attachment author.
	 * @param int $parent_id Parent post ID.
	 *
	 * @return int
	 */
	private function create_file_attachment( $author_id, $parent_id ) {
		return $this->create_attachment( $author_id, $parent_id, 'application/pdf' );
	}

	/**
	 * @since x.x
	 *
	 * @param int    $author_id Attachment author.
	 * @param int    $parent_id Parent post ID.
	 * @param string $mime_type Mime type.
	 *
	 * @return int
	 */
	private function create_attachment( $author_id, $parent_id, $mime_type ) {
		$is_image = 0 === strpos( $mime_type, 'image/' );
		$filename = $is_image ? 'listing-media-test.jpg' : 'listing-media-test.pdf';
		$contents = $is_image ? $this->get_minimal_jpeg() : "%PDF-1.4\n";
		$upload   = wp_upload_bits( uniqid( 'listing-media-', true ) . '-' . $filename, null, $contents );

		$this->assertIsArray( $upload );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_author'    => $author_id,
				'post_parent'    => $parent_id,
				'post_status'    => 'inherit',
				'post_title'     => 'Listing media attachment',
				'post_mime_type' => $mime_type,
				'guid'           => $upload['url'],
			),
			$upload['file'],
			$parent_id
		);
		$this->assertTrue( is_int( $attachment_id ) );
		$this->assertTrue( $is_image ? wp_attachment_is_image( $attachment_id ) : ! wp_attachment_is_image( $attachment_id ) );

		return $attachment_id;
	}

	/**
	 * @since x.x
	 *
	 * @return string
	 */
	private function get_minimal_jpeg() {
		return base64_decode(
			'/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAAEAAQMBIgACEQEDEQH/xAAUAAEAAAAAAAAAAAAAAAAAAAAK/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEAMQAAABkw//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/AH//2Q=='
		);
	}
}
