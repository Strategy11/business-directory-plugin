<?php
/**
 * Tests for listing image uploads.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Listing;
use WPBDP_Utils;
use WPBDP__Fee_Plan;

/**
 * Tests which file is stored on upload and the plan image limit.
 */
class ListingImageUploadTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Temporary files created by the test.
	 *
	 * @var string[]
	 */
	private $tmp_files = array();

	/**
	 * Name of each file WordPress was asked to store.
	 *
	 * @var string[]
	 */
	private $stored_names = array();

	/**
	 * @since x.x
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'record_stored_file' ) );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'record_stored_file' ) );

		foreach ( $this->tmp_files as $tmp_file ) {
			if ( file_exists( $tmp_file ) ) {
				unlink( $tmp_file );
			}
		}

		$_FILES   = array();
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testMediaUploadStoresTheFileItValidated() {
		$image  = $this->make_file( 'listing.png', 'image/png', $this->get_png() );
		$_FILES = array(
			'other'  => $this->make_file( 'other.pdf', 'application/pdf', "%PDF-1.4\n" ),
			'images' => array_map(
				function ( $value ) {
					return array( $value );
				},
				$image
			),
		);

		$files = wpbdp_flatten_files_array( $_FILES['images'] );
		WPBDP_Utils::media_upload( reset( $files ), true, true, array( 'image' => true ) );

		$this->assertSame( array( 'listing.png' ), $this->stored_names );
		$this->assertSame( array( 'other', 'images' ), array_keys( $_FILES ) );
	}

	/**
	 * @since x.x
	 */
	public function testUploadWithFreeSlotIsStored() {
		$listing = $this->create_listing_with_plan( 1 );

		$this->dispatch_upload( $listing->get_id(), 0 );

		$this->assertSame( array( 'listing.png' ), $this->stored_names );
	}

	/**
	 * @since x.x
	 */
	public function testImageLimitUsesStoredImages() {
		$listing = $this->create_listing_with_plan( 1 );
		$this->create_image_attachment( $listing->get_id() );
		$this->assertCount( 1, $listing->get_images( 'ids' ) );

		$response = $this->dispatch_upload( $listing->get_id(), 0 );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Can not upload any more images', $response['error'] );
		$this->assertSame( array(), $this->stored_names );
	}

	/**
	 * @since x.x
	 */
	public function testImageLimitCountsImagesPickedInTheForm() {
		$listing = $this->create_listing_with_plan( 2 );

		$response = $this->dispatch_upload( $listing->get_id(), 2 );

		$this->assertFalse( $response['success'] );
		$this->assertSame( array(), $this->stored_names );
	}

	/**
	 * Record the file WordPress is about to store, then stop the upload.
	 * Test files are not real uploads, so WordPress would refuse them later anyway.
	 *
	 * @since x.x
	 *
	 * @param array $file File data.
	 *
	 * @return array
	 */
	public function record_stored_file( $file ) {
		$this->stored_names[] = $file['name'];
		$file['error']        = 'Stopped by test.';
		return $file;
	}

	/**
	 * @since x.x
	 *
	 * @param int $listing_id   Listing ID.
	 * @param int $images_count Image count sent by the form.
	 *
	 * @return array
	 */
	private function dispatch_upload( $listing_id, $images_count ) {
		global $wpbdp;

		$_FILES = array(
			'images' => $this->make_file( 'listing.png', 'image/png', $this->get_png() ),
		);
		$_POST  = array(
			'listing_id'   => $listing_id,
			'images_count' => $images_count,
			'_wpnonce'     => wp_create_nonce( 'listing-' . $listing_id . '-image-upload' ),
		);
		$_REQUEST = $_POST;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_ajax_die_handler' ) );
		ob_start();

		try {
			$wpbdp->ajax_listing_submit_image_upload();
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		$output = ob_get_clean();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_ajax_die_handler' ) );

		$response = json_decode( $output, true );
		$this->assertIsArray( $response, $output );

		return $response;
	}

	/**
	 * @since x.x
	 *
	 * @return callable
	 */
	public function get_ajax_die_handler() {
		return array( $this, 'handle_ajax_die' );
	}

	/**
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
	 * Create a listing owned by an administrator, on a plan with the given image limit.
	 *
	 * @since x.x
	 *
	 * @param int $image_limit Images allowed by the plan.
	 *
	 * @return WPBDP_Listing
	 */
	private function create_listing_with_plan( $image_limit ) {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$plan = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Image limit test',
				'amount'               => 0.0,
				'days'                 => 0,
				'images'               => $image_limit,
				'supported_categories' => 'all',
				'pricing_model'        => 'flat',
				'enabled'              => true,
			)
		);
		$this->assertTrue( true === $plan->save() || is_int( $plan->id ) );

		$listing_id = wp_insert_post(
			array(
				'post_author' => $admin_id,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing image upload test',
			)
		);
		$listing    = WPBDP_Listing::get( $listing_id );
		$listing->set_fee_plan( $plan );

		return $listing;
	}

	/**
	 * @since x.x
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return int Attachment ID.
	 */
	private function create_image_attachment( $listing_id ) {
		$upload = wp_upload_bits( uniqid( 'listing-image-', true ) . '.png', null, $this->get_png() );
		$this->assertEmpty( $upload['error'] );

		return wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_status'    => 'inherit',
				'post_title'     => 'Listing image',
			),
			$upload['file'],
			$listing_id
		);
	}

	/**
	 * @since x.x
	 *
	 * @param string $name     Client file name.
	 * @param string $type     Client mime type.
	 * @param string $contents File contents.
	 *
	 * @return array A file array in the `$_FILES` format.
	 */
	private function make_file( $name, $type, $contents ) {
		$tmp_file = wp_tempnam( $name );
		file_put_contents( $tmp_file, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->tmp_files[] = $tmp_file;

		return array(
			'name'     => $name,
			'type'     => $type,
			'tmp_name' => $tmp_file,
			'error'    => 0,
			'size'     => strlen( $contents ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @return string A 1x1 PNG.
	 */
	private function get_png() {
		return base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
	}
}
