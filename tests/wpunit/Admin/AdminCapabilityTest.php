<?php
/**
 * Capability tests for admin-only AJAX handlers and screens.
 */

namespace Admin;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Admin;
use WPBDP_CSVImportAdmin;
use WPBDP_Payment;
use WPBDP__Admin__Fees;
use WPBDP__Admin__Listing_Owner;
use WPBDP__Admin__Payments;

/**
 * Admin capability tests.
 */
class AdminCapabilityTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once WPBDP_INC . 'admin/controllers/class-admin-controller.php';
		require_once WPBDP_INC . 'admin/controllers/class-admin-payments.php';
		require_once WPBDP_INC . 'admin/controllers/class-admin-fees.php';
		require_once WPBDP_INC . 'admin/csv-import.php';
		require_once WPBDP_INC . 'admin/class-listing-owner.php';
	}

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
	 *
	 * @return array
	 */
	public function notAdminRolesProvider() {
		return array(
			'contributor' => array( 'contributor' ),
			'author'      => array( 'author' ),
			'editor'      => array( 'editor' ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotAddPaymentNote( $role ) {
		$payment_id = $this->create_payment();

		wp_set_current_user( $this->create_user( $role ) );
		$this->run_add_note( $payment_id );

		$this->assertCount( 0, $this->get_payment_notes( $payment_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanAddPaymentNote() {
		$payment_id = $this->create_payment();

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_add_note( $payment_id );

		$this->assertCount( 1, $this->get_payment_notes( $payment_id ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotDeletePaymentNote( $role ) {
		$note = $this->create_payment_note( 123 );

		wp_set_current_user( $this->create_user( $role ) );
		$this->run_delete_note( 123, $note->id );

		$this->assertNotEmpty( wpbdp_get_log( $note->id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanDeletePaymentNote() {
		$note = $this->create_payment_note( 123 );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_delete_note( 123, $note->id );

		$this->assertEmpty( wpbdp_get_log( $note->id ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotUpdatePlanListings( $role ) {
		$plan = $this->set_plan_listings_images( 99 );

		wp_set_current_user( $this->create_user( $role ) );
		$this->run_update_listing_plan( $plan->id );

		$this->assertSame( 0, $this->count_plan_listings_with_images( $plan->id, (int) $plan->images ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanUpdatePlanListings() {
		$plan = $this->set_plan_listings_images( 99 );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_update_listing_plan( $plan->id );

		$this->assertGreaterThan( 0, $this->count_plan_listings_with_images( $plan->id, (int) $plan->images ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotSearchUsersForCsvImport( $role ) {
		$this->create_user( 'subscriber', 'csv-lookup-target@example.com' );

		wp_set_current_user( $this->create_user( $role ) );
		$output = $this->run_csv_autocomplete( 'csv-lookup-target' );

		$this->assertStringNotContainsString( 'csv-lookup-target', $output );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanSearchUsersForCsvImport() {
		$this->create_user( 'subscriber', 'csv-lookup-target@example.com' );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$output = $this->run_csv_autocomplete( 'csv-lookup-target' );

		$this->assertStringContainsString( 'csv-lookup-target', $output );
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function listingOwnerRolesProvider() {
		return array(
			'contributor'   => array( 'contributor', false ),
			'author'        => array( 'author', false ),
			'editor'        => array( 'editor', true ),
			'administrator' => array( 'administrator', true ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider listingOwnerRolesProvider
	 *
	 * @param string $role       User role.
	 * @param bool   $can_change Whether the role can pick the listing owner.
	 */
	public function testListingOwnerSelectorFollowsEditOthersPosts( $role, $can_change ) {
		$this->create_user( 'subscriber', 'owner-lookup-target@example.com' );

		$user_id = $this->create_user( $role );
		wp_set_current_user( $user_id );
		$listing_id = $this->create_listing( $user_id );

		$this->assertSame( $can_change, WPBDP__Admin__Listing_Owner::current_user_can_change_owner() );

		$response = $this->run_owner_autocomplete( $listing_id, 'owner-lookup-target' );

		$this->assertSame( $can_change ? 'ok' : 'fail', $response['status'] );
		if ( ! $can_change ) {
			$this->assertArrayNotHasKey( 'items', $response );
		}
	}

	/**
	 * @since x.x
	 */
	public function testDebugPageRequiresManageOptions() {
		global $submenu, $current_screen;
		$original_submenu = $submenu;
		$original_screen  = $current_screen;

		set_current_screen( 'index.php' );
		wp_set_current_user( $this->create_user( 'administrator' ) );

		$admin = new WPBDP_Admin();
		$admin->admin_menu();

		$debug_item = null;
		foreach ( $submenu[ $admin->get_menu_id() ] as $item ) {
			if ( 'wpbdp-debug-info' === $item[2] ) {
				$debug_item = $item;
			}
		}

		$submenu        = $original_submenu;
		$current_screen = $original_screen;

		$this->assertNotNull( $debug_item );
		$this->assertSame( 'manage_options', $debug_item[1] );
	}

	/**
	 * @since x.x
	 *
	 * @param int $payment_id Payment ID.
	 *
	 * @return void
	 */
	private function run_add_note( $payment_id ) {
		$this->set_request(
			array(
				'payment_id' => $payment_id,
				'note'       => 'Capability test note',
				'nonce'      => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		$this->run_ajax(
			function () {
				( new WPBDP__Admin__Payments() )->ajax_add_note();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int $payment_id Payment ID.
	 * @param int $note_id    Log ID.
	 *
	 * @return void
	 */
	private function run_delete_note( $payment_id, $note_id ) {
		$this->set_request(
			array(
				'payment_id' => $payment_id,
				'note'       => $note_id,
				'_wpnonce'   => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		$this->run_ajax(
			function () {
				( new WPBDP__Admin__Payments() )->ajax_delete_note();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int $plan_id Plan ID.
	 *
	 * @return void
	 */
	private function run_update_listing_plan( $plan_id ) {
		$this->set_request(
			array(
				'plan_id' => $plan_id,
				'nonce'   => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		$this->run_ajax(
			function () {
				( new WPBDP__Admin__Fees() )->ajax_update_listing_plan();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param string $term Search term.
	 *
	 * @return string
	 */
	private function run_csv_autocomplete( $term ) {
		$this->set_request(
			array(
				'term'  => $term,
				'nonce' => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		return $this->run_ajax(
			function () {
				( new WPBDP_CSVImportAdmin() )->ajax_autocomplete_user();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int    $listing_id Listing ID.
	 * @param string $term       Search term.
	 *
	 * @return array
	 */
	private function run_owner_autocomplete( $listing_id, $term ) {
		$this->set_request(
			array(
				'listing_id' => $listing_id,
				'term'       => $term,
				'security'   => wp_create_nonce( 'ajax_autocomplete_users_' . $listing_id ),
			)
		);

		$output = $this->run_ajax(
			function () {
				( new WPBDP__Admin__Listing_Owner() )->ajax_autocomplete_users();
			}
		);

		$response = json_decode( $output, true );
		$this->assertIsArray( $response );

		return $response;
	}

	/**
	 * Run an AJAX callback and return its output, turning wp_die() into an exception.
	 *
	 * @since x.x
	 *
	 * @param callable $callback AJAX method.
	 *
	 * @return string
	 */
	private function run_ajax( $callback ) {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		add_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );
		ob_start();

		try {
			$callback();
		} catch ( \WPDieException $e ) {
			echo $e->getMessage(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		remove_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );

		return (string) ob_get_clean();
	}

	/**
	 * @since x.x
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return array( $this, 'handle_die' );
	}

	/**
	 * @since x.x
	 *
	 * @param mixed $message Die message.
	 *
	 * @throws \WPDieException Always.
	 */
	public function handle_die( $message ) {
		throw new \WPDieException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * @since x.x
	 *
	 * @param array $values Request values.
	 *
	 * @return void
	 */
	private function set_request( $values ) {
		$_GET     = $values;
		$_POST    = $values;
		$_REQUEST = $values;
	}

	/**
	 * @since x.x
	 *
	 * @param string $role  User role.
	 * @param string $email Optional email.
	 *
	 * @return int
	 */
	private function create_user( $role, $email = '' ) {
		$email = $email ? $email : $role . '-' . wp_generate_password( 6, false ) . '@example.com';

		return self::factory()->user->create(
			array(
				'role'       => $role,
				'user_email' => $email,
				'user_login' => sanitize_user( str_replace( '@', '-', $email ) ),
			)
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Listing author.
	 *
	 * @return int
	 */
	private function create_listing( $author_id ) {
		return self::factory()->post->create(
			array(
				'post_author' => $author_id,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'draft',
			)
		);
	}

	/**
	 * @since x.x
	 *
	 * @return int
	 */
	private function create_payment() {
		$listing_ids = get_posts(
			array(
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		$this->assertNotEmpty( $listing_ids );

		$payment = new WPBDP_Payment(
			array(
				'listing_id' => $listing_ids[0],
			)
		);
		$payment->save();

		$this->assertNotEmpty( $payment->id );

		return (int) $payment->id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $payment_id Payment ID.
	 *
	 * @return object
	 */
	private function create_payment_note( $payment_id ) {
		$note = wpbdp_insert_log(
			array(
				'log_type'  => 'payment.note',
				'message'   => 'Existing note',
				'object_id' => $payment_id,
			)
		);

		$this->assertNotEmpty( $note );

		return $note;
	}

	/**
	 * @since x.x
	 *
	 * @param int $payment_id Payment ID.
	 *
	 * @return array
	 */
	private function get_payment_notes( $payment_id ) {
		return wpbdp_get_logs(
			array(
				'log_type'  => 'payment.note',
				'object_id' => $payment_id,
			)
		);
	}

	/**
	 * Give the free plan's listings an image limit that differs from the plan's.
	 *
	 * @since x.x
	 *
	 * @param int $images Image limit to store on the listings.
	 *
	 * @return object The plan.
	 */
	private function set_plan_listings_images( $images ) {
		global $wpdb;

		$plan = wpbdp_get_fee_plan( 'free' );
		$this->assertNotEmpty( $plan );

		$wpdb->update( $wpdb->prefix . 'wpbdp_listings', array( 'fee_images' => $images ), array( 'fee_id' => $plan->id ) );
		\WPBDP_Utils::cache_delete_group( 'wpbdp_listings' );

		$this->assertGreaterThan( 0, $this->count_plan_listings_with_images( $plan->id, $images ) );

		return $plan;
	}

	/**
	 * @since x.x
	 *
	 * @param int $plan_id Plan ID.
	 * @param int $images  Image limit.
	 *
	 * @return int
	 */
	private function count_plan_listings_with_images( $plan_id, $images ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_listings WHERE fee_id = %d AND fee_images = %d",
				$plan_id,
				$images
			)
		);
	}
}
