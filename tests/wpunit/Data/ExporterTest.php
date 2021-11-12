<?php

namespace Data;

use WPBDP\Tests\WPUnitTestCase;
use WPBDP_CSVExporter;

class ExporterTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testDataExport() {
		$this->tester->wantToTest( 'Data export' );
		
		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing Sample',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
			$listing->set_fee_plan( 1 );

			$settings = array(
				'include-sticky-status'   => false,
				'include-expiration-date' => false,
			);

			require_once WPBDP_INC . 'admin/class-csv-exporter.php';
			$exporter = new WPBDP_CSVExporter( $settings, '/tmp/', array( $listing->get_id() ) );

			// Execution
			$exporter->advance();

			// Ensure file exists
			$this->assertFileExists( $exporter->get_file_path() );
		} else {
			$this->assertTrue( is_wp_error( $listing ), $listing->get_error_message() );
		}
	}
}
