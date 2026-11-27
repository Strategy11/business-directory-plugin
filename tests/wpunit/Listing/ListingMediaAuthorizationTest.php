<?php
/**
 * Authorization tests for listing Media Library attach and image delete.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;
use WPBDP__Views__Submit_Listing;
use WPBDP_Admin_Listings;
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
	 * @since 6.4.28
	 */
	public function tearDown(): void {
		$this->clear_request();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.30
	 */
	public function testSubscriberOwnerCanDeleteOwnListingImage() {
		$subscriber_id = $this->get_or_create_user( 'subscriber', 'media-subscriber@example.com' );
		$listing_id    = $this->create_listing( $subscriber_id );
		$image_id      = $this->create_image_attachment( $subscriber_id, $listing_id );

		wp_set_current_user( $subscriber_id );
		$response = $this->dispatch_image_delete( $listing_id, $image_id );

		$this->assertTrue( $response['success'] );
		$this->assertNull( get_post( $image_id ) );
	}

	/**
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	public function testAdminListingSaveIgnoresImageFromAnotherListing() {
		$admin_id         = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$contributor_id   = $this->get_or_create_user( 'contributor', 'media-contributor@example.com' );
		$other_listing_id = $this->create_listing( $admin_id );
		$listing_id       = $this->create_listing( $contributor_id );
		$image_id         = $this->create_image_attachment( $admin_id, $other_listing_id );

		update_post_meta( $image_id, '_wpbdp_image_caption', 'Original caption' );

		wp_set_current_user( $contributor_id );
		$this->save_admin_listing_images(
			$listing_id,
			array(
				'_thumbnail_id' => $image_id,
				'images_meta'   => array(
					$image_id => array(
						'caption' => 'Changed caption',
						'order'   => 5,
					),
				),
			)
		);

		$this->assertSame( $other_listing_id, (int) wp_get_post_parent_id( $image_id ) );
		$this->assertSame( 'Original caption', get_post_meta( $image_id, '_wpbdp_image_caption', true ) );
		$this->assertNotContains( $image_id, array_map( 'absint', (array) get_post_meta( $listing_id, '_wpbdp[images]', true ) ) );

		wp_delete_post( $listing_id, true );
		$this->assertNotNull( get_post( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminListingSaveDoesNotMoveImageListedInImageMeta() {
		$admin_id         = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$contributor_id   = $this->get_or_create_user( 'contributor', 'media-contributor@example.com' );
		$other_listing_id = $this->create_listing( $admin_id );
		$listing_id       = $this->create_listing( $contributor_id );
		$image_id         = $this->create_image_attachment( $admin_id, $other_listing_id );

		update_post_meta( $listing_id, '_wpbdp[images]', array( $image_id ) );

		wp_set_current_user( $contributor_id );
		$this->save_admin_listing_images( $listing_id, array( '_thumbnail_id' => $image_id ) );

		$this->assertSame( $other_listing_id, (int) wp_get_post_parent_id( $image_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminListingSaveUsesOwnUnattachedImageAsThumbnail() {
		$admin_id   = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$listing_id = $this->create_listing( $admin_id );
		$image_id   = $this->create_image_attachment( $admin_id, 0 );

		wp_set_current_user( $admin_id );
		$this->save_admin_listing_images(
			$listing_id,
			array(
				'_thumbnail_id' => $image_id,
				'images_meta'   => array(
					$image_id => array(
						'caption' => 'New caption',
						'order'   => 1,
					),
				),
			)
		);

		$this->assertSame( $listing_id, (int) wp_get_post_parent_id( $image_id ) );
		$this->assertSame( $image_id, (int) get_post_meta( $listing_id, '_thumbnail_id', true ) );
		$this->assertSame( 'New caption', get_post_meta( $image_id, '_wpbdp_image_caption', true ) );
		$this->assertSame( array( $image_id ), get_post_meta( $listing_id, '_wpbdp[images]', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testListingFormImagesStepIgnoresImageFromAnotherListing() {
		$admin_id         = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$author_id        = $this->get_or_create_user( 'author', 'media-author@example.com' );
		$other_listing_id = $this->create_listing( $admin_id );
		$listing_id       = $this->create_listing( $author_id );
		$foreign_id       = $this->create_image_attachment( $admin_id, $other_listing_id );
		$own_id           = $this->create_image_attachment( $author_id, $listing_id );

		update_post_meta( $foreign_id, '_wpbdp_image_caption', 'Original caption' );

		wp_set_current_user( $author_id );
		$this->submit_listing_images_step(
			$listing_id,
			array(
				'_thumbnail_id' => $foreign_id,
				'images_meta'   => array(
					$foreign_id => array( 'caption' => 'Changed caption' ),
					$own_id     => array( 'caption' => 'Own caption' ),
				),
			)
		);

		$this->assertNotSame( $foreign_id, (int) get_post_meta( $listing_id, '_thumbnail_id', true ) );
		$this->assertSame( 'Original caption', get_post_meta( $foreign_id, '_wpbdp_image_caption', true ) );
		$this->assertSame( 'Own caption', get_post_meta( $own_id, '_wpbdp_image_caption', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testListingFormImagesStepSkipsSavedForeignThumbnail() {
		$admin_id         = $this->get_or_create_user( 'administrator', 'media-admin@example.com' );
		$author_id        = $this->get_or_create_user( 'author', 'media-author@example.com' );
		$other_listing_id = $this->create_listing( $admin_id );
		$listing_id       = $this->create_listing( $author_id );
		$foreign_id       = $this->create_image_attachment( $admin_id, $other_listing_id );

		$this->create_image_attachment( $author_id, $listing_id );

		update_post_meta( $foreign_id, '_wpbdp_image_caption', 'Original caption' );
		update_post_meta( $listing_id, '_thumbnail_id', $foreign_id );

		wp_set_current_user( $author_id );
		$this->submit_listing_images_step(
			$listing_id,
			array(
				'images_meta' => array(
					$foreign_id => array( 'caption' => 'Changed caption' ),
				),
			)
		);

		$this->assertSame( 'Original caption', get_post_meta( $foreign_id, '_wpbdp_image_caption', true ) );
	}

	/**
	 * @since x.x
	 */
	public function testListingFormImagesStepSetsOwnImageAsThumbnail() {
		$author_id  = $this->get_or_create_user( 'author', 'media-author@example.com' );
		$listing_id = $this->create_listing( $author_id );
		$first_id   = $this->create_image_attachment( $author_id, $listing_id );
		$second_id  = $this->create_image_attachment( $author_id, $listing_id );

		wp_set_current_user( $author_id );
		$this->submit_listing_images_step(
			$listing_id,
			array(
				'_thumbnail_id' => $second_id,
				'images_meta'   => array(
					$first_id  => array( 'caption' => 'First caption' ),
					$second_id => array( 'caption' => 'Second caption' ),
				),
			)
		);

		$this->assertSame( $second_id, (int) get_post_meta( $listing_id, '_thumbnail_id', true ) );
		$this->assertSame( 'First caption', get_post_meta( $first_id, '_wpbdp_image_caption', true ) );
		$this->assertSame( 'Second caption', get_post_meta( $second_id, '_wpbdp_image_caption', true ) );
	}

	/**
	 * @since 6.4.28
	 */
	public function testNormalizeImageIdsAcceptsCommaSeparatedValues() {
		$this->assertSame( array( 12, 34 ), WPBDP_Listing_Image::normalize_image_ids( '12,34' ) );
		$this->assertSame( array( 12 ), WPBDP_Listing_Image::normalize_image_ids( array( '12', '0', 12 ) ) );
	}

	/**
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * Run the wp-admin listing field save with the given image data.
	 *
	 * @since x.x
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $post_data  Image fields to post.
	 *
	 * @return void
	 */
	private function save_admin_listing_images( $listing_id, $post_data ) {
		$this->clear_request();
		$_POST = array_merge(
			array(
				'wpbdp-admin-listing-fields-nonce' => wp_create_nonce( 'save listing fields' ),
				'listingfields'                    => array( 0 => '' ),
			),
			$post_data
		);

		set_current_screen( 'edit' );
		$admin_listings = new WPBDP_Admin_Listings();
		$admin_listings->maybe_save_fields( $listing_id );
		set_current_screen( 'front' );
	}

	/**
	 * Run the images step of the listing form with the given image data.
	 *
	 * @since x.x
	 *
	 * @param int   $listing_id Listing ID.
	 * @param array $post_data  Image fields to post.
	 *
	 * @return void
	 */
	private function submit_listing_images_step( $listing_id, $post_data ) {
		require_once WPBDP_PATH . 'includes/controllers/pages/class-submit-listing.php';

		$plan = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Listing images plan',
				'amount'               => 0,
				'days'                 => 365,
				'images'               => 5,
				'enabled'              => 1,
				'supported_categories' => 'all',
			)
		);
		$plan->save();

		$listing = wpbdp_get_listing( $listing_id );
		$listing->set_fee_plan( $plan );
		wpbdp_set_option( 'allow-images', true );

		$this->clear_request();
		$_POST = $post_data;

		$view  = new \ReflectionClass( WPBDP__Views__Submit_Listing::class );
		$form  = $view->newInstanceWithoutConstructor();
		$props = array(
			'listing'         => $listing,
			'sections'        => array( 'listing_images' => array() ),
			'sections_keys'   => array( 'listing_images' ),
			'current_section' => 'listing_images',
		);

		foreach ( $props as $name => $value ) {
			$prop = $view->getProperty( $name );
			$prop->setAccessible( true );
			$prop->setValue( $form, $value );
		}

		$method = $view->getMethod( 'listing_images' );
		$method->setAccessible( true );
		$method->invoke( $form );
	}

	/**
	 * @since 6.4.28
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
	 * @since 6.4.28
	 *
	 * @return callable
	 */
	public function get_ajax_die_handler() {
		return array( $this, 'handle_ajax_die' );
	}

	/**
	 * Convert an AJAX wp_die into an exception so the suite can continue.
	 *
	 * @since 6.4.28
	 *
	 * @param mixed $message Die message.
	 *
	 * @return void
	 */
	public function handle_ajax_die( $message ) {
		throw new \WPDieException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * @since 6.4.28
	 */
	private function clear_request() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
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
	 * @since 6.4.28
	 *
	 * @return string
	 */
	private function get_minimal_jpeg() {
		return base64_decode(
			'/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAAEAAQMBIgACEQEDEQH/xAAUAAEAAAAAAAAAAAAAAAAAAAAK/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEAMQAAABkw//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/AH//2Q=='
		);
	}
}
