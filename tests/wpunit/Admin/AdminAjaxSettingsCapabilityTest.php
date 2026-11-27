<?php
/**
 * Capability tests for admin AJAX handlers tied to admin-only screens.
 */

namespace Admin;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Admin;
use WPBDP_Admin_Listings;
use WPBDP_Onboarding_Wizard;

/**
 * Payment history, plan order, onboarding consent and notice dismissal capability tests.
 */
class AdminAjaxSettingsCapabilityTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once WPBDP_INC . 'admin/class-admin.php';
		require_once WPBDP_INC . 'admin/controllers/class-admin-listings.php';
		require_once WPBDP_INC . 'admin/controllers/class-onboarding-wizard.php';
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		delete_transient( 'wpbdp_server_requirements_warning_dismissed' );
		remove_filter( 'pre_http_request', array( $this, 'block_http' ) );
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
	public function testNonAdminCannotClearOwnListingPaymentHistory( $role ) {
		$user_id    = $this->create_user( $role );
		$listing_id = $this->create_listing_with_payment( $user_id );

		wp_set_current_user( $user_id );
		$this->run_clear_payment_history( $listing_id );

		$this->assertSame( 1, $this->count_payments( $listing_id ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanClearPaymentHistory() {
		$listing_id = $this->create_listing_with_payment( $this->create_user( 'contributor' ) );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$output = $this->run_clear_payment_history( $listing_id );

		$this->assertSame( 0, $this->count_payments( $listing_id ) );
		$this->assertStringContainsString( '"success":true', $output );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotChangePlanOrder( $role ) {
		wpbdp_set_option( 'fee-order', $this->plan_order( 'label' ) );

		wp_set_current_user( $this->create_user( $role ) );
		$this->run_set_plan_order( $this->plan_order( 'amount' ) );

		$this->assertSame( $this->plan_order( 'label' ), wpbdp_get_option( 'fee-order' ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanChangePlanOrder() {
		wpbdp_set_option( 'fee-order', $this->plan_order( 'label' ) );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_set_plan_order( $this->plan_order( 'amount' ) );

		$this->assertSame( $this->plan_order( 'amount' ), wpbdp_get_option( 'fee-order' ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotAcceptOnboardingTracking( $role ) {
		wpbdp_set_option( 'tracking-on', false );

		wp_set_current_user( $this->create_user( $role ) );
		$this->run_consent_tracking();

		$this->assertEmpty( wpbdp_get_option( 'tracking-on' ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanAcceptOnboardingTracking() {
		wpbdp_set_option( 'tracking-on', false );

		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_consent_tracking();

		$this->assertNotEmpty( wpbdp_get_option( 'tracking-on' ) );
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider notAdminRolesProvider
	 *
	 * @param string $role User role.
	 */
	public function testNonAdminCannotDismissServerRequirementsNotice( $role ) {
		wp_set_current_user( $this->create_user( $role ) );
		$this->run_dismiss_notice( 'server_requirements' );

		$this->assertFalse( get_transient( 'wpbdp_server_requirements_warning_dismissed' ) );
	}

	/**
	 * @since x.x
	 */
	public function testAdminCanDismissServerRequirementsNotice() {
		wp_set_current_user( $this->create_user( 'administrator' ) );
		$this->run_dismiss_notice( 'server_requirements' );

		$this->assertNotEmpty( get_transient( 'wpbdp_server_requirements_warning_dismissed' ) );
	}

	/**
	 * @since x.x
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return string
	 */
	private function run_clear_payment_history( $listing_id ) {
		$this->set_request(
			array(
				'listing_id' => $listing_id,
				'nonce'      => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		$listings = ( new \ReflectionClass( WPBDP_Admin_Listings::class ) )->newInstanceWithoutConstructor();

		return $this->run_ajax(
			function () use ( $listings ) {
				$listings->ajax_clear_payment_history();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @param array $order Plan order option.
	 *
	 * @return void
	 */
	private function run_set_plan_order( $order ) {
		$this->set_request(
			array(
				'fee_order' => $order,
				'_wpnonce'  => wp_create_nonce( 'change fees order' ),
			)
		);

		$admin = $this->get_admin();
		$this->run_ajax(
			function () use ( $admin ) {
				$admin->ajax_fees_set_order();
			}
		);
	}

	/**
	 * @since x.x
	 *
	 * @return void
	 */
	private function run_consent_tracking() {
		add_filter( 'pre_http_request', array( $this, 'block_http' ) );

		$this->set_request(
			array(
				'nonce' => wp_create_nonce( 'wpbdp_ajax' ),
			)
		);

		$wizard = new WPBDP_Onboarding_Wizard();
		$this->run_ajax(
			function () use ( $wizard ) {
				$wizard->ajax_consent_tracking();
			}
		);
	}

	/**
	 * Dismiss a notice the way the notice's dismiss button does.
	 *
	 * @since x.x
	 *
	 * @param string $notice_id Dismissible notice ID.
	 *
	 * @return void
	 */
	private function run_dismiss_notice( $notice_id ) {
		$admin = $this->get_admin();
		$hook  = 'wpbdp_admin_ajax_dismiss_notification_' . $notice_id;
		add_action( $hook, array( $admin, 'ajax_dismiss_notification_server_requirements' ) );

		$this->set_request(
			array(
				'id'    => $notice_id,
				'nonce' => wp_create_nonce( 'dismiss notice ' . $notice_id ),
			)
		);

		$this->run_ajax(
			function () use ( $admin ) {
				$admin->ajax_dismiss_notification();
			}
		);

		remove_action( $hook, array( $admin, 'ajax_dismiss_notification_server_requirements' ) );
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
	 * Keep the onboarding consent from calling the remote feedback endpoint.
	 *
	 * @since x.x
	 *
	 * @return array
	 */
	public function block_http() {
		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * @since x.x
	 *
	 * @return WPBDP_Admin
	 */
	private function get_admin() {
		return ( new \ReflectionClass( WPBDP_Admin::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * @since x.x
	 *
	 * @param string $method Order method.
	 *
	 * @return array
	 */
	private function plan_order( $method ) {
		return array(
			'method' => $method,
			'order'  => 'asc',
		);
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
	 * @param string $role User role.
	 *
	 * @return int
	 */
	private function create_user( $role ) {
		return self::factory()->user->create(
			array(
				'role' => $role,
			)
		);
	}

	/**
	 * @since x.x
	 *
	 * @param int $author_id Listing author.
	 *
	 * @return int Listing ID.
	 */
	private function create_listing_with_payment( $author_id ) {
		$listing_id = self::factory()->post->create(
			array(
				'post_author' => $author_id,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'draft',
			)
		);

		global $wpdb;

		// Insert the row directly: saving a WPBDP_Payment runs the listing checkout flow.
		$wpdb->insert(
			$wpdb->prefix . 'wpbdp_payments',
			array(
				'listing_id'    => $listing_id,
				'payment_type'  => 'initial',
				'currency_code' => 'USD',
				'amount'        => 5,
				'status'        => 'completed',
				'created_at'    => current_time( 'mysql' ),
			)
		);

		$this->assertSame( 1, $this->count_payments( $listing_id ) );

		return $listing_id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $listing_id Listing ID.
	 *
	 * @return int
	 */
	private function count_payments( $listing_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d",
				$listing_id
			)
		);
	}
}
