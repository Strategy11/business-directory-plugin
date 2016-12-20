<?php

class WPBDP__Utils {

    /**
     * @since 3.6.10
     */
    public static function normalize( $val = '', $opts = array() ) {
        $res = strtolower( $val );
        $res = remove_accents( $res );
        $res = preg_replace( '/\s+/', '_', $res );
        $res = preg_replace( '/[^a-zA-Z0-9_-]+/', '', $res );

        return $res;
    }

    /**
     * @since next-release
     */
    public static function sort_by_property( &$array, $prop ) {
        uasort( $array,
                create_function( '$x, $y', '$x_ = (array) $x; $y_ = (array) $y; return $x_["' . $prop . '"] - $y_["' . $prop . '"];' ) );
    }

    /**
     * @since next-release
     */
    public static function table_exists( $table ) {
        global $wpdb;

        $res = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        return count( $res ) > 0;
    }

    /**
     * @since next-release
     */
    public static function table_has_col( $table, $col ) {
        if ( ! self::table_exists( $table ) )
            return false;

        global $wpdb;
        $columns = wp_filter_object_list( $wpdb->get_results( "DESCRIBE {$table}" ), null, null, 'Field' );
        return in_array( $col, $columns, true );
    }

    /**
     * @since next-release
     */
    public static function table_drop_col( $table, $col ) {
        if ( ! self::table_has_col( $table, $col ) )
            return false;

        global $wpdb;
        $wpdb->query( "ALTER TABLE {$table} DROP COLUMN {$col}" );
    }

}

/**
 * @deprecated since next-release. Use {@link WPBDP__Utils} instead.
 */
class WPBDP_Utils extends WPBDP__Utils {}
