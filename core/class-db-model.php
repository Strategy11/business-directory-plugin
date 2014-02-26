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
        $res = static::find( array( 'id' => $id, '_single' => true ) );
        return $res;
    }

    public static function find( $args = array(), $lightweight=false ) {
        global $wpdb;
        
        $query = "SELECT * FROM " . static::get_table() . " WHERE 1=1";
        $single = isset( $args['_single'] ) && true == $args['_single'];
        $order = isset( $args['_order'] ) && !empty( $args['_order'] ) ? trim( $args['_order'] ) : null;
        $limit = isset( $args['_limit'] ) && !empty( $args['_limit'] ) ? intval( $args['_limit'] ) : null;
        
        foreach ( $args as $arg => $value ) {
            if ( is_null( $value ) || in_array( $arg, array( '_single', '_order', '_limit' ), true ) )
                continue;
            
            $query .= $wpdb->prepare( " AND {$arg}=" . ( is_int( $value ) ? '%d' : '%s' ), $value );
        }

        if ( $single )
            $limit = 1;

        if ( $order ) {
            $order_field = wpbdp_starts_with( $order, '-' ) ? substr( $order, 1 ) : $order;
            $order_dir = wpbdp_starts_with( $order, '-' ) ? 'DESC' : 'ASC';

            $query .= " ORDER BY {$order_field} {$order_dir}";
        }

        if ( $limit > 0 )
            $query .= " LIMIT {$limit}";

        $results = $wpdb->get_results( $query, ARRAY_A );
        
        if ( ! $lightweight ) {
            foreach ( $results as &$r ) {
                $r = new static( $r );
            }
        }
        
        return ( $results && $single ) ? $results[0] : $results;
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
