<?php
/**
 * Includes tests for the Fees class.
 */

namespace Fields;

use WPBDP\Tests\WPUnitTestCase;
use Codeception\Util\Debug;
use WPBDP_Form_Field;

/**
 * Tests for field CRUDE
 */
class FieldCrudeTest extends WPUnitTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	private $field_id = null;

	public function testFeeCrude() {
		$this->tester->wantToTest( 'Listing Field CRUDE' );
		$this->createField();
		$this->editField();
		$this->deleteField();
	}

	/**
	 * Create field
	 */
	private function createField() {
		$field = new WPBDP_Form_Field( array(
			'association' => 'meta',
			'field_type'  => 'textfield',
			'label'       => 'Sample Text Field'
		) );
		$res   = $field->save();

		if ( is_wp_error( $res ) ) {
			$this->fail( 'Field creation failed : ' . $res->get_error_message() );
		} else {
			$this->assertTrue( is_int( $field->get_id() ), 'Field Created' );
			$this->assertEquals( $field->get_label(), 'Sample Text Field' );
			$this->field_id = $field->get_id();
		}
	}

	/**
	 * Edit field
	 */
	private function editField() {
		if ( ! $this->field_id ) {
			$this->fail( 'Could not retrieve previously created field' );
		} else {
			$existing_field = WPBDP_Form_Field::get( $this->field_id );
			if ( ! $existing_field ) {
				$this->fail( 'Field does not exist' );
			} else {
				$field = new WPBDP_Form_Field( array(
					'id'          => $this->field_id,
					'association' => 'meta',
					'field_type'  => 'textfield',
					'label'       => 'Sample Text Field Updated'
				) );
				$res   = $field->save();

				if ( is_wp_error( $res ) ) {
					$this->fail( 'Field update failed : ' . $res->get_error_message() );
				} else {
					$this->assertEquals( $field->get_id(), $this->field_id );
					$this->assertEquals( $field->get_label(), 'Sample Text Field Updated' );
				}
			}
		}
	}

	/**
	 * Delete field
	 */
	private function deleteField() {
		if ( ! $this->field_id ) {
			$this->fail( 'Could not retrieve previously created field' );
		} else {
			$field = WPBDP_Form_Field::get( $this->field_id );
			if ( ! $field ) {
				$this->fail( 'Field does not exist' );
			} else {
				$res = $field->delete();

				if ( is_wp_error( $res ) ) {
					$this->fail( 'Field update failed : ' . $res->get_error_message() );
				} else {
					$this->assertTrue( $res, 'Field deleted' );
				}
			}
		}
	}
}
