<?php
require_once ( WPBDP_PATH . 'core/class-db-query-set.php' );
require_once ( WPBDP_PATH . 'core/class-db-entity.php' );

class WPBDP__DB__Model extends WPBDP_DB_Entity {

    protected $adding = false;

    public static function _objects( $classname ) {
        static $managers_per_class = array();

        if ( ! isset( $managers_per_class[ $classname ] ) )
            $managers_per_class[ $classname ] = new WPBDP__DB__Query_Set( $classname, false, true );

        return $managers_per_class[ $classname ];
    }

    public static function from_db( $fields, $classname ) {
        $obj = new $classname( $fields );
        $obj->adding = false;

        return $obj;
    }

    public static function get_model_info( $classname ) {
        global $wpdb;
        static $cache = array();

        if ( isset( $cache[ $classname ] ) )
            return $cache[ $classname ];

        $cls_vars = get_class_vars( $classname );

        $info                = array();
        $info['class']       = $classname;
        $info['table']       = array( 'name' => isset( $cls_vars['table'] ) ? $wpdb->prefix . $cls_vars['table'] : $wpdb->prefix . strtolower( $classname ) . 's',
                                      'columns' => array() );
        $info['primary_key'] = isset( $cls_vars['primary_key'] ) ? $cls_vars['primary_key'] : 'id';
        $info['serialized']  = isset( $cls_vars['serialized'] ) ? $cls_vars['serialized'] : array();

        foreach ( $wpdb->get_results( "SHOW COLUMNS FROM " . $info['table']['name'], ARRAY_A ) as $col ) {
            $info['table']['columns'][ $col['Field'] ] = array( 'type'       => $col['Type'],
                                                                'nullable'   => ( 'yes' == strtolower( $col['Null'] ) ),
                                                                'default'    => $col['Default'],
                                                                'serialized' => in_array( $col['Field'], $info['serialized'], true ) );
        }

        return $info;
    }

}

// For backwards-compat.
require_once( WPBDP_PATH . 'core/compatibility/class-db-model2.php' );


