<?php
/**
 * Form nonce tests for the listing submit, renewal and plan toggle requests.
 */

namespace Listing;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Admin__Fees;
use WPBDP__Fee_Plan;
use WPBDP__Views__Renew_Listing;
use WPBDP__Views__Submit_Listing;

/**
 * Tests that posted listing changes need the form nonce for logged-in users.
 */
class ListingFormNonceTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * ID of the main directory page.
	 *
	 * @var int
	 */
	private $main_page_id = 0;

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		$this->main_page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_content' => '[businessdirectory]',
			)
		);
		add_filter( 'wpbdp_get_page_ids', array( $this, 'filter_main_page_ids' ), 10, 2 );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'wpbdp_get_page_ids', array( $this, 'filter_main_page_ids' ), 10 );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testEditPostWithoutNonceIsNotProcessed() {
		$owner_id   = $this->create_user( 'subscriber' );
		$listing_id = $this->create_listing( $owner_id );

		wp_set_current_user( $owner_id );
		$output = $this->dispatch_edit( $listing_id, '' );

		$this->assertStringContainsString( 'session has expired', $output );
		$this->assertSame( 'Original title', get_the_title( $listing_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testEditPostWithNonceIsProcessed() {
		$owner_id   = $this->create_user( 'subscriber' );
		$listing_id = $this->create_listing( $owner_id );

		wp_set_current_user( $owner_id );
		$output = $this->dispatch_edit( $listing_id, wp_create_nonce( 'listing submit' ) );

		$this->assertStringNotContainsString( 'session has expired', $output );
	}

	/**
	 * @since x.x
	 */
	public function testFormLoadWithoutPostedDataNeedsNoNonce() {
		wp_set_current_user( $this->create_user( 'subscriber' ) );

		$_POST    = array(
			'action'  => 'wpbdp_ajax',
			'handler' => 'submit_listing__load_form',
		);
		$_REQUEST = $_POST;

		$output = $this->dispatch_submit_view( false );

		$this->assertStringNotContainsString( 'session has expired', $output );
	}

	/**
	 * @since x.x
	 */
	public function testGuestPostNeedsNoNonce() {
		$_POST    = array( 'save_listing' => '1' );
		$_REQUEST = $_POST;

		$output = $this->dispatch_submit_view( false );

		$this->assertStringNotContainsString( 'session has expired', $output );
	}

	/**
	 * @since x.x
	 */
	public function testCancelRenewalWithoutNonceKeepsListing() {
		$owner_id   = $this->create_user( 'subscriber' );
		$listing_id = $this->create_listing( $owner_id );

		wp_set_current_user( $owner_id );
		$output = $this->dispatch_cancel_renewal( $listing_id, '' );

		$this->assertStringContainsString( 'session has expired', $output );
		$this->assertSame( 'publish', get_post_status( $listing_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testCancelRenewalWithNonceRemovesListing() {
		$owner_id   = $this->create_user( 'subscriber' );
		$listing_id = $this->create_listing( $owner_id );

		wp_set_current_user( $owner_id );
		$this->dispatch_cancel_renewal( $listing_id, wp_create_nonce( 'cancel renewal ' . $listing_id ) );

		$this->assertSame( 'trash', get_post_status( $listing_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testPlanToggleWithoutNonceKeepsPlan() {
		$plan = $this->create_plan();

		wp_set_current_user( $this->create_user( 'administrator' ) );

		try {
			$this->toggle_plan( $plan->id, '' );
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// The request is stopped before the plan changes.
		}

		$this->assertTrue( (bool) wpbdp_get_fee_plan( $plan->id )->enabled );
	}

	/**
	 * @since x.x
	 */
	public function testPlanToggleWithNonceDisablesPlan() {
		$plan = $this->create_plan();

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->toggle_plan( $plan->id, wp_create_nonce( 'toggle-fee' ) );

		$this->assertFalse( (bool) wpbdp_get_fee_plan( $plan->id )->enabled );
	}

	/**
	 * Point BD at the test's main directory page.
	 *
	 * @since x.x
	 *
	 * @param array|null $page_ids Page IDs found.
	 * @param string     $page     Page name.
	 *
	 * @return array|null
	 */
	public function filter_main_page_ids( $page_ids, $page ) {
		return 'main' === $page ? array( $this->main_page_id ) : $page_ids;
	}

	/**
	 * Post the front-end edit form for a listing.
	 *
	 * @since x.x
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $nonce      Nonce to send, or an empty string for none.
	 *
	 * @return string
	 */
	private function dispatch_edit( $listing_id, $nonce ) {
		$_POST = array(
			'listing_id'   => $listing_id,
			'editing'      => '1',
			'save_listing' => '1',
		);
		if ( $nonce ) {
			$_POST['_wpnonce'] = $nonce;
		}
		$_REQUEST = $_POST;

		return $this->dispatch_submit_view( true );
	}

	/**
	 * Run the submit listing view.
	 *
	 * @since x.x
	 *
	 * @param bool $editing Whether the edit view is used.
	 *
	 * @return string
	 */
	private function dispatch_submit_view( $editing ) {
		require_once WPBDP_INC . 'controllers/pages/class-submit-listing.php';

		$view = new WPBDP__Views__Submit_Listing( $editing ? array( 'editing' => true ) : array() );

		return $this->get_output(
			function () use ( $view ) {
				return $view->dispatch();
			}
		);
	}

	/**
	 * Post "Do not renew my listing" for a listing.
	 *
	 * @since x.x
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $nonce      Nonce to send, or an empty string for none.
	 *
	 * @return string
	 */
	private function dispatch_cancel_renewal( $listing_id, $nonce ) {
		require_once WPBDP_INC . 'controllers/pages/class-renew-listing.php';

		wpbdp_set_option( 'listing-renewal', true );

		$_GET  = array( 'renewal_id' => $listing_id );
		$_POST = array( 'cancel-renewal' => '1' );
		if ( $nonce ) {
			$_POST['_wpnonce'] = $nonce;
		}
		$_REQUEST = array_merge( $_GET, $_POST );

		$view = new WPBDP__Views__Renew_Listing();

		return $this->get_output(
			function () use ( $view ) {
				return $view->dispatch();
			}
		);
	}

	/**
	 * Run the plan enable/disable admin action.
	 *
	 * @since x.x
	 *
	 * @param int    $plan_id Plan ID.
	 * @param string $nonce   Nonce to send, or an empty string for none.
	 *
	 * @return void
	 */
	private function toggle_plan( $plan_id, $nonce ) {
		require_once WPBDP_INC . 'admin/controllers/class-admin-controller.php';
		require_once WPBDP_INC . 'admin/controllers/class-admin-fees.php';

		$_GET = array( 'id' => $plan_id );
		if ( $nonce ) {
			$_GET['_wpnonce'] = $nonce;
		}
		$_REQUEST = $_GET;

		global $wpbdp;
		$admin        = $wpbdp->admin ?? null;
		$wpbdp->admin = (object) array( 'messages' => array() );

		// Skip rendering the plans screen after the change; it needs a full admin request.
		$controller = new class() extends WPBDP__Admin__Fees {
			/**
			 * @since x.x
			 *
			 * @param string $view_or_url View or URL.
			 *
			 * @return string
			 */
			public function _redirect( $view_or_url ) {
				return '';
			}
		};

		try {
			$controller->toggle_fee();
		} finally {
			$wpbdp->admin = $admin;
		}
	}

	/**
	 * Return printed and returned output of a callback.
	 *
	 * @since x.x
	 *
	 * @param callable $callback Callback to run.
	 *
	 * @return string
	 */
	private function get_output( $callback ) {
		add_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
		ob_start();
		try {
			$returned = $callback();
		} catch ( \RuntimeException $e ) {
			$returned = $e->getMessage();
		} finally {
			$printed = ob_get_clean();
			remove_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
		}

		return $printed . ( is_string( $returned ?? null ) ? $returned : '' );
	}

	/**
	 * Stop a redirect before headers are sent, so the request ends inside the test.
	 *
	 * @since x.x
	 *
	 * @param string $location Redirect URL.
	 *
	 * @throws \RuntimeException Always.
	 */
	public function stop_redirect( $location ) {
		throw new \RuntimeException( 'Redirected to ' . esc_url_raw( $location ) );
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
				'post_title'  => 'Original title',
			)
		);

		wpbdp_get_listing( $listing_id )->set_fee_plan( wpbdp_get_fee_plan( 'free' ) );

		return $listing_id;
	}

	/**
	 * Create an enabled plan next to the free one, so it can be disabled.
	 *
	 * @since x.x
	 *
	 * @return WPBDP__Fee_Plan
	 */
	private function create_plan() {
		$plan = new WPBDP__Fee_Plan(
			array(
				'label'                => 'Toggle Plan',
				'amount'               => 10,
				'days'                 => 30,
				'enabled'              => 1,
				'supported_categories' => 'all',
			)
		);
		$plan->save();

		return $plan;
	}
}
