<?php

class WPBDP_Admin_Listings {

    function __construct() {
        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_columns', array( &$this, 'add_columns' ) );
        add_action( 'manage_' . WPBDP_POST_TYPE . '_posts_custom_column', array( &$this, 'listing_column' ), 10, 2 );

        add_filter( 'views_edit-' . WPBDP_POST_TYPE, array( &$this, 'listing_views' ) );
        add_filter( 'posts_clauses', array( &$this, 'listings_admin_filters' ) );
    }

    // {{{ Custom columns.

    function add_columns( $columns_ ) {
        $custom_columns = array();
        $custom_columns['category'] = _x( 'Categories', 'admin', 'WPBDM' );
        $custom_columns['payment_status'] = __( 'Payment Status', 'WPBDM' );
        $custom_columns['sticky_status'] = __( 'Featured (Sticky) Status', 'WPBDM' );

        $columns = array();

        foreach ( $columns_ as $k => $v ) {
            $columns[ $k ] = $v;

            if ( 'title' == $k )
                $columns = array_merge( $columns, $custom_columns );
        }

        return $columns;
    }


    function listing_column( $column, $post_id ) {
        if ( ! method_exists( $this, 'listing_column_' . $column ) )
            return;

        call_user_func( array( &$this, 'listing_column_' . $column ), $post_id );
    }

    function listing_column_category( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $categories = $listing->get_categories( 'all' );

        $i = 0;
        foreach ( $categories as &$category ) {
            print $category->expired ? '<s>' : '';
            printf( '<a href="%s" title="%s">%s</a>',
                    get_term_link( $category->id, WPBDP_CATEGORY_TAX ),
                    $category->expired ? _x( '(Listing expired in this category)', 'admin', 'WPBDM' ) : '',
                    $category->name );
            print $category->expired ? '</s>' : '';
            print ( ( $i + 1 ) != count( $categories ) ? ', ' : '' );

            $i++;
        }
    }

    function listing_column_payment_status( $post_id ) {
        $listing = WPBDP_Listing::get( $post_id );
        $paid_status = $listing->get_payment_status();

        $status_links = '';

        if ( $paid_status != 'ok' )
            $status_links .= sprintf('<span><a href="%s">%s</a></span>',
                                    add_query_arg( array( 'wpbdmaction' => 'setaspaid', 'post' => $post_id ) ),
                                    __('Paid', 'WPBDM'));

        printf( '<span class="tag paymentstatus %s">%s</span>', $paid_status, strtoupper( $paid_status ) );

        if ( $status_links && current_user_can( 'administrator' ) )
            printf( '<div class="row-actions"><b>%s:</b> %s</div>', __( 'Mark as', 'WPBDM' ), $status_links );
    }

    function listing_column_sticky_status( $post_id ) {
        $upgrades_api = wpbdp_listing_upgrades_api();
        $sticky_info = $upgrades_api->get_info( $post_id );

        echo sprintf('<span class="tag status %s">%s</span><br />',
                    str_replace(' ', '', $sticky_info->status),
                    $sticky_info->pending ? __('Pending Upgrade', 'WPBDM') : esc_attr($sticky_info->level->name) );

        echo '<div class="row-actions">';

        if ( current_user_can('administrator') ) {
            if ( $sticky_info->upgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->upgrade->id, 'post' => $post_id)),
                             '<b>↑</b> ' . sprintf(__('Upgrade to %s', 'WPBDM'), esc_attr($sticky_info->upgrade->name)) );
                echo '<br />';
            }

            if ( $sticky_info->downgradeable ) {
                echo sprintf('<span><a href="%s">%s</a></span>',
                             add_query_arg(array('wpbdmaction' => 'changesticky', 'u' => $sticky_info->downgrade->id, 'post' => $post_id)),
                             '<b>↓</b> ' . sprintf(__('Downgrade to %s', 'WPBDM'), esc_attr($sticky_info->downgrade->name)) );                
            }
        } elseif ( current_user_can('contributor') && wpbdp_user_can( 'upgrade-to-sticky', $post_id ) ) {
                echo sprintf('<span><a href="%s"><b>↑</b> %s</a></span>', wpbdp_get_page_link('upgradetostickylisting', $post_id), _x('Upgrade to Featured', 'admin actions', 'WPBDM'));            
        }

        echo '</div>';

    }

    // }}}

    // {{{ List views.
    
    function listing_views( $views ) {
        global $wpdb;

        if ( ! current_user_can( 'administrator' ) ) {
            if ( current_user_can( 'contributor' ) && isset( $views['mine'] ) )
                return array( $views['mine'] );

            return array();
        }

        $post_statuses = '\'' . join('\',\'', isset($_GET['post_status']) ? array($_GET['post_status']) : array('publish', 'draft', 'pending')) . '\'';

        $paid = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type = %s AND p.post_status IN ({$post_statuses})
            AND NOT EXISTS ( SELECT 1 FROM {$wpdb->prefix}wpbdp_payments ps WHERE ps.listing_id = p.ID AND ps.status = %s )",
            WPBDP_POST_TYPE,
            'pending'
        ) );

        $unpaid = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->prefix}wpbdp_payments ps ON p.ID = ps.listing_id
             WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ps.status = %s",
             WPBDP_POST_TYPE,
             'pending'
        ) );
        $pending_upgrade = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id)
                                                           WHERE p.post_type = %s AND p.post_status IN ({$post_statuses}) AND ( (pm.meta_key = %s AND pm.meta_value = %s) )",
                                                           WPBDP_POST_TYPE,
                                                           '_wpbdp[sticky]',
                                                           'pending') );
        $expired = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->prefix}wpbdp_listing_fees lf ON lf.listing_id = p.ID WHERE lf.expires_on < %s",
                                                   current_time( 'mysql' ) ) );

        $views['paid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                 add_query_arg('wpbdmfilter', 'paid', remove_query_arg('post')),
                                 wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'paid' ? 'current' : '',
                                 __('Paid', 'WPBDM'),
                                 number_format_i18n($paid));
        $views['unpaid'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                   add_query_arg('wpbdmfilter', 'unpaid', remove_query_arg('post')),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'unpaid' ? 'current' : '',
                                   __('Unpaid', 'WPBDM'),
                                   number_format_i18n($unpaid));
        $views['featured'] = sprintf('<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                   add_query_arg('wpbdmfilter', 'pendingupgrade', remove_query_arg('post')),
                                   wpbdp_getv($_REQUEST, 'wpbdmfilter') == 'pendingupgrade' ? 'current' : '',
                                   __('Pending Upgrade', 'WPBDM'),
                                   number_format_i18n($pending_upgrade));
        $views['expired'] = sprintf( '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                                     add_query_arg( 'wpbdmfilter', 'expired', remove_query_arg( 'post' ) ),
                                     wpbdp_getv( $_REQUEST, 'wpbdmfilter' ) == 'expired' ? 'current' : '' ,
                                     _x( 'Expired', 'admin', 'WPBDM' ),
                                     number_format_i18n( $expired )
                                    );
        return $views;
    }

    function listings_admin_filters( $pieces  ) {
        global $current_screen;
        global $wpdb;

        if ( ! is_admin() || ! isset( $_REQUEST['wpbdmfilter'] ) ||  'edit-' . WPBDP_POST_TYPE !=  $current_screen->id )
            return $pieces;

        switch ( $_REQUEST['wpbdmfilter'] ) {
            case 'expired':
                $pieces['join'] = " LEFT JOIN {$wpdb->prefix}wpbdp_listing_fees ON {$wpdb->prefix}wpbdp_listing_fees.listing_id = {$wpdb->posts}.ID ";
                $pieces['where'] = $wpdb->prepare( " AND {$wpdb->prefix}wpbdp_listing_fees.expires_on IS NOT NULL AND {$wpdb->prefix}wpbdp_listing_fees.expires_on < %s ", current_time( 'mysql' ) );
                $pieces['groupby'] = " {$wpdb->posts}.ID ";
                break;
            case 'pendingupgrade':
                $pieces['join'] = " LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = {$wpdb->posts}.ID ";
                $pieces['where'] = $wpdb->prepare( " AND pm.meta_key = %s AND pm.meta_value = %s ", '_wpbdp[sticky]', 'pending' );
                break;
            case 'paid':
                $pieces['where'] .= $wpdb->prepare( " AND NOT EXISTS ( SELECT 1 FROM {$wpdb->prefix}wpbdp_payments WHERE {$wpdb->posts}.ID = {$wpdb->prefix}wpbdp_payments.listing_id AND ( {$wpdb->prefix}wpbdp_payments.status IS NULL OR {$wpdb->prefix}wpbdp_payments.status != %s ) )", 'pending' );
                break;
            case 'unpaid':
                $pieces['join'] .= " LEFT JOIN {$wpdb->prefix}wpbdp_payments ON {$wpdb->posts}.ID = {$wpdb->prefix}wpbdp_payments.listing_id ";
                $pieces['where'] .= $wpdb->prepare( " AND {$wpdb->prefix}wpbdp_payments.status = %s ", 'pending' );
                $pieces['groupby'] .= " {$wpdb->posts}.ID ";
                break;
            default:
                break;
        }

        return $pieces;
    }

    // }}}

}
