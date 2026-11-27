<?php
/**
 * Response tests for the add-on install request.
 */

namespace Admin;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Addons_Controller;
use ZipArchive;

/**
 * Tests for what the add-on install request replies.
 */
class AddonInstallResponseTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Temporary folder the add-on is installed into.
	 *
	 * @var string
	 */
	private $install_dir = '';

	/**
	 * Output sent during the request.
	 *
	 * @var string
	 */
	private $output = '';

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		if ( ! class_exists( 'WPBDP_Addons_Controller' ) ) {
			require_once WPBDP_INC . 'controllers/class-addons-controller.php';
		}

		$this->install_dir = trailingslashit( get_temp_dir() ) . uniqid( 'wpbdp-addon-install-', true );

		add_filter( 'filesystem_method', array( $this, 'use_direct_filesystem' ) );
		add_filter( 'upgrader_pre_download', array( $this, 'use_local_package' ) );
		add_filter( 'async_update_translation', '__return_false' );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'filesystem_method', array( $this, 'use_direct_filesystem' ) );
		remove_filter( 'upgrader_pre_download', array( $this, 'use_local_package' ) );
		remove_filter( 'upgrader_package_options', array( $this, 'install_outside_plugins_folder' ) );
		remove_filter( 'async_update_translation', '__return_false' );

		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->exists( $this->install_dir ) ) {
			$wp_filesystem->delete( $this->install_dir, true );
		}

		$_POST    = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testInstallRepliesWithTheErrorWhenTheAddonIsNotInstalled() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// The package lands outside the plugins folder, so the installer can't find the new plugin.
		add_filter( 'upgrader_package_options', array( $this, 'install_outside_plugins_folder' ) );

		$response = json_decode( $this->dispatch_request( 'ajax_install_addon', 'https://example.org/sample-addon.zip' ), true );

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Plugin was not installed', $response['message'] );
		$this->assertStringNotContainsString( $this->install_dir, $response['message'] );
	}

	/**
	 * @since x.x
	 */
	public function testActivateRepliesWithAMessageWhenTheAddonCannotBeActivated() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$response = json_decode( $this->dispatch_request( 'ajax_activate_addon', 'wpbdp-missing-addon/wpbdp-missing-addon.php' ), true );

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'] ?? null );
		$this->assertNotEmpty( $response['message'] ?? '' );
	}

	/**
	 * @since x.x
	 *
	 * @param string $handler Controller AJAX method.
	 * @param string $plugin  Posted plugin value.
	 *
	 * @return string
	 */
	private function dispatch_request( $handler, $plugin ) {
		$_POST    = array(
			'nonce'  => wp_create_nonce( 'wpbdp_ajax' ),
			'plugin' => $plugin,
		);
		$_REQUEST = $_POST;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );

		$this->output = '';
		$level        = ob_get_level();
		ob_start( array( $this, 'collect_output' ) );

		$screen = $GLOBALS['current_screen'] ?? null;

		try {
			call_user_func( array( WPBDP_Addons_Controller::class, $handler ) );
		} catch ( \WPDieException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		while ( ob_get_level() > $level ) {
			ob_end_flush();
		}
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );
		$GLOBALS['current_screen'] = $screen;

		return $this->output;
	}

	/**
	 * Keep the request output instead of printing it.
	 *
	 * @since x.x
	 *
	 * @param string $buffer Output.
	 *
	 * @return string
	 */
	public function collect_output( $buffer ) {
		$this->output .= $buffer;

		return '';
	}

	/**
	 * @since x.x
	 *
	 * @return string
	 */
	public function use_direct_filesystem() {
		return 'direct';
	}

	/**
	 * Serve a small plugin ZIP instead of downloading the package.
	 *
	 * @since x.x
	 *
	 * @return string Path to the package.
	 */
	public function use_local_package() {
		$package = wp_tempnam( 'wpbdp-sample-addon.zip' );
		$zip     = new ZipArchive();
		$zip->open( $package, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		$zip->addFromString( 'wpbdp-sample-addon/wpbdp-sample-addon.php', "<?php\n/**\n * Plugin Name: WPBDP Sample Addon\n */\n" );
		$zip->close();

		return $package;
	}

	/**
	 * @since x.x
	 *
	 * @param array $options Upgrader package options.
	 *
	 * @return array
	 */
	public function install_outside_plugins_folder( $options ) {
		$options['destination'] = $this->install_dir;

		return $options;
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
}
