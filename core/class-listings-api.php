<?php

if ( ! class_exists( 'WPBDP_Listings_API' ) ) {

/**
 * @since 3.5.4
 */
class WPBDP_Listings_API {

    public function __construct() {
        add_filter( 'post_type_link', array( &$this, '_post_link' ), 10, 3 );
        add_filter( 'get_shortlink', array( &$this, '_short_link' ), 10, 4 );
        // add_filter('post_type_link', array($this, '_post_link_qtranslate'), 11, 2); // basic support for qTranslate
        add_filter('preview_post_link', array($this, '_preview_post_link'), 10, 2);

        add_filter('term_link', array($this, '_category_link'), 10, 3);
        add_filter('term_link', array($this, '_tag_link'), 10, 3);

        add_filter('comments_open', array($this, '_allow_comments'), 10, 2);

        add_action( 'WPBDP_Listing::listing_created', array( &$this, 'new_listing_admin_email' ) );
        add_action( 'WPBDP_Listing::listing_created', array( &$this, 'new_listing_confirmation_email' ) );
        add_action( 'wpbdp_edit_listing', array( &$this, 'edit_listing_admin_email' ) );

        add_action( 'WPBDP_Payment::status_change', array( &$this, 'setup_listing_after_payment' ) );
        add_action( 'WPBDP_Payment::status_change', array( &$this, 'auto_renewal_notification_email' ) );

        add_action( 'transition_post_status', array( &$this, 'listing_published_notification' ), 10, 3 );

        add_action( 'before_delete_post', array( &$this, 'after_listing_delete' ) );
        add_action( 'delete_term', array( &$this, 'handle_delete_term' ), 10, 3 );

        $this->upgrades = WPBDP_Listing_Upgrade_API::instance();
    }

    public function _category_link($link, $category, $taxonomy) {
        if ( WPBDP_CATEGORY_TAX != $taxonomy )
            return $link;

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) )
                return wpbdp_url( '/' ) . '&_' . wpbdp_get_option( 'permalinks-category-slug' ) . '=' . $category->slug;

            return $link;
        }

        $link = wpbdp_url( sprintf( '/%s/%s/', wpbdp_get_option( 'permalinks-category-slug' ), $category->slug ) );

        return apply_filters( 'wpbdp_category_link', $link, $category );
    }

    public function _tag_link($link, $tag, $taxonomy) {
        if ( WPBDP_TAGS_TAX != $taxonomy )
            return $link;

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) )
                $link = wpbdp_url( '/' ) . '&_' . wpbdp_get_option( 'permalinks-tags-slug' ) . '=' . $tag->slug;

            return $link;
        }

        $link = wpbdp_url( sprintf( '/%s/%s/', wpbdp_get_option( 'permalinks-tags-slug' ), $tag->slug ) );

        return $link;
    }

    public function _post_link( $link, $post = null, $leavename = false ) {
        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return $link;

        if ( $querystring = parse_url( $link, PHP_URL_QUERY ) ) 
            $querystring = '?' . $querystring;
        else
            $querystring = '';

        if ( ! wpbdp_rewrite_on() ) {
            if ( wpbdp_get_option( 'disable-cpt' ) ) {
                $link = wpbdp_url( '/' ) . '&' . '_' . wpbdp_get_option( 'permalinks-directory-slug' ) . '=' . $post->post_name;
            }
        } else {
            if ( $leavename )
                return wpbdp_url( '/' . '%' . WPBDP_POST_TYPE . '%' . '/' . $querystring );

            if ( wpbdp_get_option( 'permalinks-no-id' ) && $post->post_name )
                $link = wpbdp_url( '/' . $post->post_name . '/' );
            else
                $link = wpbdp_url( '/' . $post->ID . '/' . ( $post->post_name ? $post->post_name : '' ) );

            $link .= $querystring;
        }

        return apply_filters( 'wpbdp_listing_link', $link, $post->ID );
    }

    public function _short_link( $shortlink, $id = 0, $context = 'post', $allow_slugs = true ) {
        if ( 'post' !== $context || WPBDP_POST_TYPE != get_post_type( $id ) )
            return $shortlink;

        $post = get_post( $id );
        return $this->_post_link( $shortlink, $post );
    }

    public function _post_link_qtranslate( $url, $post ) {
        if ( is_admin() || !function_exists( 'qtrans_convertURL' ) )
            return $url;

        global $q_config;

        $lang = isset( $_GET['lang'] ) ? $_GET['lang'] : $q_config['language'];
        $default_lang = $q_config['default_language'];

        if ( $lang != $default_lang )
            return add_query_arg( 'lang', $lang, $url );

        return $url;
    }

    public function _preview_post_link( $url, $post = null ) {
        if ( is_null( $post ) && isset( $GLOBALS['post'] ) )
            $post = $GLOBALS['post'];

        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return $url ;

        if ( wpbdp_rewrite_on() )
            $url = remove_query_arg( array( 'post_type', 'p' ), $url );

        return $url;
    }

    public function _allow_comments($open, $post_id) {
        // comments on directory pages
        if ($post_id == wpbdp_get_page_id('main'))
            return false;

        // comments on listings
        if (get_post_type($post_id) == WPBDP_POST_TYPE)
            return wpbdp_get_option('show-comment-form');

        return $open;
    }

    /**
     * @since 3.4
     */
    public function setup_listing_after_payment( &$payment ) {
        $listing = WPBDP_Listing::get( $payment->get_listing_id() );

        // TODO: handle some rejected payments (i.e. downgrade listing if pending upgrade, etc.)

        if ( ! $listing || ! $payment->is_completed() )
            return;

        $is_renewal = false;

        foreach ( $payment->get_items() as $item ) {
            switch ( $item->item_type ) {
                case 'recurring_fee':
                    $listing->add_category( $item->rel_id_1, (object) $item->data, true, array( 'recurring_id' => $payment->get_data( 'recurring_id' ),
                                                                                                'payment_id' => $payment->get_id()  ) );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;
                case 'fee':
                    $listing->add_category( $item->rel_id_1, $item->rel_id_2, false );

                    if ( ! empty( $item->data['is_renewal'] ) )
                        $is_renewal = true;

                    break;

                case 'upgrade':
                    $upgrades_api = wpbdp_listing_upgrades_api();
                    $sticky_info = $upgrades_api->get_info( $listing->get_id() );

                    if ( $sticky_info->upgradeable )
                        $upgrades_api->set_sticky( $listing->get_id(), $sticky_info->upgrade->id, true );

                    break;
            }
        }

        $listing->save();

        if ( $is_renewal )
            $listing->set_post_status( 'publish' );
    }

    /**
     * @since 3.5.2
     */
    public function auto_renewal_notification_email( &$payment ) {
        if ( ! $payment->is_completed() || ! $payment->has_item_type( 'recurring_fee' ) )
            return;

        if ( ! $payment->get_data( 'parent_payment_id' ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $recurring_item = $payment->get_recurring_item();

        $replacements = array();
        $replacements['listing'] = sprintf( '<a href="%s">%s</a>',
                                            get_permalink( $payment->get_listing_id() ),
                                            get_the_title( $payment->get_listing_id() ) );
        $replacements['author'] = get_the_author_meta( 'display_name', get_post( $payment->get_listing_id() )->post_author );
        $replacements['category'] = wpbdp_get_term_name( $recurring_item->rel_id_1 );
        $replacements['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                                           strtotime( $payment->get_processed_on() ) );
        $replacements['site'] = sprintf( '<a href="%s">%s</a>',
                                         get_bloginfo( 'url' ),
                                         get_bloginfo( 'name' ) );

        $email = wpbdp_email_from_template( 'listing-autorenewal-message', $replacements );
        $email->to[] = wpbusdirman_get_the_business_email( $payment->get_listing_id() );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

    function listing_published_notification( $new_status, $old_status, $post ) {
        if ( ! in_array( 'listing-published', wpbdp_get_option( 'user-notifications' ), true ) )
            return;

        if ( WPBDP_POST_TYPE != get_post_type( $post ) )
            return;

        if ( $new_status == $old_status || 'publish' != $new_status || ( 'pending' != $old_status && 'draft' != $old_status ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = wpbdp_email_from_template( 'email-templates-listing-published', array(
            'listing' => get_the_title( $post->ID ),
            'listing-url' => get_permalink( $post->ID )
        ) );
        $email->to[] = wpbusdirman_get_the_business_email( $post->ID );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

    /**
     * Handles cleanup after a listing is deleted.
     * @since 3.4
     */
    public function after_listing_delete( $post_id ) {
        global $wpdb;

        if ( WPBDP_POST_TYPE != get_post_type( $post_id ) )
            return;

        // Remove attachments.
        $attachments = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $post_id, 'numberposts' => -1, 'fields' => 'ids' ) );
        foreach ( $attachments as $attachment_id )
            wp_delete_attachment( $attachment_id, true );

        // Remove listing fees.
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $post_id ) );

        // Remove payment information.
        $wpdb->query( $wpdb->prepare( "DELETE pi.* FROM {$wpdb->prefix}wpbdp_payments_items pi WHERE pi.payment_id IN (SELECT p.id FROM {$wpdb->prefix}wpbdp_payments p WHERE p.listing_id = %d)", $post_id ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_payments WHERE listing_id = %d", $post_id ) );
    }

    public function handle_delete_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        if ( WPBDP_CATEGORY_TAX != $taxonomy )
            return;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE category_id = %d", $term_id ) );
    }

    public function new_listing_confirmation_email( &$listing ) {
        if ( ! in_array( 'new-listing', wpbdp_get_option( 'user-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = wpbdp_email_from_template( 'email-confirmation-message', array(
            'listing' => $listing->get_title()
        ) );
        $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );
        $email->template = 'businessdirectory-email';
        $email->send();
    }

   public function new_listing_admin_email( &$listing ) {
        if ( ! in_array( 'new-listing', wpbdp_get_option( 'admin-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] New listing notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );

        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

        $email->body = wpbdp_render( 'email/listing-added', array( 'listing' => $listing ), false );
        $email->send();
    }

   public function edit_listing_admin_email( &$listing ) {
        if ( ! in_array( 'listing-edit', wpbdp_get_option( 'admin-notifications' ), true ) )
            return;

        global $wpbdp;
        if ( isset( $wpbdp->_importing_csv_no_email ) && $wpbdp->_importing_csv_no_email )
            return;

        $email = new WPBDP_Email();
        $email->subject = sprintf( _x( '[%s] Listing edit notification', 'notify email', 'WPBDM' ), get_bloginfo( 'name' ) );
        $email->to[] = get_bloginfo( 'admin_email' );

        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );

        $email->body = wpbdp_render( 'email/listing-edited', array( 'listing' => $listing ), false );

        $email->send();
    }

    public function get_thumbnail_id($listing_id) {
        if ( $thumbnail_id = get_post_meta($listing_id, '_wpbdp[thumbnail_id]', true ) ) {
            if ( false !== get_post_status( $thumbnail_id ) )
                return intval( $thumbnail_id );
        }

        if ( $images = $this->get_images( $listing_id ) ) {
            update_post_meta( $listing_id, '_wpbdp[thumbnail_id]', $images[0]->ID );
            return $images[0]->ID;
        }

        return 0;
    }

    /**
     * @since 3.4.1
     */
    public function calculate_sequence_id( $listing_id ) {
        $sequence_id = get_post_meta( $listing_id, '_wpbdp[import_sequence_id]', true );

        if ( ! $sequence_id ) {
            global $wpdb;

            $candidate = intval( $wpdb->get_var( $wpdb->prepare( "SELECT MAX(CAST(meta_value AS UNSIGNED INTEGER )) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                                                                 '_wpbdp[import_sequence_id]' ) ) );
            $candidate++;

            if ( false == add_post_meta( $listing_id, '_wpbdp[import_sequence_id]', $candidate, true ) )
                $sequence_id = 0;
            else
                $sequence_id = $candidate;
        }

        return $sequence_id;
    }

    public function get_images($listing_id) {
        $attachments = get_posts(array(
            'numberposts' => -1,
            'post_type' => 'attachment',
            'post_parent' => $listing_id
        ));

        $result = array();

        foreach ($attachments as $attachment) {
            if (wp_attachment_is_image($attachment->ID))
                $result[] = $attachment;
        }

        return $result;
    }

    public function get_listing_fees($listing_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d", $listing_id));
    }

    public function remove_category_info( $listing_id, $category_or_categories ) {
        global $wpdb;

        $categories = array_map( 'intval', is_array( $category_or_categories ) ? $category_or_categories : array( $category_or_categories ) );
        $current_terms = array_map( 'intval', wp_get_post_terms( $listing_id, WPBDP_CATEGORY_TAX, 'fields=ids' ) );
        $new_terms = array_diff( $current_terms, $categories );

        wp_set_post_terms( $listing_id, $new_terms, WPBDP_CATEGORY_TAX, false );

        foreach ( $categories as $cat_id ) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d",
                                          $listing_id,
                                          $cat_id ) );
        }

        return true;
    }

    public function get_expiration_time($listing_id, $fee) {
        if (is_array($fee)) return $this->get_expiration_time($listing_id, (object) $fee);

        if ($fee->days == 0)
            return null;

        $start_time = get_post_time('U', false, $listing_id);
        $expire_time = strtotime(sprintf('+%d days', $fee->days), $start_time);
        return $expire_time;
    }

    public function get_listing_fee_for_category($listing_id, $catid) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE listing_id = %d AND category_id = %d", $listing_id, $catid));

        if ($row != null) {
            // $fee = unserialize($row->fee);
            $fee['expires_on'] = $row->expires_on;
            $fee['renewal_id'] = $row->id;
            $fee['category_id'] = $row->category_id;
            return (object) $fee;
        }

        return null;
    }

    /*
     * Featured listings.
     */

    // TODO: deprecate (move to ListingUpgrades)
    public function get_sticky_status( $listing_id ) {
        $listing = WPBDP_Listing::get( $listing_id );

        if ( ! $listing )
            return 'normal';

        return $listing->get_sticky_status( false );
    }

    /**
     * Automatically renews a listing in all of its expired categories using the same fee as before (if possible) or the first one available.
     * @param int $listing_id the listing ID
     * @since 3.1
     */
    public function auto_renew( $listing_id ) {
        global $wpdb;

        $listing = WPBDP_Listing::get( $listing_id );
        $expired = $listing->get_categories( 'expired' );

        if ( !$expired )
            return;

        foreach ( $expired as &$e ) {
            $available_fees = wpbdp_get_fees_for_category( $e->term_id );
            $old_fee_id = $e->fee_id;
            $new_fee = null;

            foreach ( $available_fees as &$fee_option ) {
                if ( $fee_option->id == $old_fee_id ) {
                    $new_fee = $fee_option;
                    break;
                }
            }

            if ( !$new_fee )
                $new_fee = $available_fees[0];

            $listing->add_category( $e->term_id, $new_fee );
        }

        wp_update_post( array( 'ID' => $listing_id, 'post_status' => 'publish' ) );
    }

    public function renew_listing($renewal_id, $fee) {
        global $wpdb;

        if ( $renewal = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d AND expires_on IS NOT NULL", $renewal_id, current_time( 'mysql' ) ) ) ) {
            // set payment status to not-paid
//            update_post_meta( $renewal->listing_id, '_wpbdp[payment_status]', 'not-paid' );

            // register the new transaction
            $transaction_id = wpbdp_payments_api()->save_transaction( array(
                'listing_id' => $renewal->listing_id,
                'amount' => $fee->amount,
                'payment_type' => 'renewal',
                'extra_data' => array( 'renewal_id' => $renewal_id, 'fee' => $fee )
            ));

            return $transaction_id;
        }

        return 0;
    }

    // {{{ Quick search.

    private function get_quick_search_fields() {
        $fields = array();

        foreach ( wpbdp_get_option( 'quick-search-fields', array() ) as $field_id ) {
            if ( $field = WPBDP_FormField::get( $field_id ) )
                $fields[] = $field;
        }

        if ( ! $fields ) {
            // Use default fields.
            foreach( wpbdp_get_form_fields() as $field ) {
                if ( in_array( $field->get_association(), array( 'title', 'excerpt', 'content' ) ) )
                    $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Performs a "quick search" for listings on the fields marked as quick-search fields in the plugin settings page.
     * @uses WPBDP_ListingsAPI::get_quick_search_fields().
     * @param string $q The string used for searching.
     * @return array The listing IDs.
     * @since 3.4
     */
    public function quick_search( $q = '' ) {
        $q = trim( $q );

        if ( ! $q )
            return array();

        global $wpdb;

        $fields = $this->get_quick_search_fields();
        $query_pieces = array( 'where' => '',
                               'join' => '',
                               'orderby' => '',
                               'distinct' => '',
                               'fields' => "{$wpdb->posts}.ID",
                               'limits' => '' );
        $optimization = array( 'global' => array(), 'words' => array() );

        $words = wpbdp_get_option( 'quick-search-enable-performance-tricks' ) ? array( trim( $q ) ) : array_map( 'trim', explode( ' ', $q ) );

        $query_pieces['where'] .= '';

        foreach ( $words as $i => $w ) {
            $optimization['words'][ $i ] = array();

            $query_pieces['where'] .= ' AND ( 1=0 ';

            foreach ( $fields as &$f ) {
                $f->build_quick_search_query( $w, $query_pieces, $q, $i, $optimization );
            }

            $query_pieces['where'] .= ' )';
        }

//        wpbdp_debug_e( 'search', $query_pieces, $q, $optimization );

        $query_pieces = apply_filters( 'wpbdp_quick_search_query_pieces', $query_pieces );
        $query = sprintf( "SELECT %s %s FROM {$wpdb->posts} %s WHERE 1=1 AND ({$wpdb->posts}.post_type = '%s' AND {$wpdb->posts}.post_status = '%s') %s GROUP BY {$wpdb->posts}.ID %s %s",
                          $query_pieces['distinct'],
                          $query_pieces['fields'],
                          $query_pieces['join'],
                          WPBDP_POST_TYPE,
                          'publish',
                          $query_pieces['where'],
                          $query_pieces['orderby'],
                          $query_pieces['limits'] );

        return $wpdb->get_col( $query );
    }

    // }}}

    /* listings search */
    public function search($args) {
        global $wpdb;

        $args = stripslashes_deep( $args );
        $term = str_replace('*', '', trim(wpbdp_getv($args, 'q', '')));

        if (!$term && (!isset($args['fields']) || !$args['fields']) && (!isset($args['extra']) || !$args['extra']) )
            return array();

        $query = "SELECT DISTINCT ID FROM {$wpdb->posts}";
        $where = $wpdb->prepare("{$wpdb->posts}.post_type = %s AND {$wpdb->posts}.post_status = %s",
                                WPBDP_POST_TYPE, 'publish');

        if ($term) {
            // process term
            $where .= $wpdb->prepare(" AND ({$wpdb->posts}.post_title LIKE '%%%s%%' OR {$wpdb->posts}.post_content LIKE '%%%s%%' OR {$wpdb->posts}.post_excerpt LIKE '%%%s%%')", $term, $term, $term);
        }

        if (isset($args['fields'])) {
            foreach ($args['fields'] as $i => $meta_search) {

                if ( $field = wpbdp_get_formfield( $meta_search['field_id'] ) ) {
                    $q = is_array( $meta_search['q'] ) ? array_map( 'trim', $meta_search['q'] ) : trim( $meta_search['q'] );

                    if (!$q) continue;

                    switch ( $field->get_association() ) {
                        case 'title':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE '%%%s%%'", $q);
                            break;
                        case 'content':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_content LIKE '%%%s%%'", $q);
                            break;
                        case 'excerpt':
                            $where .= $wpdb->prepare(" AND {$wpdb->posts}.post_excerpt LIKE '%%%s%%'", $q);
                            break;
                        case 'category':
                            $term_ids = array_diff( is_array($q) ? $q : array($q), array('-1', '0') ) ;
                            $terms = array();

                            // $term_ids = implode(',',  array_diff($term_ids, array('-1', '0')) );

                            foreach ( $term_ids as $tid ) {
                                $terms[] = $tid;
                                $terms = array_merge( $terms, get_term_children( $tid, WPBDP_CATEGORY_TAX ) );
                            }

                            if ($terms) {
                                $query .= " LEFT JOIN {$wpdb->term_relationships} AS trel1 ON ({$wpdb->posts}.ID = trel1.object_id) LEFT JOIN {$wpdb->term_taxonomy} AS ttax1 ON (trel1.term_taxonomy_id = ttax1.term_taxonomy_id)";
                                $where .= " AND ttax1.term_id IN (" . implode( ',', $terms ) . ") ";
                            }

                            break;
                        case 'tags':
                            $terms = is_array($q) ? array_values($q) : explode(',', $q);
                            $term_ids = array();

                            foreach ($terms as $term_name) {
                                $term = null;

                                if ( $term_name === '-1' || $term_name === '0' )
                                    continue;

                                // if ( is_numeric( $term_name ) )
                                //     $term = get_term_by( 'id', $term_name, WPBDP_TAGS_TAX );

                                // if ( !$term )
                                if ( strpos( $term_name, '&' ) !== false )
                                    $term_name = htmlentities( $term_name, null, null, false );

                                $term = get_term_by( 'name', $term_name, WPBDP_TAGS_TAX );

                                if ( $term ) {
                                    $term_ids[] = $term->term_id;
                                } else {
                                    $where .= ' AND 1=0'; // force no results when a tag does not exist
                                }
                            }

                            if ($term_ids) {
                                $term_ids = implode(',', $term_ids);
                                $query .= " LEFT JOIN {$wpdb->term_relationships} AS trel2 ON ({$wpdb->posts}.ID = trel2.object_id) LEFT JOIN {$wpdb->term_taxonomy} AS ttax2 ON (trel2.term_taxonomy_id = ttax2.term_taxonomy_id)";
                                $where .= " AND ttax2.term_id IN ({$term_ids}) ";
                            }

                            break;
                        case 'meta':
                            // Multi-valued field.
                            if (in_array($field->get_field_type()->get_id(), array('checkbox', 'multiselect', 'select'))) {
                                $options = array_diff( is_array( $q ) ? $q : array( $q ), array( '-1' ) );
                                $options = array_map( 'preg_quote', $options );

                                if (!$options)
                                    continue;

                                $pattern = '(' . implode('|', $options) . '){1}([tab]{0,1})';

                                $query .= " INNER JOIN {$wpdb->postmeta} AS mt{$i}mv ON ({$wpdb->posts}.ID = mt{$i}mv.post_id)";
                                $where .= $wpdb->prepare(" AND (mt{$i}mv.meta_key = %s AND mt{$i}mv.meta_value REGEXP %s )",
                                                         "_wpbdp[fields][" . $field->get_id() . "]",
                                                         $pattern );
                            } elseif ( 'date' == $field->get_field_type_id() ) {
                                $field_type = $field->get_field_type();
                                $q = $field_type->date_to_storage_format( $field, $q );

                                if ( ! $q )
                                    continue;

                                $query .= sprintf(" INNER JOIN {$wpdb->postmeta} AS mt%1$1d ON ({$wpdb->posts}.ID = mt%1$1d.post_id)", $i);
                                $where .= $wpdb->prepare(" AND (mt{$i}.meta_key = %s AND mt{$i}.meta_value = %s)",
                                                         '_wpbdp[fields][' . $field->get_id() . ']',
                                                         $q);
                            } else { // Single-valued field.
                                if ( in_array( $field->get_field_type()->get_id(),
                                               array( 'textfield', 'textarea' ) ) ) {
                                    $query .= sprintf(" INNER JOIN {$wpdb->postmeta} AS mt%1$1d ON ({$wpdb->posts}.ID = mt%1$1d.post_id)", $i);
                                    $where .= $wpdb->prepare(" AND (mt{$i}.meta_key = %s AND mt{$i}.meta_value LIKE '%%%s%%')",
                                                             '_wpbdp[fields][' . $field->get_id() . ']',
                                                             $q);
                                } else {
                                    $query .= sprintf(" INNER JOIN {$wpdb->postmeta} AS mt%1$1d ON ({$wpdb->posts}.ID = mt%1$1d.post_id)", $i);
                                    $where .= $wpdb->prepare(" AND (mt{$i}.meta_key = %s AND mt{$i}.meta_value = %s)",
                                                             '_wpbdp[fields][' . $field->get_id() . ']',
                                                             $q);
                                }
                            }

                            break;
                        default:
                            break;
                    }
                }

            }
        }

        $query .= ' WHERE ' . apply_filters('wpbdp_search_where', $where, $args);
        $query = apply_filters( 'wpbdp_search_query ', $query, $args );

        return $wpdb->get_col($query);
    }

    public function send_renewal_email( $renewal_id, $email_message_type = 'auto' ) {
        global $wpdb;

        $renewal_id = intval( $renewal_id );
        $fee_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $renewal_id ) );

        if ( !$fee_info || !$fee_info->expires_on )
            return false;


        $message_option = '';

        if ( $email_message_type == 'auto' ) {
            $expiration = strtotime( $fee_info->expires_on );
            $current_time = time();

            if ( $expiration > $current_time ) {
                $message_option = 'renewal-pending-message';
            } else {
                $message_option = 'listing-renewal-message';
            }
        } elseif ( $email_message_type ) {
            $message_option = $email_message_type;
        } else {
            $message_option = 'listing-renewal-message';
        }

        $listing = WPBDP_Listing::get( $fee_info->listing_id );

        $renewal_url = $listing->get_renewal_url( $fee_info->category_id );
        $message_replacements = array( 'site' => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
                                       'listing' => esc_attr( get_the_title( $fee_info->listing_id ) ),
                                       'category' => get_term( $fee_info->category_id, WPBDP_CATEGORY_TAX )->name,
                                       'expiration' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $fee_info->expires_on ) ),
                                       'link' => sprintf( '<a href="%1$s">%1$s</a>', $renewal_url )
                                     );

        $email = wpbdp_email_from_template( $message_option, $message_replacements );
        $email->to[] = wpbusdirman_get_the_business_email( $fee_info->listing_id );
        $email->template = 'businessdirectory-email';
        $email->send();

        return true;
    }


    /**
     * Notifies listings expiring soon. Despite its name this function also changes listings according to expiration rules (removing categories, unpublishing, etc.).
     * @param int $threshold A threshold (in days) to use for checking listing expiration times: 0 means already expired listings and a positive
     *                       value checks listings expiring between now and now + $threshold.
     * @param int $now Timestamp to use as current time. Defaults to the value of `current_time( 'timestamp' )`.
     * @since 3.1
     */
    public function notify_expiring_listings( $threshold=0, $now=null ) {
        global $wpdb;

        $threshold = intval( $threshold );
        $now = $now > 0 ? intval( $now ) : current_time( 'timestamp' );

        $query = '';
        $now_date = wpbdp_format_time( $now, 'mysql' );

        if ( $threshold == 0 ) {
            $this->notify_expired_listings_recurring( $now );

            $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE recurring = %d AND expires_on IS NOT NULL AND expires_on < %s AND email_sent <> %d AND email_sent <> %d ORDER BY expires_on LIMIT 100",
                                     0,
                                     $now_date,
                                     2,
                                     3 );
        } else {
            if ( $threshold > 0 ) {
                $end_date = wpbdp_format_time( strtotime( sprintf( '+%d days', $threshold ), $now ), 'mysql' );

                if ( wpbdp_get_option( 'send-autorenewal-expiration-notice' ) ) {
                    $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE expires_on IS NOT NULL AND expires_on >= %s AND expires_on <= %s AND email_sent = %d ORDER BY expires_on LIMIT 100",
                                             $now_date,
                                             $end_date,
                                             0 );
                } else {
                    $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE recurring = %d AND expires_on IS NOT NULL AND expires_on >= %s AND expires_on <= %s AND email_sent = %d ORDER BY expires_on LIMIT 100",
                                             0,
                                             $now_date,
                                             $end_date,
                                             0 );
                 }
            } else {
                $exp_date = wpbdp_format_time( strtotime( sprintf( '%d days', $threshold ), $now ), 'mysql' );

                $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE recurring = %d AND expires_on IS NOT NULL AND expires_on < %s AND email_sent = %d",
                                         0,
                                         $exp_date,
                                         2 );
            }
        }

        $rs = $wpdb->get_results( $query );

        if ( !$rs )
            return;

        foreach ( $rs as &$r ) {
            $listing = WPBDP_Listing::get( $r->listing_id );

            if ( ! $listing || ! term_exists( absint( $r->category_id ), WPBDP_CATEGORY_TAX ) ) {
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpbdp_listing_fees WHERE id = %d", $r->id ) );
                continue;
            }

            $base_replacements = array( 'site' => sprintf( '<a href="%s">%s</a>', get_bloginfo( 'url' ), get_bloginfo( 'name' ) ),
                                        'author' => get_the_author_meta( 'display_name', get_post( $r->listing_id )->post_author ),
                                        'listing' => sprintf( '<a href="%s">%s</a>', $listing->get_permalink(), esc_attr( $listing->get_title() ) ),
                                        'category' => wpbdp_get_term_name( $r->category_id ),
                                        'expiration' => date_i18n( get_option( 'date_format' ), strtotime( $r->expires_on ) )
            );

            if ( ! $r->recurring ) {
                $renewal_url = $listing->get_renewal_url( $r->category_id );
                $message_replacements = array_merge( $base_replacements, array(
                    'link' => sprintf( '<a href="%1$s">%1$s</a>', $renewal_url )
                ) );
            } else {
                $message_replacements = array_merge( $base_replacements, array(
                    'date' => $base_replacements['expiration'],
                    'link' => sprintf( '<a href="%1$s">%1$s</a>', esc_url( wpbdp_url( 'manage_recurring' ) ) )
                ) );
            }

            if ( 0 == $threshold ) {
                // handle expired listings

                // remove expired category from post
                $listing->remove_category( $r->category_id, false );

                if ( ! $listing->get_categories( 'current' ) ) {
                    // $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d", wpbdp_get_option( 'deleted-status' ), $r->listing_id ) );
                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = %s WHERE ID = %d", 'draft', $listing->get_id() ) );
                }

                if ( wpbdp_get_option( 'listing-renewal' ) ) {
                    $email = wpbdp_email_from_template( 'listing-renewal-message', $message_replacements );
                    $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );

                    if ( in_array( 'renewal', wpbdp_get_option( 'admin-notifications' ), true ) ) {
                        $email->cc[] = get_option( 'admin_email' );

                        if ( wpbdp_get_option( 'admin-notifications-cc' ) )
                            $email->cc[] = wpbdp_get_option( 'admin-notifications-cc' );
                    }

                    $email->template = 'businessdirectory-email';
                    $email->send();
                }

                $wpdb->update( "{$wpdb->prefix}wpbdp_listing_fees", array( 'email_sent' => 2 ), array( 'id' => $r->id ) );
            } elseif ( $threshold > 0 ) {
                // notify about coming expirations
                $email = wpbdp_email_from_template( ( $r->recurring ? 'listing-autorenewal-notice' : 'renewal-pending-message' ),
                                                    $message_replacements );
                $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );
                $email->template = 'businessdirectory-email';
                $email->send();

                $wpdb->update( "{$wpdb->prefix}wpbdp_listing_fees", array( 'email_sent' => 1 ), array( 'id' => $r->id ) );
            } elseif ( $threshold < 0 ) {
                // remind about expired listings
                $email = wpbdp_email_from_template( 'renewal-reminder-message', $message_replacements );
                $email->to[] = wpbusdirman_get_the_business_email( $listing->get_id() );
                $email->template = 'businessdirectory-email';
                $email->send();

                $wpdb->update( "{$wpdb->prefix}wpbdp_listing_fees", array( 'email_sent' => 3 ), array( 'id' => $r->id ) );
            }
        }

    }

    private function notify_expired_listings_recurring( $now ) {
        global $wpdb, $wpbdp;

        $now_date = wpbdp_format_time( $now, 'mysql' );

        $query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpbdp_listing_fees WHERE recurring = %d AND expires_on IS NOT NULL AND expires_on < %s ORDER BY expires_on LIMIT 100",
                1,
                $now_date );
        $rs = $wpdb->get_results( $query );

        foreach ( $rs as $r ) {
            $recurring_id = $r->recurring_id;
            $data = unserialize( $r->recurring_data );

            if ( ! isset( $data['payment_id'] ) )
                continue;

            $wpbdp->payments->process_recurring_expiration( $data['payment_id'] );
        }
    }



}

/*
 * For compatibility with other APIs (< 3.5.4).
 */
class WPBDP_ListingsAPI extends WPBDP_Listings_API {}

}

