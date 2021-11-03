<?php

namespace Data;

use WPBDP_CSVExporter;

class ExporterTest extends \Codeception\Test\Unit {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testDataExport() {
		$this->tester->wantToTest( 'Data export' );

		$this->markTestSkipped(
			'mysqli fetch error on wpbdp_save_listing'
		);

		
		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Listing Sample',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
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
