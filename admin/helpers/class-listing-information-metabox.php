<?php
/**
 * @since next-release
 */
class WPBDP__Admin__Metaboxes__Listing_Information {

    function __construct( $post_id ) {
        $this->listing = WPBDP_Listing::get( $post_id );
    }

    function render() {
        $vars = array();
        $vars['plans'] = WPBDP_Fee_Plan::find();
        $vars['listing'] = $this->listing;
        $vars['current_plan'] = $this->listing->get_fee_plan();
        $vars['payments'] = $this->listing->get_latest_payments();

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/metaboxes-listing-information.tpl.php', $vars );
    }

}
