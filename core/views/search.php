<?php

class WPBDP__Views__Search extends WPBDP_NView {

    public function dispatch() {
        $listings_api = wpbdp_listings_api();
        $quick_search = false;

        // Are we performing an advanced search or a quick search?
        if ( ! empty ( $_POST['q'] ) )
            $quick_search = true;

        if ( $quick_search ) {
            $keywords = trim( $_POST['q'] );
            $location = ! empty( $_POST['location'] ) ? $_POST['location'] : '';

            $results = $listings_api->quick_search_2( $keywords, $location );

            wpbdp_debug_e( $results );
        }


    }

}

