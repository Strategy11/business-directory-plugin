<?php

namespace Data;

use WPBDP_CSVExporter;

class ExporterTest extends \Codeception\Test\Unit {

	/**
     * @var \WpunitTester
     */
    protected $tester;

	public function testDataExport() {	

		$listing = wpbdp_save_listing( array(
			'post_author' => 1,
			'post_type'   => WPBDP_POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'Listing Sample',
		) );
		if ( $listing ) {
			$listing->set_fee_plan( 1 );

			$settings   = array(
				'include-sticky-status'   => false,
				'include-expiration-date' => false,
			);

			require_once WPBDP_INC . 'admin/class-csv-exporter.php';
			$exporter = new WPBDP_CSVExporter( $settings, '/tmp/', array( $listing_id ) );

			// Execution
			$exporter->advance();

			//Ensure file exists
			$this->assertFileExists( $export->get_file_path() );
		} else {
			$this->assertNull( $listing, 'Error creating listing' );
		}
	}
}
