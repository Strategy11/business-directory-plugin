<?php
/**
 * Tests for the WPML search fallback to the original listing's field value.
 */

namespace Compatibility;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP__Listing_Search;
use WPBDP_Form_Field;
use WPBDP_WPML_Compat;

/**
 * Translations with an empty field show the original's value, so search must find them.
 */
class WPMLSearchOriginalValueTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/**
	 * @var WPBDP_Form_Field
	 */
	private $field;

	/**
	 * @var int
	 */
	private $trid = 0;

	/**
	 * @var \WP_Screen|null
	 */
	private $previous_screen;

	/**
	 * @var mixed
	 */
	private $previous_sitepress;

	/**
	 * @var bool
	 */
	private static $created_table = false;

	/**
	 * A real table, created before the per-test transaction: MySQL can't self-join a temporary table.
	 *
	 * @since x.x
	 */
	public static function setUpBeforeClass() : void {
		global $wpdb;

		parent::setUpBeforeClass();
		self::$created_table = ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'icl_translations' ) );
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}icl_translations (
				translation_id bigint(20) NOT NULL AUTO_INCREMENT,
				element_type varchar(60) NOT NULL DEFAULT 'post_post',
				element_id bigint(20) DEFAULT NULL,
				trid bigint(20) NOT NULL,
				language_code varchar(7) NOT NULL,
				source_language_code varchar(7) DEFAULT NULL,
				PRIMARY KEY (translation_id)
			)"
		);
	}

	/**
	 * @since x.x
	 */
	public static function tearDownAfterClass() : void {
		global $wpdb;

		if ( self::$created_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}icl_translations" );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * @since x.x
	 */
	protected function after_setup() {
		require_once WPBDP_PATH . 'includes/compatibility/class-wpml-compat.php';
		require_once WPBDP_PATH . 'includes/helpers/class-listing-search.php';

		$this->field = new WPBDP_Form_Field(
			array(
				'association' => 'meta',
				'field_type'  => 'textfield',
				'label'       => 'WPML search field',
			)
		);
		$this->field->save();

		// The search filters are front-end only; earlier tests may leave an admin screen set.
		$this->previous_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		set_current_screen( 'front' );
		// The compat class only needs the WPML object for URL settings, not used by search.
		$this->previous_sitepress = isset( $GLOBALS['sitepress'] ) ? $GLOBALS['sitepress'] : null;
		$GLOBALS['sitepress']     = new \stdClass();
		new WPBDP_WPML_Compat();
	}

	/**
	 * @since x.x
	 */
	public function tearDown() : void {
		$GLOBALS['sitepress']      = $this->previous_sitepress;
		$GLOBALS['current_screen'] = $this->previous_screen;
		parent::tearDown();
	}

	/**
	 * @since x.x
	 */
	public function test_finds_empty_translation_of_matching_original() {
		$original    = $this->listing( 'VAL-ORIG' );
		$translation = $this->listing( '' );
		$this->link( $original, $translation );

		$results = $this->search( 'VAL-ORIG' );

		$this->assertContains( $original, $results );
		$this->assertContains( $translation, $results );
	}

	/**
	 * @since x.x
	 */
	public function test_translation_with_own_value_is_only_found_by_its_own_value() {
		$original    = $this->listing( 'VAL-A' );
		$translation = $this->listing( 'VAL-B' );
		$this->link( $original, $translation );

		$this->assertSame( array( $original ), $this->search( 'VAL-A' ) );
		$this->assertSame( array( $translation ), $this->search( 'VAL-B' ) );
	}

	/**
	 * @since x.x
	 */
	public function test_matching_translation_does_not_pull_in_the_original() {
		$original    = $this->listing( '' );
		$translation = $this->listing( 'VAL-TRANS' );
		$this->link( $original, $translation );

		$this->assertSame( array( $translation ), $this->search( 'VAL-TRANS' ) );
	}

	/**
	 * @since x.x
	 */
	public function test_listing_without_translations_is_unchanged() {
		$listing = $this->listing( 'VAL-ALONE' );
		$this->listing( '' );

		$this->assertSame( array( $listing ), $this->search( 'VAL-ALONE' ) );
	}

	/**
	 * @since x.x
	 *
	 * @param string $value Field value, empty for none.
	 *
	 * @return int
	 */
	private function listing( $value ) {
		$id = wp_insert_post(
			array(
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'WPML listing ' . wp_generate_password( 6, false ),
			)
		);

		if ( '' !== $value ) {
			update_post_meta( $id, '_wpbdp[fields][' . $this->field->get_id() . ']', $value );
		}

		return $id;
	}

	/**
	 * @since x.x
	 *
	 * @param int $original    The original (English) listing.
	 * @param int $translation The Spanish translation.
	 */
	private function link( $original, $translation ) {
		global $wpdb;

		++$this->trid;
		$type = 'post_' . WPBDP_POST_TYPE;
		$wpdb->insert( $wpdb->prefix . 'icl_translations', array( 'element_type' => $type, 'element_id' => $original, 'trid' => $this->trid, 'language_code' => 'en', 'source_language_code' => null ) );
		$wpdb->insert( $wpdb->prefix . 'icl_translations', array( 'element_type' => $type, 'element_id' => $translation, 'trid' => $this->trid, 'language_code' => 'es', 'source_language_code' => 'en' ) );
		// A language WPML lists without a post yet.
		$wpdb->insert( $wpdb->prefix . 'icl_translations', array( 'element_type' => $type, 'element_id' => null, 'trid' => $this->trid, 'language_code' => 'de', 'source_language_code' => 'en' ) );
	}

	/**
	 * @since x.x
	 *
	 * @param string $value Value to search the field for.
	 *
	 * @return int[]
	 */
	private function search( $value ) {
		$search  = WPBDP__Listing_Search::from_request(
			array(
				'dosrch'        => 1,
				'listingfields' => array( $this->field->get_id() => $value ),
			)
		);
		$results = array_map( 'intval', $search->get_results() );
		sort( $results );

		return $results;
	}
}
