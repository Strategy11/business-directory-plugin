<?php
/**
 * Request argument tests for license and update requests.
 */

namespace Admin;

use ReflectionMethod;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP_Licensing;
use WPBDP_Themes_Admin;

/**
 * Tests that license and update requests keep certificate verification on.
 */
class LicensingRequestArgsTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Requests made during the current test, as url => args.
	 *
	 * @var array
	 */
	private $requests = array();

	/**
	 * Response body returned to intercepted requests.
	 *
	 * @var string
	 */
	private $response_body = '{}';

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		$this->requests      = array();
		$this->response_body = '{}';
		add_filter( 'pre_http_request', array( $this, 'capture_http_request' ), 10, 3 );
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'capture_http_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function testLicenseRequestVerifiesCertificate() {
		$this->call_private( new WPBDP_Licensing(), 'license_request', array( WPBDP_Licensing::STORE_URL . '?edd_action=check_license' ) );

		$this->assert_requests_verify_certificate();
	}

	/**
	 * @since x.x
	 */
	public function testBatchVersionRequestVerifiesCertificate() {
		$this->response_body = '[]';

		$this->call_private(
			new WPBDP_Licensing(),
			'batch_get_plugins',
			array(
				array(
					'edd_action' => 'batch_get_version',
					'licenses'   => array( 'sample-key-one', 'sample-key-two' ),
				),
			)
		);

		$this->assert_requests_verify_certificate();
	}

	/**
	 * @since x.x
	 */
	public function testThemePackageLookupVerifiesCertificate() {
		if ( ! class_exists( 'WPBDP_Themes_Admin' ) ) {
			require_once WPBDP_INC . 'admin/controllers/class-themes-admin.php';
		}

		$themes_api = new class() {
			/**
			 * @since x.x
			 *
			 * @param string $theme_id Theme ID.
			 *
			 * @return object
			 */
			public function get_theme( $theme_id ) {
				return (object) array(
					'id'   => $theme_id,
					'name' => 'Sample Theme',
				);
			}
		};
		$licensing  = new WPBDP_Licensing();
		$admin      = new WPBDP_Themes_Admin( $themes_api, $licensing );

		wpbdp_set_option( 'license-key-theme-sample-theme', 'sample-key' );
		$this->call_private( $admin, 'fetch_theme_package_url', array( 'sample-theme' ) );
		wpbdp_set_option( 'license-key-theme-sample-theme', '' );

		$this->assert_requests_verify_certificate();
	}

	/**
	 * Record request arguments and answer without touching the network.
	 *
	 * @since x.x
	 *
	 * @param false|array|\WP_Error $response Short-circuit value.
	 * @param array                 $args     Request arguments.
	 * @param string                $url      Request URL.
	 *
	 * @return array
	 */
	public function capture_http_request( $response, $args, $url ) {
		$this->requests[ $url ] = $args;

		return array(
			'headers'  => array(),
			'body'     => $this->response_body,
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
	 */
	private function assert_requests_verify_certificate() {
		$this->assertNotEmpty( $this->requests, 'No request was made.' );

		foreach ( $this->requests as $url => $args ) {
			$this->assertStringStartsWith( WPBDP_Licensing::STORE_URL, $url );
			$this->assertTrue( $args['sslverify'], 'Certificate verification is off for ' . $url );
		}
	}

	/**
	 * @since x.x
	 *
	 * @param object $instance Object to call the method on.
	 * @param string $method   Private method name.
	 * @param array  $args     Method arguments.
	 *
	 * @return mixed
	 */
	private function call_private( $instance, $method, $args ) {
		$reflection = new ReflectionMethod( $instance, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $instance, $args );
	}
}
