<?php
/**
 * Plan image limit tests for the listing Media Library picker.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Listing;
use WPBDP__Fee_Plan;

/**
 * Tests for the Media Library picker and the plan image limit.
 */
class ListingMediaImageLimitTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testPickerRefusesImagesWhenThePlanHasNoFreeSlots() {
		$admin_id   = $this->login_as_admin();
		$listing_id = $this->create_listing_with_plan( 1 );
		$current    = $this->create_image( $admin_id, $listing_id );
		$picked     = $this->create_image( $admin_id, 0 );

		$response = $this->dispatch_media_image( $listing_id, $picked );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 0, (int) wp_get_post_parent_id( $picked ) );
		$this->assertSame( array( $current ), WPBDP_Listing::get( $listing_id )->get_images( 'ids' ) );
	}

	/**
	 * @since x.x
	 */
	public function testPickerRefusesMoreImagesThanTheFreeSlots() {
		$admin_id   = $this->login_as_admin();
		$listing_id = $this->create_listing_with_plan( 2 );
		$this->create_image( $admin_id, $listing_id );
		$first  = $this->create_image( $admin_id, 0 );
		$second = $this->create_image( $admin_id, 0 );

		$response = $this->dispatch_media_image( $listing_id, array( $first, $second ) );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'only have 1 slot available', $response['data']['errors'] );
		$this->assertSame( 0, (int) wp_get_post_parent_id( $first ) );
		$this->assertSame( 0, (int) wp_get_post_parent_id( $second ) );
	}

	/**
	 * @since x.x
	 */
	public function testPickerAttachesImageWhenThePlanHasAFreeSlot() {
		$admin_id   = $this->login_as_admin();
		$listing_id = $this->create_listing_with_plan( 2 );
		$this->create_image( $admin_id, $listing_id );
		$picked = $this->create_image( $admin_id, 0 );

		$response = $this->dispatch_media_image( $listing_id, $picked );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $listing_id, (int) wp_get_post_parent_id( $picked ) );
	}

	/**
	 * @since x.x
	 */
	public function testPickingAnImageTheListingAlreadyHasDoesNotUseASlot() {
		$admin_id   = $this->login_as_admin();
		$listing_id = $this->create_listing_with_plan( 1 );
		$current    = $this->create_image( $admin_id, $listing_id );

		$response = $this->dispatch_media_image( $listing_id, $current );

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( $current ), WPBDP_Listing::get( $listing_id )->get_images( 'ids' ) );
	}

	/**
	 * @since x.x
	 */
	public function testPickingAnImageTheListingAlreadyHasWorksWhenOverTheLimit() {
		$admin_id   = $this->login_as_admin();
		$listing_id = $this->create_listing_with_plan( 1 );
		$current    = $this->create_image( $admin_id, $listing_id );
		$this->create_image( $admin_id, $listing_id );

		$response = $this->dispatch_media_image( $listing_id, $current );

		$this->assertTrue( $response['success'] );
	}

	/**
	 * @since x.x
	 *
	 * @return int
	 */
	private function login_as_admin() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		return $admin_id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $images Image slots in the plan.
	 *
	 * @return int
	 */
	private function create_listing_with_plan( $images ) {
		$listing_id = wp_insert_post(
			array(
				'post_author' => get_current_user_id(),
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing media limit test',
			)
		);
		$this->assertTrue( is_int( $listing_id ) );

		$plan = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Media limit plan',
				'amount'               => 0,
				'days'                 => 365,
				'images'               => $images,
				'enabled'              => 1,
				'supported_categories' => 'all',
			)
		);
		$plan->save();
		WPBDP_Listing::get( $listing_id )->set_fee_plan( $plan );

		return $listing_id;
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

		$_REQUEST = array(
			'listing_id' => $listing_id,
			'image_ids'  => $image_ids,
			'_wpnonce'   => wp_create_nonce( 'listing-' . $listing_id . '-image-from-media' ),
		);
		$_GET     = $_REQUEST;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		ob_start();

		try {
			$wpbdp->ajax_listing_media_image();
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		$output = ob_get_clean();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );

		$response = json_decode( $output, true );
		$this->assertIsArray( $response );

		return $response;
	}

	/**
	 * @since x.x
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return array( $this, 'throw_die_exception' );
	}

	/**
	 * @since x.x
	 *
	 * @param mixed $message Die message.
	 *
	 * @return void
	 */
	public function throw_die_exception( $message ) {
		throw new \WPDieException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Attachment author.
	 * @param int $parent_id Parent post ID.
	 *
	 * @return int
	 */
	private function create_image( $author_id, $parent_id ) {
		$upload = wp_upload_bits( uniqid( 'listing-media-limit-', true ) . '.jpg', null, $this->get_minimal_jpeg() );
		$this->assertIsArray( $upload );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_author'    => $author_id,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Listing media limit test image',
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$parent_id
		);
		$this->assertTrue( is_int( $attachment_id ) );

		wp_update_attachment_metadata(
			$attachment_id,
			array(
				'width'  => 1,
				'height' => 1,
				'file'   => _wp_relative_upload_path( $upload['file'] ),
			)
		);

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
