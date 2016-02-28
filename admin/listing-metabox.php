<?php
class WPBDP_Admin_Listing_Metabox {

    private $listing = null;

    public function __construct( $listing_id ) {
        $this->listing = WPBDP_Listing::get( $listing_id );
    }

    public function render() {
        $tabs = array( 'generalinfo' => _x('General', 'admin', 'WPBDM'),
                       'fees' => _x('Fee Details', 'admin', 'WPBDM'),
                       'transactions' => _x('Transactions', 'admin', 'WPBDM') );

        // Determine selected tab.
        $selected_tab = 'generalinfo';
        if ( isset( $_GET['wpbdmaction'] ) && in_array( $_GET['wpbdmaction'], array( 'removecategory', 'assignfee', 'change_expiration' ), true ) )
            $selected_tab = 'fees';
        $selected_tab = 'fees';

        echo '<div class="misc-pub-section">';

        echo '<ul class="listing-metabox-tabs">';
        foreach ( $tabs as $tab_id => $tab_label ) {
            echo '<li class="tabs ' . ( $selected_tab === $tab_id ? 'selected' : '' ) . '"><a href="#listing-metabox-' . $tab_id . '">' . $tab_label  .'</a></li>';
        }
        echo '</ul>';

        foreach ( array_keys( $tabs ) as $tab_id ) {
            echo '<div id="listing-metabox-' . $tab_id . '" class="listing-metabox-tab">';
            call_user_func( array( &$this, 'tab_' . $tab_id ) );
            echo '</div>';
        }

        echo '</div>';
        echo '<div class="clear"></div>';
    }

    private function tab_generalinfo() {
        $upgrades_api = wpbdp_listing_upgrades_api();

        echo '<strong>' . _x('General Info', 'admin infometabox', 'WPBDM') . '</strong>';        
        echo '<dl>';
            echo '<dt>' . _x( 'Access Key', 'admin infometabox', 'WPBDM' ) . '</dt>';
            echo '<dd><input type="text" value="' . esc_attr( $this->listing->get_access_key() ) . '" /></dd>';
            echo '<dt>'. _x('Total Listing Cost', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>' . wpbdp_currency_format( $this->listing->get_total_cost() ) . '</dd>';
            echo '<dt>'. _x('Payment Status', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>';
            echo sprintf('<span class="tag paymentstatus %1$s">%1$s</span>', $this->listing->get_payment_status() );
            echo '</dd>';
            echo '<dt>' . _x('Featured (Sticky) Status', 'admin infometabox', 'WPBDM') . '</dt>';
            echo '<dd>';

                // sticky information
                $sticky_info = $upgrades_api->get_info( $this->listing->get_id() );

                echo '<span><b>';
                if ($sticky_info->pending) {
                    echo _x('Pending Upgrade', 'admin metabox', 'WPBDM');
                } else {
                    echo esc_attr( $sticky_info->level->name );
                }
                echo '</b> </span><br />';

                if (current_user_can('administrator')) {
                    if ( $sticky_info->upgradeable ) {
                        echo sprintf('<span><a href="%s">%s</a></span>',
                                     esc_url( add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->upgrade->id, 'post' => $this->listing->get_id())) ),
                                     '<b>↑</b> ' . sprintf(__('Upgrade to %s', 'WPBDM'), esc_attr($sticky_info->upgrade->name)) );
                    }

                    if ( $sticky_info->downgradeable ) {
                        echo '<br />';
                        echo sprintf('<span><a href="%s">%s</a></span>',
                                     esc_url( add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->downgrade->id, 'post' => $this->listing->get_id())) ),
                                     '<b>↓</b> ' . sprintf(__('Downgrade to %s', 'WPBDM'), esc_attr($sticky_info->downgrade->name)) );                
                    }
                }

                $import_id = get_post_meta( $this->listing->get_id(), '_wpbdp[import_sequence_id]', true );
                if ( current_user_can( 'administrator' ) && $import_id ) {
                    echo '<dt>' . _x( 'CSV Import Sequence ID', 'admin infometabox', 'WPBDM' ) . '</dt>';
                    echo '<dd>' . $import_id . '</dd>';
                }

            echo '</dd>';
        do_action( 'wpbdp_admin_metabox_generalinfo_list', $this->listing->get_id() );
        echo '</dl>';

        if ( current_user_can( 'administrator' ) && 'ok' != $this->listing->get_payment_status() ) {
            echo sprintf( '<a href="%s" class="button-primary">%s</a> ',
                          esc_url( add_query_arg('wpbdmaction', 'setaspaid' ) ),
                          _x( 'Mark listing as Paid', 'admin infometabox', 'WPBDM' ) );
        }

        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-metabox-feesummary.tpl.php', array(
            'categories' => $this->listing->get_categories( 'all' ),
            'listing' => $this->listing
        ) );
    }

    private function tab_fees() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-metabox-fees.tpl.php', array(
                                'categories' => $this->listing->get_categories( 'all' ),
                                'listing' => $this->listing
                                ) );
    }

    private function tab_transactions() {
        echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/listing-metabox-transactions.tpl.php',
                                array( 'payments' => $this->listing->get_latest_payments() ) );
    }

    public static function metabox_callback( $post ) {
        $instance = new self( $post->ID );
        return $instance->render();
    }
}
