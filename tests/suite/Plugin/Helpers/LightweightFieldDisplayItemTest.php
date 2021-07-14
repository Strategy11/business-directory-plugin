<?php

namespace WPBDP\Tests\Plugin\Helpers;

use Phake;

use WPBDP\Tests\TestCase;

use _WPBDP_Lightweight_Field_Display_Item;
use WPBDP_Form_Field;

class LightweightFieldDisplayItemTest extends TestCase {

    public function test_isset() {
        $field = Phake::mock( 'WPBDP_Form_Field' );

        // Phake::when( $field )->get_id->thenReturn( 1729 );
        // Phake::when( $field )->get_short_name->thenReturn( 'somefield' );
        // Phake::when( $field )->get_tag->thenReturn( 'sometag' );
        // Phake::when( $field )->display_in->thenReturn( true );

        $item = new _WPBDP_Lightweight_Field_Display_Item( $field, null, null );

        $this->assertTrue( isset( $item->html ) );
        $this->assertTrue( isset( $item->value ) );
        $this->assertTrue( isset( $item->raw ) );
        $this->assertTrue( isset( $item->id ) );
        $this->assertTrue( isset( $item->label ) );
        $this->assertTrue( isset( $item->tag ) );
        $this->assertTrue( isset( $item->field ) );
    }
}
