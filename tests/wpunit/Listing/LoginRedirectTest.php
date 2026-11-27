<?php
/**
 * Tests for where the directory login view sends visitors.
 */

namespace Listing;

use RuntimeException;
use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Views__Login;

/**
 * Login view redirect tests.
 */
class LoginRedirectTest extends WPUnitTestCase {

	/**
	 * Codeception tester.
	 *
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * Page used as the directory main page.
	 *
	 * @var int
	 */
	private $directory_page_id = 0;

	/**
	 * Load the view and stop redirects before they exit.
	 *
	 * @since x.x
	 */
	protected function after_setup() {
		require_once WPBDP_INC . 'controllers/pages/class-login.php';

		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'test-auth-key' );
		}

		$this->directory_page_id = $this->factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[businessdirectory]',
			)
		);

		add_filter( 'wpbdp_get_page_id', array( $this, 'directory_page_id' ) );
		add_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
		wpbdp_set_option( 'login-url', '' );
		wpbdp_set_option( 'enable-key-access', true );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
	}

	/**
	 * Remove the test filters and reset the request.
	 *
	 * @since x.x
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
		remove_filter( 'wpbdp_get_page_id', array( $this, 'directory_page_id' ) );
		wpbdp_set_option( 'login-url', '' );
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * Use the page created for this test as the directory page.
	 *
	 * @since x.x
	 *
	 * @return int
	 */
	public function directory_page_id() {
		return $this->directory_page_id;
	}

	/**
	 * Throw the redirect location so the test can read it.
	 *
	 * @since x.x
	 *
	 * @param string $location Redirect location.
	 *
	 * @throws RuntimeException Always.
	 */
	public function stop_redirect( $location ) {
		throw new RuntimeException( $location );
	}

	/**
	 * Requested redirect targets and whether they belong to the site.
	 *
	 * @since x.x
	 *
	 * @return array
	 */
	public function redirectTargetProvider() {
		return array(
			'other host'         => array( 'https://example.invalid/collect', false ),
			'protocol relative'  => array( '//example.invalid/collect', false ),
			'same site absolute' => array( '{home}/directory/?wpbdp_view=all_listings', true ),
			'same site relative' => array( '/directory/?wpbdp_view=all_listings', true ),
		);
	}

	/**
	 * A guest signing in with an access key is sent to a same-site page.
	 *
	 * @dataProvider redirectTargetProvider
	 *
	 * @since x.x
	 *
	 * @param string $redirect_to Requested target.
	 * @param bool   $is_local    Whether the target is on the site.
	 */
	public function testAccessKeyLoginRedirect( $redirect_to, $is_local ) {
		$listing_id = $this->factory()->post->create(
			array(
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $listing_id, '_wpbdp[access_key]', 'test-access-key' );
		update_post_meta( $listing_id, '_wpbdp[fields][7]', 'owner@example.com' );

		$this->set_request(
			array(
				'redirect_to' => $redirect_to,
				'method'      => 'access_key',
				'email'       => 'owner@example.com',
				'access_key'  => 'test-access-key',
			)
		);

		$location = $this->dispatch_login();

		$this->assertStringContainsString( 'access_key_hash=', $location );
		$this->assert_target( $redirect_to, $is_local, $location );
	}

	/**
	 * A logged-in visitor is sent to a same-site page.
	 *
	 * @dataProvider redirectTargetProvider
	 *
	 * @since x.x
	 *
	 * @param string $redirect_to Requested target.
	 * @param bool   $is_local    Whether the target is on the site.
	 */
	public function testLoggedInRedirect( $redirect_to, $is_local ) {
		wp_set_current_user( $this->factory()->user->create() );
		$this->set_request( array( 'redirect_to' => $redirect_to ) );

		$this->assert_target( $redirect_to, $is_local, $this->dispatch_login() );
	}

	/**
	 * The custom login page receives a same-site return address only.
	 *
	 * @since x.x
	 */
	public function testCustomLoginUrlGetsSameSiteRedirect() {
		wpbdp_set_option( 'login-url', 'https://sso.example.com/login' );
		$this->set_request( array( 'redirect_to' => 'https://example.invalid/collect' ) );

		$location = $this->dispatch_login();
		wp_parse_str( (string) wp_parse_url( $location, PHP_URL_QUERY ), $query );

		$this->assertSame( 'sso.example.com', wp_parse_url( $location, PHP_URL_HOST ) );
		$this->assertSame( wpbdp_url( 'main' ), $query['redirect_to'] );
	}

	/**
	 * Set the login request parameters.
	 *
	 * @since x.x
	 *
	 * @param array $args Request parameters.
	 */
	private function set_request( $args ) {
		$args['redirect_to'] = str_replace( '{home}', home_url(), $args['redirect_to'] );

		$_POST    = $args;
		$_REQUEST = $args;
	}

	/**
	 * Run the login view and return the redirect location.
	 *
	 * @since x.x
	 *
	 * @return string
	 */
	private function dispatch_login() {
		$view = new WPBDP__Views__Login();

		try {
			$view->dispatch();
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}

		$this->fail( 'The login view did not redirect.' );
	}

	/**
	 * Check the redirect stays on the site and goes where expected.
	 *
	 * @since x.x
	 *
	 * @param string $redirect_to Requested target.
	 * @param bool   $is_local    Whether the target is on the site.
	 * @param string $location    Actual redirect location.
	 */
	private function assert_target( $redirect_to, $is_local, $location ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$this->assertContains( wp_parse_url( $location, PHP_URL_HOST ), array( null, $site_host ) );

		$expected = $is_local ? str_replace( '{home}', home_url(), $redirect_to ) : wpbdp_url( 'main' );
		$this->assertStringStartsWith( $expected, $location );
	}
}
