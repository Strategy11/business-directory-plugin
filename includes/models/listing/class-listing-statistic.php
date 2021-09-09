<?php
/**
 *
 * @subpackage  Listing Statistic
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Listing statistic model
 * Handles database operations of the listing statistics
 */
class WPBDP_Listing_Statistic {

    /**
     * Save or update a listing view
     * 
     * @param int $listing_id - the listing it
     * @param int $page_id - the page id
     * @param string $ip - the user ip
     */
    public function save_listing_view( $listing_id, $page_id, $ip ) {
        global $wpdb;
        if ( ! defined( 'WPBDP_LISTING_VIEW_ENABLE_TRACK_IP' ) || ( defined( 'WPBDP_LISTING_VIEW_ENABLE_TRACK_IP' ) && ! WPBDP_LISTING_VIEW_ENABLE_TRACK_IP ) ) {
			$ip = null;
		}
        if ( ! is_null( $ip ) ) {
			$ip_query = ' AND `ip` = %s';
		} else {
			$ip_query = ' AND `ip` IS NULL';
		}

        $sql = "SELECT `id` FROM {$this->get_table_name()} WHERE `listing_id` = %d AND `page_id` = %d {$ip_query} AND `date_created` BETWEEN DATE_SUB(utc_timestamp(), INTERVAL 1 DAY) AND utc_timestamp()";

        if ( ! is_null( $ip ) ) {
			$prepared_sql = $wpdb->prepare( $sql, $listing_id, $page_id, $ip ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$prepared_sql = $wpdb->prepare( $sql, $listing_id, $page_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

        $id = $wpdb->get_var( $prepared_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $id ) {
			$this->_update( $id, $wpdb );
		} else {
			$this->_save( $listing_id, $page_id, $ip, $wpdb );
		}
    }

	/**
	 * Count views
	 *
	 * @param int $listing_id - the listing id
	 * @param string $starting_date - the start date (dd-mm-yyy)
	 * @param string $ending_date - the end date (dd-mm-yyy)
	 *
	 * @return int - totol views based on parameters
	 */
	public function count_views( $listing_id, $starting_date = null, $ending_date = null ) {
		return $this->_count( $listing_id, $starting_date, $ending_date );
	}

	/**
	 * Delete stats by listing id
     * 
	 * @param int $listing_id - the form id
	 */
	public function delete_by_listing_id( $listing_id ) {
		global $wpdb;
		$sql = "DELETE FROM {$this->get_table_name()} WHERE `listing_id` = %d";
		$wpdb->query( $wpdb->prepare( $sql, $listing_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}


    /**
	 * Return statistics table name
	 *
	 * @return string
	 */
	public function get_table_name() {
        global $wpdb;
        $table_name = $wpdb->prefix."wpbdp_statistics";
		return $table_name;
	}

    /**
	 * Save Data to database
	 *
	 * @param int $listing_id - the listing id
	 * @param int $page_id - the page id
	 * @param string $ip - the user ip
	 * @param bool|object $db - the wp db object
	 */
	private function _save( $listing_id, $page_id, $ip, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}

		$db->insert(
			$this->get_table_name(),
			array(
				'listing_id'   => $listing_id,
				'page_id'      => $page_id,
				'ip'           => $ip,
				'date_created' => date_i18n( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Update views
     * 
	 * @param int $id - stat id
	 * @param bool|object $db - the wp db object
	 *
	 */
	private function _update( $id, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$db->query( $db->prepare( "UPDATE {$this->get_table_name()} SET `views` = `views`+1, `date_updated` = now() WHERE `id` = %d", $id ) );
	}

    /**
	 * Count data
	 *
	 * @param int $listing_id - the listing id
	 * @param string $starting_date - the start date (dd-mm-yyy)
	 * @param string $ending_date - the end date (dd-mm-yyy)
	 *
	 * @return int - totol counts based on parameters
	 */
	private function _count( $listing_id, $starting_date = null, $ending_date = null ) {
		global $wpdb;
		$date_query = $this->_generate_date_query( $wpdb, $starting_date, $ending_date );
		$sql        = "SELECT SUM(`views`) FROM {$this->get_table_name()} WHERE `listing_id` = %d $date_query";
		$counts     = $wpdb->get_var( $wpdb->prepare( $sql, $listing_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $counts ) {
			return $counts;
		}

		return 0;
	}


    /**
	 * Generate the date query
	 *
	 * @param object $wpdb - the WordPress database object
	 * @param string $starting_date - the start date (dd-mm-yyy)
	 * @param string $ending_date - the end date (dd-mm-yyy)
	 *
	 * @return string $date_query
	 */
	private function _generate_date_query( $wpdb, $starting_date = null, $ending_date = null, $prefix = '', $clause = 'AND' ) {
		$date_query  = '';
		$date_format = '%d-%m-%Y';
		if ( ! is_null( $starting_date ) && ! is_null( $ending_date ) && ! empty( $starting_date ) && ! empty( $ending_date ) ) {
			$date_query = $wpdb->prepare( "$clause DATE_FORMAT($prefix`date_created`, '$date_format') >= %s AND DATE_FORMAT($prefix`date_created`, '$date_format') <= %s", $starting_date, $ending_date ); // phpcs:ignore
		} else {
			if ( ! is_null( $starting_date ) && ! empty( $starting_date ) ) {
				$date_query = $wpdb->prepare( "$clause DATE_FORMAT($prefix`date_created`, '$date_format') >= %s", $starting_date ); // phpcs:ignore
			} elseif ( ! is_null( $ending_date ) && ! empty( $ending_date ) ) {
				$date_query = $wpdb->prepare( "$clause DATE_FORMAT($prefix`date_created`, '$date_format') <= %s", $starting_date ); // phpcs:ignore
			}
		}

		return $date_query;
	}
}
