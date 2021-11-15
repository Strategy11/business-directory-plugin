<?php

namespace Data;

require_once WPBDP_INC . 'admin/class-csv-exporter.php';

use WPBDP\Tests\BaseListingTestCase;
use WPBDP_CSVExporter;

class ExporterTest extends BaseListingTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function testDataExport() {
		$this->tester->wantToTest( 'Data export' );
		$this->markTestSkipped(
			'Cannot create file to test'
		);
		wpbdp_set_option( 'new-post-status', 'publish' ); // New post status will be set to publish.
		$listing = wpbdp_save_listing(
			array(
				'post_author' => 1,
				'post_type'   => WPBDP_POST_TYPE,
				'post_status' => 'pending_payment',
				'post_title'  => '(no title)',
			)
		);
		if ( ! is_wp_error( $listing ) ) {
			$payment = $listing->generate_or_retrieve_payment();
			// Execute
			$payment->status = 'completed';
			$payment->save();

			$settings = array(
				'include-sticky-status'   => false,
				'include-expiration-date' => false,
			);
			
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
