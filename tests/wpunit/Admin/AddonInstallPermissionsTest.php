<?php
/**
 * Capability tests for the add-on install and activate requests.
 */

namespace Admin;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Addons_Controller;

/**
 * Tests for the add-on install and activate requests.
 */
class AddonInstallPermissionsTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * URLs requested during the current test.
	 *
	 * @var string[]
	 */
	private $requested_urls = array();

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		if ( ! class_exists( 'WPBDP_Addons_Controller' ) ) {
			require_once WPBDP_INC . 'controllers/class-addons-controller.php';
		}

		$this->requested_urls = array();
		add_filter( 'pre_http_request', array( $this, 'block_http_request' ), 10, 3 );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'block_http_request' ), 10 );
		remove_filter( 'map_meta_cap', array( $this, 'deny_install_plugins' ), 10 );
		$_POST    = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testInstallIsRefusedWhenUserCannotInstallPlugins() {
		$this->log_in_as_admin();
		add_filter( 'map_meta_cap', array( $this, 'deny_install_plugins' ), 10, 2 );

		$this->assertTrue( current_user_can( 'activate_plugins' ) );
		$this->assertFalse( current_user_can( 'install_plugins' ) );

		$output = $this->dispatch( 'ajax_install_addon', 'https://example.org/sample-addon.zip' );

		$this->assertSame( 'true', $output );
		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * @since x.x
	 */
	public function testInstallDownloadsPackageWhenUserCanInstallPlugins() {
		$this->log_in_as_admin();

		$this->assertTrue( current_user_can( 'install_plugins' ) );

		$output = $this->dispatch( 'ajax_install_addon', 'https://example.org/sample-addon.zip' );

		$this->assertNotSame( 'true', $output );
		$this->assertContains( 'https://example.org/sample-addon.zip', $this->requested_urls );
	}

	/**
	 * @since x.x
	 */
	public function testActivateStillWorksWhenUserCannotInstallPlugins() {
		$this->log_in_as_admin();
		add_filter( 'map_meta_cap', array( $this, 'deny_install_plugins' ), 10, 2 );

		$output = $this->dispatch( 'ajax_activate_addon', '' );

		$this->assertStringContainsString( 'activated', $output );
	}

	/**
	 * @since x.x
	 */
	public function testInstallIsRefusedForEditor() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$output = $this->dispatch( 'ajax_install_addon', 'https://example.org/sample-addon.zip' );

		$this->assertSame( 'true', $output );
		$this->assertSame( array(), $this->requested_urls );
	}

	/**
	 * Map install_plugins to do_not_allow, as core does with DISALLOW_FILE_MODS.
	 *
	 * @since x.x
	 *
	 * @param string[] $caps Primitive capabilities.
	 * @param string   $cap  Requested capability.
	 *
	 * @return string[]
	 */
	public function deny_install_plugins( $caps, $cap ) {
		if ( 'install_plugins' === $cap ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * Record outgoing requests and stop them from reaching the network.
	 *
	 * @since x.x
	 *
	 * @param array|false|\WP_Error $response Short-circuit value.
	 * @param array                 $args     Request arguments.
	 * @param string                $url      Request URL.
	 *
	 * @return \WP_Error
	 */
	public function block_http_request( $response, $args, $url ) {
		$this->requested_urls[] = $url;

		return new \WP_Error( 'http_request_blocked', 'Blocked in tests.' );
	}

	/**
	 * Return a wp_die handler that throws instead of exiting.
	 *
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
	 * @return void
	 */
	public function handle_die( $message ) {
		throw new \WPDieException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * @since x.x
	 */
	private function log_in_as_admin() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Call an add-on request handler and return what it printed.
	 *
	 * @since x.x
	 *
	 * @param string $method Handler method name.
	 * @param string $plugin Value of the plugin parameter.
	 *
	 * @return string
	 */
	private function dispatch( $method, $plugin ) {
		$_POST    = array(
			'plugin' => $plugin,
			'nonce'  => wp_create_nonce( 'wpbdp_ajax' ),
		);
		$_REQUEST = $_POST;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		add_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );
		ob_start();

		try {
			WPBDP_Addons_Controller::$method();
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		$output = ob_get_clean();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		remove_filter( 'wp_die_handler', array( $this, 'get_die_handler' ) );

		return trim( $output );
	}
}
