<?php
/**
 * Includes tests for listing query sorting.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Fee_Plan;
use WPBDP_Utils;
use WP_Query;

/**
 * Tests for listing query sorting.
 */
class ListingQuerySortTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var int[]
	 */
	private $created_listing_ids = array();

	/**
	 * @var int[]
	 */
	private $created_plan_ids = array();

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		wpbdp_set_option(
			'fee-order',
			array(
				'method' => 'custom',
				'order'  => 'asc',
			)
		);
		wpbdp_set_option( 'prevent-sticky-on-directory-view', array() );
		WPBDP_Utils::cache_delete_group( 'wpbdp_listings' );
	}

	/**
	 * @since x.x
	 */
	public function tearDown() : void {
		foreach ( $this->created_listing_ids as $listing_id ) {
			wp_delete_post( $listing_id, true );
		}

		foreach ( $this->created_plan_ids as $plan_id ) {
			$plan = wpbdp_get_fee_plan( $plan_id );
			if ( $plan ) {
				$plan->delete();
			}
		}

		WPBDP_Utils::cache_delete_group( 'wpbdp_listings' );

		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testStickyListingsUsePlanOrderTitleSort() {
		$plan = $this->create_fee_plan( 'sticky_plan_order_title', 10 );

		$zebra = $this->create_listing_on_plan( 'Zebra Corp', $plan, '2026-01-01 00:00:00' );
		$alpha = $this->create_listing_on_plan( 'Alpha Inc', $plan, '2026-01-02 00:00:00' );
		$mango = $this->create_listing_on_plan( 'Mango Ltd', $plan, '2026-01-03 00:00:00' );

		$listing_ids = array( $zebra, $alpha, $mango );

		$this->assertSame(
			array( $alpha, $mango, $zebra ),
			$this->query_listing_ids( 'plan-order-title', 'ASC', $listing_ids )
		);

		$this->assertSame(
			array( $zebra, $mango, $alpha ),
			$this->query_listing_ids( 'plan-order-title', 'DESC', $listing_ids )
		);
	}

	/**
	 * @since x.x
	 */
	public function testStickyListingsUsePlanOrderDateSort() {
		$plan = $this->create_fee_plan( 'sticky_plan_order_date', 10 );

		$newest = $this->create_listing_on_plan( 'Newest Listing', $plan, '2026-01-03 00:00:00' );
		$oldest = $this->create_listing_on_plan( 'Oldest Listing', $plan, '2026-01-01 00:00:00' );
		$middle = $this->create_listing_on_plan( 'Middle Listing', $plan, '2026-01-02 00:00:00' );

		$this->assertSame(
			array( $oldest, $middle, $newest ),
			$this->query_listing_ids( 'plan-order-date', 'ASC', array( $newest, $oldest, $middle ) )
		);
	}

	/**
	 * @since x.x
	 *
	 * @param string $tag    Fee plan tag.
	 * @param int    $weight Fee plan weight.
	 *
	 * @return WPBDP__Fee_Plan
	 */
	private function create_fee_plan( $tag, $weight ) {
		$fee = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Sticky Plan ' . $weight,
				'amount'               => 100,
				'days'                 => 365,
				'sticky'               => 1,
				'recurring'            => 0,
				'enabled'              => 1,
				'images'               => 0,
				'pricing_model'        => 'flat',
				'supported_categories' => 'all',
				'tag'                  => $tag,
				'weight'               => $weight,
			)
		);

		$result = $fee->save();
		if ( is_wp_error( $result ) ) {
			$this->fail( 'Fee creation failed: ' . $result->get_error_message() );
		}

		$this->assertTrue( is_int( $fee->id ) );
		$this->created_plan_ids[] = $fee->id;

		return $fee;
	}

	/**
	 * @since x.x
	 *
	 * @param string          $title Post title.
	 * @param WPBDP__Fee_Plan $plan  Fee plan.
	 * @param string          $date  Post date.
	 *
	 * @return int
	 */
	private function create_listing_on_plan( $title, $plan, $date ) {
		$listing_id = wp_insert_post(
			array(
				'post_author'   => 1,
				'post_type'     => WPBDP_POST_TYPE,
				'post_status'   => 'publish',
				'post_title'    => $title,
				'post_date'     => $date,
				'post_date_gmt' => get_gmt_from_date( $date ),
			)
		);

		$this->assertTrue( is_int( $listing_id ) && 0 < $listing_id );
		$this->assertNotFalse( wpbdp_get_listing( $listing_id )->set_fee_plan( $plan ) );
		$this->created_listing_ids[] = $listing_id;

		return $listing_id;
	}

	/**
	 * @since x.x
	 *
	 * @param string $order_by   Order by value.
	 * @param string $order      Order direction.
	 * @param int[]  $listing_ids Listing ids.
	 *
	 * @return int[]
	 */
	private function query_listing_ids( $order_by, $order, $listing_ids ) {
		$query = new WP_Query(
			array(
				'post_type'       => WPBDP_POST_TYPE,
				'post_status'     => 'publish',
				'orderby'         => $order_by,
				'order'           => $order,
				'posts_per_page'  => count( $listing_ids ),
				'wpbdp_shortcode' => true,
			)
		);

		return array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) );
	}
}
