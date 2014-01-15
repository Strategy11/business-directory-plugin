<?php
// TODO: static:: requires PHP 5.3.0. Check if this is OK.

abstract class WPBDP_DB_Model {
    
    const TABLE_NAME = '';

    protected $id = null;
    
    public static function get_table() {
        global $wpdb;

        if ( !static::TABLE_NAME )
            throw new Exception( 'Table name for model not specified.' );

        return $wpdb->prefix . 'wpbdp_' . static::TABLE_NAME;
    }    
    
    public static function get( $id ) {
        return static::find( array( 'id' => $id, '_single' => true ) );
    }

    public static function find( $args = array(), $lightweight=false ) {
        global $wpdb;
        
        $query = "SELECT * FROM " . static::get_table() . " WHERE 1=1";
        $single = isset( $args['_single'] ) && true == $args['_single'];        
        
        foreach ( $args as $arg => $value ) {
            if ( is_null( $value ) || '_single' == $arg )
                continue;
            
            $query .= $wpdb->prepare( " AND {$arg}=" . ( is_int( $value ) ? '%d' : '%s' ), $value );
        }
        
        if ( $single )
            $query .= " LIMIT 1";
        
        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( ! $lightweight ) {
            foreach ( $results as &$r ) {
                $r = new static( $r );
            }
        }
        
        return $results && $single ? $results[0] : $results;
    }
    
    abstract public function save();
    abstract public function delete();
    
    public function get_id() {
        return $this->id;
    }

    protected function fill_from_data( &$data = array(), $defaults = array() ) {
        $data = (array) $data;
        $keys = array_unique( array_merge( array_keys( $data ), array_keys( $defaults ) ) );
        
        foreach ( $keys as $k ) {
            $v = isset( $data[ $k ] ) ? maybe_unserialize( $data[ $k ] ) : null;
            
            if ( $v )
                $this->{$k} = $v;
            elseif( isset( $defaults [ $k ] ) )
                $this->{$k} = $defaults[ $k ];
        }
    }

}
