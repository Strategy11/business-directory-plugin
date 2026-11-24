<?php
/**
 * Tests for the WPML language on BD page URLs with the "?lang=" URL format.
 */

namespace Compatibility;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_WPML_Compat;

/**
 * Stand-in for WPML's SitePress object: only the URL format setting is read.
 */
class SitePressStub {

	/**
	 * @param string $key Setting name.
	 *
	 * @return mixed
	 */
	public function get_setting( $key ) {
		return 'language_negotiation_type' === $key ? 3 : null;
	}
}

/**
 * Keep "lang" on contact and report URLs, but not for the default language (WPML redirects it, dropping POST data).
 */
class WPMLPageLinkTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var WPBDP_WPML_Compat
	 */
	private $compat;

	/**
	 * @var string
	 */
	private $current_lang = 'es';

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		require_once WPBDP_PATH . 'includes/compatibility/class-wpml-compat.php';

		$GLOBALS['sitepress'] = new SitePressStub();
		$this->compat         = new WPBDP_WPML_Compat();

		add_filter(
			'wpml_current_language',
			function () {
				return $this->current_lang;
			}
		);
		add_filter(
			'wpml_default_language',
			function () {
				return 'en';
			}
		);
	}

	/**
	 * @since x.x
	 */
	public function tearDown() : void {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @return array
	 */
	public function views_provider() {
		return array(
			'contact form' => array( 'listing_contact' ),
			'report link'  => array( 'flag_listing' ),
			'edit link'    => array( 'edit_listing' ),
		);
	}

	/**
	 * @dataProvider views_provider
	 *
	 * @since x.x
	 *
	 * @param string $view BD view name.
	 */
	public function test_translated_language_is_kept( $view ) {
		$this->current_lang = 'es';

		$url = $this->compat->correct_page_link( 'https://example.test/directorio/?wpbdp_view=' . $view . '&listing_id=5', $view, 5 );

		$this->assertStringContainsString( 'lang=es', $url );
	}

	/**
	 * @dataProvider views_provider
	 *
	 * @since x.x
	 *
	 * @param string $view BD view name.
	 */
	public function test_default_language_is_not_added( $view ) {
		$this->current_lang = 'en';

		$url = $this->compat->correct_page_link( 'https://example.test/directory/?wpbdp_view=' . $view . '&listing_id=5', $view, 5 );

		$this->assertStringNotContainsString( 'lang=', $url );
	}
}
