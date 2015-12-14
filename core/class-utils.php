<?php

class WPBDP_Utils {

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

}
