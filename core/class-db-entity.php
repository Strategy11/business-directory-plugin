<?php
require_once( WPBDP_PATH . 'core/class-db-entity-error-list.php' );

/**
 * @since 3.7
 */
class WPBDP_DB_Entity {

    /*    protected $data = array();*/
    public $errors;
    private $_is_new_record = true;


    public function __construct( $args = array() ) {
        $this->errors = new WPBDP_DB_Entity_Error_List();
        $info = self::get_entity_info( $this );

        foreach ( array_keys( $info['columns'] ) as $col_name ) {
            $v = null;

            if ( array_key_exists( $col_name, $args ) )
                $v = in_array( $col_name, $info['serialized'], true ) ? maybe_unserialize( $args[ $col_name ] ) : $args[ $col_name ];

            $this->{$col_name} = $v;
        }

/*        foreach ( $args as $p => $v ) {
            if ( in_array( $p, $info['serialized'], true ) )
                $v = maybe_unserialize( $v );

            $this->{$p} = $v;
//            $this->data[ $p ] = $v;
        }*/
    }

    public function is_new() {
        return $this->_is_new_record;
    }

    public function is_valid() {
        $this->errors->clear();
        $this->sanitize();
        $this->validate();

        return $this->errors->is_empty();
    }

    public function save( $validate = true ) {
        if ( $this->_is_new_record )
            return $this->insert_record( $validate );
        else
            return $this->update_record( $validate );
    }

    public function update( $values = array(), $validate = true ) {
        $info = self::get_entity_info( $this );

        foreach ( array_keys( $info['columns'] ) as $col_name ) {
            if ( array_key_exists( $col_name, $values ) )
                $this->{$col_name} = $values[ $col_name ];
        }

        return $this->save( $validate );
    }

    public function __get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) )
            return call_user_func( array( $this, 'get_' . $key ) );

        if ( isset( $this->{$key} ) )
            return $this->{$key};

        throw new Exception( sprintf( 'Invalid property name: %s', $key ) );
    }

    public function __set( $key, $value ) {
        if ( method_exists( $this, 'set_' . $key ) )
            return call_user_func( array( $this, 'set_' . $key ), $value );

        // XXX: Maybe restrict this to model columns only?
        $this->{$key} = $value;
    }


    private function prepare_row() {
        $info = self::get_entity_info( $this );
        $row = array();

        foreach ( $info['columns'] as $col_name => $col_data ) {
            //$value = array_key_exists( $col_name, $this->data ) ? $this->data[ $col_name ] : $col_data['default'];
            $value = isset( $this->{$col_name} ) ? $this->{$col_name} : $col_data['default'];

            if ( $col_data['serialized'] && ! is_scalar( $value ) )
                $value = maybe_serialize( $value );

            $row[ $col_name ] = $value;
        }

        if ( isset( $info['columns']['updated_at'] ) )
            $row[ 'updated_at' ] = current_time( 'mysql' );

        return $row;
    }

    private function update_record( $validate = true ) {
        global $wpdb;

        if ( $validate && ! $this->is_valid() )
            return false;

        $info = self::get_entity_info( $this );
        $row = $this->prepare_row();
        unset( $row[ $info['primary_key'] ] );

        if ( isset( $info['columns']['updated_at'] ) )
            $row['updated_at'] = current_time( 'mysql' );

        $where = array();
        $where[ $info['primary_key'] ] = $this->{$info['primary_key']};

        $res = $wpdb->update( $info['table'], $row, $where );
        return false !== $res;
    }

    private function insert_record( $validate = true ) {
        global $wpdb;

        if ( $validate && ! $this->is_valid() )
            return false;

        $info = self::get_entity_info( $this );
        $row = $this->prepare_row();
        unset( $row[ $info['primary_key'] ] );

        if ( isset( $info['columns']['created_at'] ) )
            $row['created_at'] = current_time( 'mysql' );

        if ( isset( $info['columns']['updated_at'] ) )
            $row['updated_at'] = current_time( 'mysql' );

        $res = $wpdb->insert( $info['table'], $row );

        if ( $res ) {
            //$this->data[ $info['primary_key'] ] = $wpdb->insert_id;
            $this->{$info['primary_key']} = $wpdb->insert_id;
            $this->_is_new_record = false;
        }

        return $res;
    }

    protected function sanitize() { }
    protected function validate() { }

    // "Hooks". {{
    static function before_find( $query ) { return $query; }

    protected function before_validation() { }
    protected function after_validation() { }
    protected function before_save( $new_record ) { }
    protected function after_save( $new_record ) { }
    protected function before_destroy() { }
    protected function after_destroy() { }
    // }}

    public function destroy() {
        global $wpdb;

        if ( $this->_is_new_record )
            return false;

        $info = self::get_entity_info( $this );
        $table = $info['table'];
        $pk = $info['primary_key'];
        $id = $this->{$pk};

        return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$pk} = %s", $id ) );
    }

    private static function get_entity_info( $cls_or_obj ) {
        global $wpdb;
        static $cache = array();

        $cls = is_object( $cls_or_obj ) ? get_class( $cls_or_obj ) : $cls_or_obj;

        if ( isset( $cache[ $cls ] ) )
            return $cache[ $cls ];

        $class_vars = get_class_vars( $cls );

        $res = array();
        $res['classname'] = $cls;
        $res['table_name'] = isset( $class_vars[ '_table_name' ] ) ? $class_vars['_table_name'] : strtolower( $cls ) . 's';
        $res['primary_key'] = isset( $class_vars['_primary_key'] ) ? $class_vars['_primary_key'] : 'id';
        $res['table'] = $wpdb->prefix . $res['table_name'];
        $res['serialized'] = isset( $class_vars['_serialized'] ) ? $class_vars['_serialized'] : array();
        $res['nocache'] = isset( $class_vars['_nocache'] ) ? (bool) $class_vars['_nocache'] : false;

        // Columns.
        $res['columns'] = array();
        foreach ( $wpdb->get_results( "SHOW COLUMNS FROM " . $res['table'], ARRAY_A ) as $col ) {
            $res['columns'][ $col['Field'] ] = array( 'type' => $col['Type'],
                                                      'nullable' => ( 'yes' == strtolower( $col['Null'] ) ),
                                                      'default' => $col['Default'],
                                                      'serialized' => in_array( $col['Field'], $res['serialized'], true ) );
        }

        $cache[ $cls ] = $res;

        return $res;
    }

    public static function _find( $args, $cls ) {
        global $wpdb;

        $entity = self::get_entity_info( $cls );
        $query = array(
            'where' => '',
            'limit' => '',
            'orderby' => '',
            'order' => ''
        );
        $res = array();

        if ( 'first' == $args || 'last' == $args ) {
            $query['orderby'] = $entity['primary_key'];
            $query['order'] = ( 'last' == $args ? 'DESC' : 'ASC' );
            $query['limit'] = 1;
        } elseif ( empty( $args ) || 'all' == $args ) {
        } elseif ( is_scalar( $args ) ) {
            $query['where'] = array( $entity['primary_key'] => $args );
            $query['limit'] = 1;
        } elseif ( is_array( $args ) ) {
            if ( isset( $args['_limit'] ) ) {
                $query['limit'] = $args['_limit'];
                unset( $args['_limit'] );
            }

            $query['where'] = $args;
        }

        if ( method_exists( $cls, 'before_find' ) )
            $query = call_user_func( $cls . '::before_find', $query );

        // We only cache individual records (but we could cache more).
        $cache_result = ( ! $entity['nocache'] && ! empty( $query['where'][ $entity['primary_key'] ] ) );
        $cache_id = $cache_result ? ( is_numeric( $query['where'][ $entity['primary_key'] ] ) ? $query['where'][ $entity['primary_key'] ] : sha1( $query['where'][ $entity['primary_key'] ] ) ) : false;

        if ( $cache_result ) {
            if ( $cached_record = wp_cache_get( $cache_id, $entity['table_name'] ) ) {
                $o = new $cls( $cached_record );
                $o->_is_new_record = false;

                return $o;
            }
        }

        // Continue building the SQL when there wasn't anything inside the cache or the query is not for an individual record (by PK).
        $sql  = '';
        $sql .= 'SELECT * FROM ' . $entity['table'];

        if ( $query['where'] ) {
            if ( is_array( $query['where'] ) ) {
                $parts = array();

                foreach ( $query['where'] as $c => $v ) {
                    $op = '=';

                    if ( '-' == $c[0] ) {
                        $c = substr( $c, 1 );
                        $op = '!=';
                    }

                    $parts[] = $wpdb->prepare( $c . ' ' . $op . ' ' . '%s', $v );
                }

                $sql .= ' WHERE ( ' . implode( ' AND ', $parts ) . ' )';
            } elseif ( is_string( $query['where'] ) ) {
                $sql .= ' WHERE ' . $query['where'];
            }
        }

        if ( $query['limit'] )
            $sql .= ' LIMIT ' . $query['limit'];

        if ( $query['orderby'] )
            $sql .= ' ORDER BY ' . $query['orderby'] . ' ' . $query['order'];

        //wpbdp_debug_e( $sql );

        $rows = $wpdb->get_results( $sql, ARRAY_A );

        foreach ( $rows as &$r ) {
            $o = new $cls( $r );
            $o->_is_new_record = false;
            $res[] = $o;
        }

        if ( $cache_result && 1 == count( $rows ) )
            wp_cache_set( $cache_id, $rows[0], $entity['table_name'] );

        return ( 1 == $query['limit'] && $res ) ? $res[0] : $res;
    }

}
