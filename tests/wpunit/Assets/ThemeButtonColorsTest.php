<?php
/**
 * Tests for the button CSS variables taken from the theme's theme.json.
 */

namespace Assets;

use ReflectionMethod;
use WP_Theme_JSON_Data;
use WP_Theme_JSON_Resolver;
use WPBDP\Tests\WPUnitTestCase;

/**
 * Theme button colors that reference undefined presets must not reach the BD CSS variables.
 */
class ThemeButtonColorsTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var array
	 */
	private $theme_json = array();

	/**
	 * @since x.x
	 */
	public function setUp() : void {
		parent::setUp();

		if ( ! class_exists( 'WP_Theme_JSON_Data' ) ) {
			$this->markTestSkipped( 'Requires the wp_theme_json_data_theme filter (WP 6.1).' );
		}

		add_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
	}

	/**
	 * @since x.x
	 */
	public function tearDown() : void {
		remove_filter( 'wp_theme_json_data_theme', array( $this, 'filter_theme_json' ) );
		$this->clean_theme_json_cache();

		parent::tearDown();
	}

	/**
	 * @since x.x
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme data.
	 *
	 * @return WP_Theme_JSON_Data
	 */
	public function filter_theme_json( $theme_json ) {
		return $theme_json->update_with( $this->theme_json );
	}

	/**
	 * @since x.x
	 *
	 * @return array
	 */
	public function button_colors_provider() {
		return array(
			'undefined preset falls back to the primary color' => array(
				array( 'background' => 'var(--wp--preset--color--test-missing)', 'text' => 'var( --wp--preset--color--test-missing-text )' ),
				array(),
				array(
					'--bd-main-color'        => '#569AF6',
					'--bd-button-bg-color'   => '#569AF6',
					'--bd-button-text-color' => '#fff',
				),
			),
			'defined preset is used'                           => array(
				array( 'background' => 'var(--wp--preset--color--test-brand)', 'text' => 'var(--wp--preset--color--test-ink)' ),
				array(
					array( 'slug' => 'test-brand', 'name' => 'Brand', 'color' => '#aa2200' ),
					array( 'slug' => 'test-ink', 'name' => 'Ink', 'color' => '#ffffee' ),
				),
				array(
					'--bd-main-color'        => 'var(--wp--preset--color--test-brand)',
					'--bd-button-bg-color'   => 'var(--wp--preset--color--test-brand)',
					'--bd-button-text-color' => 'var(--wp--preset--color--test-ink)',
				),
			),
			'defined background with undefined text'           => array(
				array( 'background' => 'var(--wp--preset--color--test-brand)', 'text' => 'var(--wp--preset--color--test-missing-text)' ),
				array(
					array( 'slug' => 'test-brand', 'name' => 'Brand', 'color' => '#aa2200' ),
				),
				array(
					'--bd-main-color'        => 'var(--wp--preset--color--test-brand)',
					'--bd-button-bg-color'   => 'var(--wp--preset--color--test-brand)',
					'--bd-button-text-color' => null,
				),
			),
			'plain colors are used'                            => array(
				array( 'background' => '#123456', 'text' => '#fefefe' ),
				array(),
				array(
					'--bd-main-color'        => '#123456',
					'--bd-button-bg-color'   => '#123456',
					'--bd-button-text-color' => '#fefefe',
				),
			),
		);
	}

	/**
	 * @dataProvider button_colors_provider
	 *
	 * @since x.x
	 *
	 * @param array $button_colors Button colors in theme.json.
	 * @param array $palette       Palette defined by the theme.
	 * @param array $expected      Expected CSS variables, null when not set.
	 */
	public function testButtonColorsFromThemeJson( $button_colors, $palette, $expected ) {
		$this->set_theme_json( $button_colors, $palette );

		$css_vars = $this->get_css_vars( '#569AF6' );

		foreach ( $expected as $var => $value ) {
			$this->assertSame( $value, $css_vars[ $var ] ?? null, $var );
		}
	}

	/**
	 * A custom primary color still wins over the theme colors.
	 *
	 * @since x.x
	 */
	public function testCustomPrimaryColorOverridesThemeColors() {
		$this->set_theme_json( array( 'background' => 'var(--wp--preset--color--test-missing)' ), array() );

		$css_vars = $this->get_css_vars( '#118833' );

		$this->assertSame( '#118833', $css_vars['--bd-main-color'] );
		$this->assertSame( '#118833', $css_vars['--bd-button-bg-color'] );
		$this->assertSame( '#fff', $css_vars['--bd-button-text-color'] );
	}

	/**
	 * @since x.x
	 *
	 * @param array $button_colors Button colors.
	 * @param array $palette       Palette.
	 */
	private function set_theme_json( $button_colors, $palette ) {
		$this->theme_json = array(
			'version'  => 2,
			'settings' => array( 'color' => array( 'palette' => $palette ) ),
			'styles'   => array( 'elements' => array( 'button' => array( 'color' => $button_colors ) ) ),
		);
		$this->clean_theme_json_cache();
	}

	/**
	 * @since x.x
	 *
	 * @param string $main_color Primary color setting.
	 *
	 * @return array
	 */
	private function get_css_vars( $main_color ) {
		$css_vars = array( '--bd-main-color'        => $main_color );
		$method   = new ReflectionMethod( wpbdp()->assets, 'add_default_theme_css' );
		$method->setAccessible( true );
		$method->invokeArgs( wpbdp()->assets, array( &$css_vars ) );

		return $css_vars;
	}

	/**
	 * @since x.x
	 */
	private function clean_theme_json_cache() {
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
			return;
		}
		WP_Theme_JSON_Resolver::clean_cached_data();
	}
}
