<?php

namespace WPBDP\Tests\Plugin\CSV;

use Brain\Monkey\Functions;
use Mockery;
use Phake;

use function Patchwork\always;

use WPBDP\Tests\TestCase;

use WPBDP_CSVExporter;

class ExporterTest extends TestCase {

	public function test_extract_data() {
		$listing_id = rand() + 1;
		$settings   = array(
			'include-sticky-status'   => false,
			'include-expiration-date' => false,
		);

		$form_field = Mockery::mock( 'WPBDP_Form_Field' );
		$listing    = Mockery::mock( 'WPBDP_Listing' );
		$fee_plan   = Mockery::mock( 'WPBPD__FeePlan' );

		$form_field->shouldReceive(
			array(
				'get_short_name'  => 'category',
				'get_association' => 'category',
			)
		);

		$listing->shouldReceive(
			array(
				'get_id'       => $listing_id,
				'get_fee_plan' => $fee_plan,
			)
		);

		$fee_plan->fee_id = rand() + 1;

		$csvfile = fopen( 'php://temp', 'a' );
		$content = '';

		Functions\when( 'wpbdp_get_form_fields' )->justReturn( array( $form_field ) );
		Functions\when( 'wpbdp_get_listing' )->justReturn( $listing );
		Functions\when( 'wp_get_post_terms' )->justReturn( array( 'Ren &amp; Stimpy' ) );

		$this->redefine( 'WPBDP_CSVExporter::get_csvfile', always( $csvfile ) );
		$this->redefine(
			'WPBDP_CSVExporter::prepare_content',
			function( $the_content ) use ( &$content ) {
				$content = $the_content;
			}
		);

		require_once WPBDP_INC . 'admin/csv-export.php';
		$exporter = new WPBDP_CSVExporter( $settings, '/tmp/', array( $listing_id ) );

		// Execution
		$exporter->advance();

		// Verification
		$this->assertEquals( '"Ren & Stimpy","' . $fee_plan->fee_id . '"', $content );
	}
}
