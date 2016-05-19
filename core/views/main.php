<?php
class WPBDP__Views__Main extends WPBDP_NView {

    public function dispatch() {
        $html = '';

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

        $listings = '';
        if (wpbdp_get_option('show-listings-under-categories'))
            $listings = $this->view_listings(false);

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

        if ( wpbdp_experimental( 'themes' ) ) {
            $html .= wpbdp_x_render( 'main_page', array( '_full' => true, 'listings' => $listings ) );
            return $html;
        }

        $html .= wpbdp_render( array('businessdirectory-main-page', 'wpbusdirman-index-categories'),
                               array( 'listings' => $listings ) );

        return $html;
    }

}
