<?php
class WPBDP__Views__Main extends WPBDP_NView {

    private function warnings() {
        if ( count(get_terms(WPBDP_CATEGORY_TAX, array('hide_empty' => 0))) == 0 ) {
            if (is_user_logged_in() && current_user_can('install_plugins')) {
                $html .= wpbdp_render_msg( _x('There are no categories assigned to the business directory yet. You need to assign some categories to the business directory. Only admins can see this message. Regular users are seeing a message that there are currently no listings in the directory. Listings cannot be added until you assign categories to the business directory.', 'templates', 'WPBDM'), 'error' );
            } else {
                $html .= "<p>" . _x('There are currently no listings in the directory.', 'templates', 'WPBDM') . "</p>";
            }
        }

        if (current_user_can('administrator')) {
            if ($errors = wpbdp_payments_api()->check_config()) {
                foreach ($errors as $error) {
                    $html .= wpbdp_render_msg($error, 'error');
                }
            }
        }

        if ( current_user_can( 'administrator' ) && wpbdp_get_option( 'hide-empty-categories' ) &&
             wp_count_terms( WPBDP_CATEGORY_TAX, 'hide_empty=0' ) > 0 && wp_count_terms( WPBDP_CATEGORY_TAX, 'hide_empty=1' ) == 0 ) {
            $msg = _x( 'You have "Hide Empty Categories" on and some categories that don\'t have listings in them. That means they won\'t show up on the front end of your site. If you didn\'t want that, click <a>here</a> to change the setting.',
                       'templates',
                       'WPBDM' );
            $msg = str_replace( '<a>',
                                '<a href="' . admin_url( 'admin.php?page=wpbdp_admin_settings&groupid=listings#hide-empty-categories' ) . '">',
                                $msg );
            $html .= wpbdp_render_msg( $msg );
        }
    }

    public function dispatch() {
        global $wpbdp;

        $html = '';

        // Warnings and messages for admins.
        $html .= $this->warnings();

        // Listings under categories?
        if ( wpbdp_get_option( 'show-listings-under-categories' ) ) {
            require_once ( WPBDP_PATH . 'core/views/all_listings.php' );
            $v = new WPBDP__Views__All_Listings( array( 'menu' => false ) );
            $listings = $v->dispatch();
        } else {
            $listings = '';
        }

        $html = $this->_render_page( 'main_page', array( 'listings' => $listings ) );

        return $html;
    }

}
