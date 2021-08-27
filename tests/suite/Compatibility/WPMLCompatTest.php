<?php
/**
 * @package WPBDP\Tests\Plugin\Compatiblity
 */

namespace WPBDP\Tests\Plugin\Compatiblity;

use Brain\Monkey\Functions;
use Mockery;

use function Patchwork\always;

use WPBDP\Tests\TestCase;

use WPBDP_WPML_Compat;

/**
 * TestCase for WPML Compat class.
 */
class WPMLCompatTest extends TestCase {

	public function setup() {
		parent::setup();

		$GLOBALS['sitepress'] = Mockery::mock();
		require_once WPBDP_PATH . 'includes/compatibility/class-wpml-compat.php';
	}

	public function test_correct_page_link_takes_negotiation_type_into_account() {
		$GLOBALS['sitepress']->shouldReceive( 'get_current_language' )
				->zeroOrMoreTimes()->andReturn( 'de' );

		$GLOBALS['sitepress']->shouldReceive( 'get_setting' )
				->with( 'language_negotiation_type' )
				->andReturn( 1 /* anything but 3 */ );

		$link = 'https://example.org/business-directory/';

		Functions\when( 'is_admin' )
			->justReturn( false );

		Functions\expect( 'add_query_arg' )
			->never()
			->with( 'lang', 'de', $link );

		Functions\expect( 'add_action' )->zeroOrMoreTimes();
		Functions\expect( 'add_filter' )->zeroOrMoreTimes();

		$compatibility = new WPBDP_WPML_Compat();

		// Execution
		$compatibility->correct_page_link( $link, 'main' );
		$compatibility->correct_page_link( $link, '/' );
		$compatibility->correct_page_link( $link, 'edit_listing' );
		$compatibility->correct_page_link( $link, 'upgrade_listing' );
		$compatibility->correct_page_link( $link, 'delete_listing' );
		$compatibility->correct_page_link( $link, 'all_listings' );
		$compatibility->correct_page_link( $link, 'view_listings' );
		$compatibility->correct_page_link( $link, 'submit_listing' );
	}

	public function test_compat_class_registers_handler_for_ajax_url_filter() {
		$this->redefine( 'WPBDP_WPML_Compat::is_doing_ajax', always( true ) );

		Functions\when( 'is_admin' )->justReturn( true );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wpbdp_ajax_url', Mockery::type( 'callable' ) );

		Functions\expect( 'add_action' )->zeroOrMoreTimes();
		Functions\expect( 'add_filter' )->zeroOrMoreTimes();

		$compatibility = new WPBDP_WPML_Compat();
	}

	public function test_filter_ajax_url_adds_current_language_to_url() {
		$ajax_url = 'https://example.org/wp-admin/admin-ajax.php';
		$lang     = 'de';

		add_filter( 'wpml_current_language', array( $this, 'set_lang_de' ) );

		$expected = add_query_arg( 'lang', $lang, $ajax_url );

		$wpml = new WPBDP_WPML_Compat();
		$url  = $wpml->filter_ajax_url( $ajax_url );

		$this->assertEquals( $expected, $url );
	}

	public function set_lang_de() {
		return 'de';
	}

	public function test_compat_class_registers_handler_for_before_ajax_dispatch_action() {
		$this->redefine( 'WPBDP_WPML_Compat::is_doing_ajax', always( true ) );

		Functions\when( 'is_admin' )->justReturn( true );

		Functions\expect( 'add_action' )
			->once()
			->with( 'wpbdp_before_ajax_dispatch', Mockery::type( 'callable' ) );

		Functions\expect( 'add_action' )->zeroOrMoreTimes();
		Functions\expect( 'add_filter' )->zeroOrMoreTimes();

		$compatibility = new WPBDP_WPML_Compat();
	}

	public function test_before_ajax_dispatch_switches_language() {
		$_GET['lang'] = 'de';

		Functions\expect( 'is_admin' )->zeroOrMoreTimes();
		Functions\expect( 'add_action' )->zeroOrMoreTimes();
		Functions\expect( 'add_filter' )->zeroOrMoreTimes();

		Functions\expect( 'do_action' )
			->once()
			->with( 'wpml_switch_language', 'de' );

		$compatibility = new WPBDP_WPML_Compat();

		$compatibility->before_ajax_dispatch( null );
	}

	public function test_compat_class_registers_handlers_to_translate_field_attributes() {
		$this->redefine( 'WPBDP_WPML_Compat::is_doing_ajax', always( true ) );

		Functions\when( 'is_admin' )->justReturn( true );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wpbdp_render_field_label', Mockery::type( 'callable' ), 10, 2 );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wpbdp_render_field_description', Mockery::type( 'callable' ), 10, 2 );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wpbdp_display_field_label', Mockery::type( 'callable' ), 10, 2 );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'wpbdp_category_fee_selection_label', Mockery::type( 'callable' ), 10, 2 );

		Functions\expect( 'add_action' )->zeroOrMoreTimes();
		Functions\expect( 'add_filter' )->zeroOrMoreTimes();

		$compatibility = new WPBDP_WPML_Compat();
	}

	public function test_language_switcher_for_tags() {
		$_GET['lang'] = 'de';

		$languages['de'] = array(
			'language_code' => 'de',
		);

		add_filter( 'wpml_current_language', array( $this, 'set_lang_de' ) );

		$GLOBALS['sitepress']->shouldReceive( 'get_setting' )
				->zeroOrMoreTimes()->andReturn( false );

		$GLOBALS['sitepress']->shouldReceive( 'language_negotiation_type' )
			->zeroOrMoreTimes()->andReturn( 3 );

		Functions\when( 'is_admin' )->justReturn( true );

		Functions\when( 'wpbdp_current_view' )->justReturn( 'show_tag' );

		Functions\when( 'wpbdp_current_tag_id' )->justReturn( rand() + 1 );

		Functions\when( 'get_term_link' )
			->justReturn( 'https://www.example.com/bd/wpbdp_tag/tag1?lang=en' );

		$WPML_Compat = new WPBDP_WPML_Compat();

		$langs = $WPML_Compat->language_switcher( $languages );

		$this->assertEquals( 'https://www.example.com/bd/wpbdp_tag/tag1?lang=de', $langs['de']['url'] );

	}
}

