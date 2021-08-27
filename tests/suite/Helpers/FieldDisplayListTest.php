<?php

namespace WPBDP\Tests\Plugin\Helpers;

use Brain\Monkey\Functions;
use Phake;

use WPBDP\Tests\TestCase;

use WPBDP_Field_Display_List;
use WPBDP_Form_Field;

class FieldDisplayListTest extends TestCase {

	public function test_isset() {
		$field = Phake::mock( 'WPBDP_Form_Field' );

		Phake::when( $field )->get_id->thenReturn( 1729 );
		Phake::when( $field )->get_short_name->thenReturn( 'somefield' );
		Phake::when( $field )->get_tag->thenReturn( 'sometag' );
		Phake::when( $field )->display_in->thenReturn( true );

		Functions\when( 'absint' )->alias( 'abs' );

		$list = new WPBDP_Field_Display_List( null, null, array( $field ) );

		$this->assertTrue( isset( $list->id1729 ) );
		$this->assertTrue( isset( $list->somefield ) );
		$this->assertTrue( isset( $list->t_sometag ) );
		$this->assertTrue( isset( $list->_h_address ) );
		$this->assertTrue( isset( $list->_h_address_nobr ) );
		$this->assertTrue( isset( $list->html ) );
	}
}
