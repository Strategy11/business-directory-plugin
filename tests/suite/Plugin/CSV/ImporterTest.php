<?php

use Brain\Monkey\Functions;

use function Patchwork\always;

use WPBDP\Tests\TestCase;

class ImporterTest extends TestCase {

    public function test_sanitize_and_validate_row_decode_html_entities_in_category_names() {
        $csvfile = tempnam( sys_get_temp_dir(), 'ImporterTest' );
        $category_name = null;

        $settings = array(
            'csv-file-separator' => ';',
            'assign-listings-to-user' => false,
        );

        $file = Phake::partialMock( 'SplFileObject', 'php://memory' );

        Phake::when( $file )->seek->thenReturn( null );
        Phake::when( $file )->key->thenReturn( 2 );

        $field_id = rand() + 1;
        $field = Phake::mock( 'WPBDP_FormField' );

        $this->redefine( 'WPBDP_CSV_Import::read_header', always( null ) );
        $this->redefine( 'WPBDP_CSV_Import::get_csv_file', always( $file ) );

        $this->redefine( 'WPBDP_CSV_Import::import_row', function( $data ) use ( &$category_name ) {
            $category_name = $data['categories'][0]['name'];
        });

        Functions\when( 'wpbdp_get_form_field' )->justReturn( $field );

        require_once( WPBDP_INC . 'admin/csv-import.php' );
        $importer = new WPBDP_CSV_Import( null, $csvfile, '', $settings );

        // Execution
        $importer->do_work();

		$this->_test_prepare_categories( $importer );
    }

	/**
	 * @covers WPBDP_CSV_Import::prepare_categories
	 */
	public function _test_prepare_categories( $importer ) {
		$categories = array();
		$errors     = array();
		$value      = 'Ren &amp; Stimpy; Two';
		$expected   = array(
			array(
				'name'    => 'Ren &amp; Stimpy',
				'term_id' => 0,
			),
			array(
				'name'    => 'Two',
				'term_id' => 0,
			),
		);

		$this->run_private_method( array( $importer, 'prepare_categories' ), array( $value, &$categories, &$errors ) );
		$this->assertEquals( $expected, $categories );
	}
}
