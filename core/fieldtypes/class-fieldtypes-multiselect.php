<?php

class WPBDP_FieldTypes_MultiSelect extends WPBDP_FieldTypes_Select {

    public function __construct() {
        parent::__construct( _x('Multiple select list', 'form-fields api', 'WPBDM') );
        $this->set_multiple( true );
    }

    public function get_name() {
        return _x( 'Multiselect List', 'form-fields api', 'WPBDM' );
    }

    public function get_id() {
        return 'multiselect';
    }

    public function get_supported_associations() {
        return array( 'category', 'tags', 'meta' );
    }

}

