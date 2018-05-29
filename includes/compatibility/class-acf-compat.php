<?php
/**
 * @package WPBDP/Compatibility/ACF Compat
 */

// phpcs:disable Squiz,PEAR,Generic,WordPress,PSR2

/**
 * @SuppressWarnings(PHPMD)
 */
class WPBDP_ACF_Compat {

    public function __construct() {
        add_filter( 'wpbdp_get_option_disable-cpt', array( $this, 'disable_cpt') );
    }

    public function disable_cpt( ){
        return true;
    }
}
