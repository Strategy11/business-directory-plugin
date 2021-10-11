<?php
/**
 * @since x.x
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPBDP_Reviews {

	private $option_name = 'wpbdp_reviewed';

	private $review_status = array();

	private $inbox_key = 'review';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add admin notices as needed for reviews
	 */
	public function review_request() {

		// Only show the review request to high-level users on business directory pages
		if ( ! current_user_can( 'administrator' ) || ! WPBDP_App_Helper::is_directory_admin() ) {
			return;
		}

		// Verify that we can do a check for reviews
		$this->set_review_status();

		// Check if it has been dismissed or if we can ask later
		$dismissed = $this->review_status['dismissed'];
		if ( $dismissed === 'later' && $this->review_status['asked'] < 3 ) {
			$dismissed = false;
		}

		$week_ago = ( $this->review_status['time'] + WEEK_IN_SECONDS ) <= time();

		if ( empty( $dismissed ) && $week_ago ) {
			$this->review();
		}
	}

	/**
	 * When was the review request last dismissed?
	 */
	private function set_review_status() {
		$user_id = get_current_user_id();
		$review  = get_user_meta( $user_id, $this->option_name, true );
		$default = array(
			'time'      => time(),
			'dismissed' => false,
			'asked'     => 0,
		);

		if ( empty( $review ) ) {
			// Set the review request to show in a week
			update_user_meta( $user_id, $this->option_name, $default );
		}

		$review              = array_merge( $default, (array) $review );
		$review['asked']     = (int) $review['asked'];
		$this->review_status = $review;
	}

	/**
	 * Maybe show review request
	 */
	private function review() {

		// show the review request 3 times, depending on the number of entries
		$show_intervals = array( 50, 200, 500 );
		$asked          = $this->review_status['asked'];

		if ( ! isset( $show_intervals[ $asked ] ) ) {
			return;
		}

		$entries = WPBDP_Listing::count_listings();
		$count   = $show_intervals[ $asked ];
		$user    = wp_get_current_user();

		// Only show review request if the site has collected enough entries
		if ( $entries < $count ) {
			// check the entry count again in a week
			$this->review_status['time'] = time();
			update_user_meta( $user->ID, $this->option_name, $this->review_status );

			return;
		}

		if ( $entries <= 100 ) {
			// round to the nearest 10
			$entries = floor( $entries / 10 ) * 10;
		} else {
			// round to the nearest 50
			$entries = floor( $entries / 50 ) * 50;
		}
		$name = $user->first_name;

		$title = sprintf(
			/* translators: %s: User name, %2$d: number of entries */
			esc_html__( 'Congratulations %1$s! You have collected %2$d listings.', 'business-directory-plugin' ),
			esc_html( $name ),
			absint( $entries )
		);

		include WPBDP_PATH . 'includes/admin/views/review.php';
	}


	/**
	 * If there are already later requests, don't add it to the inbox again.
	 *
	 * @since 4.05.02
	 */
	private function has_later_request( $requests, $asked ) {
		return isset( $requests[ $this->inbox_key . ( $asked + 1 ) ] ) || isset( $requests[ $this->inbox_key . ( $asked + 2 ) ] );
	}

	/**
	 * Save the request to hide the review
	 */
	public function dismiss_review() {
		check_admin_referer( 'wpbdp_dismiss_review' );

		$review = get_user_meta( $user_id, $this->option_name, true );
		if ( empty( $review ) ) {
			$review = array();
		}

		if ( isset( $review['dismissed'] ) && $review['dismissed'] === 'done' ) {
			// if feedback was submitted, don't update it again when the review is dismissed
			update_user_meta( $user_id, $this->option_name, $review );
			wp_die();
		}

		$dismissed           = wpbdp_get_var(
			array(
				'param'   => 'link',
				'default' => 'no',
			),
			'post'
		);
		$review['time']      = time();
		$review['dismissed'] = $dismissed === 'done' ? true : 'later';
		$review['asked']     = isset( $review['asked'] ) ? $review['asked'] + 1 : 1;

		update_user_meta( $user_id, $this->option_name, $review );
		wp_die();
	}
}
