<?php

/**
 * @since next-release
 */
class WPBDP__Search_Helper {

    public $mode = '';
    public $hints = array();
    public $fields = array();
    public $args = array();
    public $location = '';

    public $tax_query = array();


    public function __construct( $args, $mode = 'AND' ) {
        foreach ( $args as $field_id => $value ) {
            $f = WPBDP_Form_Field::get( $field_id );

            if ( ! $f )
                unset( $this->args[ $field_id ] );

            $this->args[ $field_id ] = wpbdp_get_option( 'quick-search-enable-performance-tricks' ) ? array( $args[ $field_id ] ) : array_map( 'trim', explode( ' ', $args[ $field_id ] ) );
            $this->fields[ $field_id ] = $f;
        }

        $this->mode = $mode;
    }

    public function set_location( $location ) {
        $this->location = $location;
    }

    public function prepare() {
        do_action_ref_array( 'wpbdp_search_prepare', array( $this ) );
    }

}
