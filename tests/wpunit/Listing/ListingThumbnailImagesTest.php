<?php
/**
 * Tests for the listing image list when a thumbnail is saved.
 */

namespace Listing;

use ReflectionClass;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Listing;
use WPBDP_Listing_Image;
use WPBDP__Views__Submit_Listing;

/**
 * Listing images and saved thumbnail tests.
 */
class ListingThumbnailImagesTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testSortedImageIdsKeepEveryImageWhenThumbnailIsNotAListingImage() {
		$fixture = $this->create_listing_with_images();
		update_post_meta( $fixture['listing_id'], '_thumbnail_id', $fixture['other_image'] );

		$images = WPBDP_Listing::get( $fixture['listing_id'] )->get_images( 'ids', true );

		$this->assertSame( array( $fixture['heavy'], $fixture['light'] ), $images );
	}

	/**
	 * @since x.x
	 */
	public function testSortedImageObjectsKeepEveryImageWhenThumbnailIsNotAListingImage() {
		$fixture = $this->create_listing_with_images();
		update_post_meta( $fixture['listing_id'], '_thumbnail_id', $fixture['other_image'] );

		$images = array_values( WPBDP_Listing::get( $fixture['listing_id'] )->get_images( 'all', true ) );

		$this->assertCount( 2, $images );
		$this->assertContainsOnlyInstancesOf( WPBDP_Listing_Image::class, $images );
		$this->assertSame( array( $fixture['heavy'], $fixture['light'] ), wp_list_pluck( $images, 'id' ) );
	}

	/**
	 * @since x.x
	 */
	public function testThumbnailThatIsAListingImageIsListedFirst() {
		$fixture = $this->create_listing_with_images();
		update_post_meta( $fixture['listing_id'], '_thumbnail_id', $fixture['light'] );

		$listing = WPBDP_Listing::get( $fixture['listing_id'] );

		$this->assertSame( array( $fixture['light'], $fixture['heavy'] ), array_values( $listing->get_images( 'ids', true ) ) );
		$this->assertSame( array( $fixture['light'], $fixture['heavy'] ), wp_list_pluck( array_values( $listing->get_images( 'all', true ) ), 'id' ) );
	}

	/**
	 * @since x.x
	 */
	public function testImagesStepSavesEveryCaptionWhenThumbnailIsNotAListingImage() {
		$fixture = $this->create_listing_with_images();
		update_post_meta( $fixture['listing_id'], '_thumbnail_id', $fixture['other_image'] );

		$_POST['images_meta'] = array(
			$fixture['heavy'] => array( 'caption' => 'Heavy caption' ),
			$fixture['light'] => array( 'caption' => 'Light caption' ),
		);
		$_REQUEST             = $_POST;

		$this->run_images_step( $fixture['listing_id'] );

		$this->assertSame( 'Heavy caption', get_post_meta( $fixture['heavy'], '_wpbdp_image_caption', true ) );
		$this->assertSame( 'Light caption', get_post_meta( $fixture['light'], '_wpbdp_image_caption', true ) );
		$this->assertSame( '', get_post_meta( $fixture['other_image'], '_wpbdp_image_caption', true ) );
	}

	/**
	 * Run the images step of the submit form for a listing.
	 *
	 * @since x.x
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return void
	 */
	private function run_images_step( $listing_id ) {
		require_once WPBDP_PATH . 'includes/controllers/pages/class-submit-listing.php';

		$reflection = new ReflectionClass( WPBDP__Views__Submit_Listing::class );
		$view       = $reflection->newInstanceWithoutConstructor();

		$properties = array(
			'listing'         => WPBDP_Listing::get( $listing_id ),
			'sections'        => array( 'listing_images' => array( 'id' => 'listing_images' ) ),
			'sections_keys'   => array( 'listing_images' ),
			'current_section' => 'listing_images',
		);
		foreach ( $properties as $name => $value ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( $view, $value );
		}

		$method = $reflection->getMethod( 'listing_images' );
		$method->setAccessible( true );
		$method->invoke( $view );
	}

	/**
	 * Create a listing with two images (weights 1 and 5) and one image that
	 * isn't attached to it.
	 *
	 * @since x.x
	 *
	 * @return array
	 */
	private function create_listing_with_images() {
		$listing_id = wp_insert_post(
			array(
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing thumbnail images test',
			)
		);
		$this->assertTrue( is_int( $listing_id ) );

		WPBDP_Listing::get( $listing_id )->set_fee_plan( $this->get_plan_with_images() );

		$light = $this->create_image( $listing_id );
		$heavy = $this->create_image( $listing_id );
		update_post_meta( $light, '_wpbdp_image_weight', 1 );
		update_post_meta( $heavy, '_wpbdp_image_weight', 5 );

		return array(
			'listing_id'  => $listing_id,
			'light'       => $light,
			'heavy'       => $heavy,
			'other_image' => $this->create_image( 0 ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @return \WPBDP__Fee_Plan
	 */
	private function get_plan_with_images() {
		$plan = new \WPBDP__Fee_Plan(
			array(
				'label'                => 'Images plan',
				'amount'               => 0,
				'days'                 => 365,
				'images'               => 5,
				'enabled'              => 1,
				'supported_categories' => 'all',
			)
		);
		$plan->save();

		return $plan;
	}

	/**
	 * @since x.x
	 *
	 * @param int $parent_id Parent post ID.
	 *
	 * @return int
	 */
	private function create_image( $parent_id ) {
		$upload = wp_upload_bits( uniqid( 'listing-thumb-', true ) . '.jpg', null, $this->get_minimal_jpeg() );
		$this->assertIsArray( $upload );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'Listing thumbnail test image',
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
