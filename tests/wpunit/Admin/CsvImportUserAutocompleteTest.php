<?php
/**
 * Tests for the CSV import default-user autocomplete.
 */

namespace Admin;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_CSVImportAdmin;

/**
 * Tests for the CSV import default-user autocomplete.
 */
class CsvImportUserAutocompleteTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		if ( ! class_exists( 'WPBDP_CSVImportAdmin' ) ) {
			require_once WPBDP_INC . 'admin/csv-import.php';
		}
	}

	/**
	 * @since x.x
	 */
	public function tearDown(): void {
		$_GET     = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function scriptProvider() {
		return array(
			'source' => array( 'assets/js/admin-csv-import.js' ),
			'build'  => array( 'assets/js/admin-csv-import.min.js' ),
		);
	}

	/**
	 * @since x.x
	 *
	 * @dataProvider scriptProvider
	 *
	 * @param string $script Script path relative to the plugin folder.
	 */
	public function testAutocompleteRequestSendsTheGlobalNonce( $script ) {
		$source = file_get_contents( WPBDP_PATH . $script ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$this->assertMatchesRegularExpression( '/wpbdp-autocomplete-user&nonce=[\'"]\s*\+\s*wpbdp_global\.nonce/', $source );
	}

	/**
	 * @since x.x
	 */
	public function testAutocompleteRepliesWithUsersForTheGlobalNonce() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$user_id = self::factory()->user->create( array( 'user_login' => 'csvautocompleteuser' ) );

		$_GET     = array(
			'nonce' => $this->get_global_nonce(),
			'term'  => 'csvautocomplete',
		);
		$_REQUEST = $_GET;

		$response = json_decode( $this->dispatch_autocomplete(), true );

		$this->assertIsArray( $response );
		$this->assertSame( array( $user_id ), array_map( 'intval', wp_list_pluck( $response, 'value' ) ) );
	}

	/**
	 * Read the nonce the admin script gets as wpbdp_global.nonce.
	 *
	 * @since x.x
	 *
	 * @return string
	 */
	private function get_global_nonce() {
		wp_register_script( 'wpbdp-admin-js', WPBDP_ASSETS_URL . 'js/admin.min.js', array(), WPBDP_VERSION, true );
		wpbdp()->assets->global_localize( 'wpbdp-admin-js' );

		$data = (string) wp_scripts()->get_data( 'wpbdp-admin-js', 'data' );
		preg_match( '/"nonce":"([^"]+)"/', $data, $matches );

		return $matches[1] ?? '';
	}

	/**
	 * @since x.x
	 *
	 * @return string
	 */
	private function dispatch_autocomplete() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );

		$output = '';
		try {
			( new WPBDP_CSVImportAdmin() )->ajax_autocomplete_user();
		} catch ( \WPDieException $e ) {
			$output = $e->getMessage();
		}

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ) );

		return $output;
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
