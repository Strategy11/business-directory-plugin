<?php
class WPBDP_DirectoryController {

    public $action = null;

    private $current_category = 0;
    private $current_tag = 0;
    private $current_listing = 0;

    private $router = null;
    private $output = null;

    public function _handle_action(&$wp) {
        global $wpbdp;

        if ( is_page() && in_array( get_the_ID(), wpbdp_get_page_ids( 'main' ) ) ) {
            $action = get_query_var('action') ? get_query_var('action') : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '' );

            if (get_query_var('category_id') || get_query_var('category')) $action = 'browsecategory';
            if (get_query_var('tag')) $action = 'browsetag';
            if (get_query_var('id') || get_query_var('listing')) $action = 'showlisting';

            if (!$action) $action = 'main';

            $this->action = $action;
        } else {
            $this->action = null;

            if ( $wpbdp->is_plugin_page() ) {
                global $post;

                if ( wpbdp_has_shortcode( $post->post_content, 'businessdirectory-submitlisting' ) ||
                     wpbdp_has_shortcode( $post->post_content, 'WPBUSDIRMANADDLISTING' ) ) {
                     $this->action = 'submitlisting';
                } elseif ( wpbdp_has_shortcode( $post->post_content, 'businessdirectory-search' ) ||
                           wpbdp_has_shortcode( $post->post_content, 'businessdirectory_search' ) ) {
                    $this->action = 'search';
                }
            }
        }

    }

}

