<?php
/**
 * @package WPBDP\Tests\Plugin\Fields\Types;
 */

namespace WPBDP\Tests\Plugin\Fields\Types;

use Mockery;

use WPBDP\Tests\TestCase;

use WPBDP_FieldTypes_Date;
use WPBDP_Form_Field;

/**
 * Tests for Date field type.
 */
class DateTest extends TestCase {

	/**
	 */
	public function test_setup_validation_uses_internal_format() {
		$internal_format   = 'yyyymmdd';
		$configured_format = 'dd/mm/yy';

		$field = Mockery::mock( 'WPBDP_Form_Field' );

		$field->shouldReceive( 'get_label' )->andReturn( 'Test Field' );
		$field->shouldReceive( 'data' )
			->with( 'date_format' )
			->andReturn( $configured_format );

		$type = new WPBDP_FieldTypes_Date();

		// Execution
		$args = $type->setup_validation( $field, 'date_', null );

		// Verification
		$this->assertEquals( $internal_format, $args['format'] );
	}
}

