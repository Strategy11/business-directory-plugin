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
}

/**
 * @deprecated since next-release. Use {@link WPBDP__Utils} instead.
 */
class WPBDP_Utils extends WPBDP__Utils {}
