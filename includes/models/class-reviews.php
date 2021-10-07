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

	private function set_review_status() {
		$user_id = get_current_user_id();
		$review  = get_option( $this->option_name );
		$default = array(
			'time'      => time(),
			'dismissed' => false,
			'asked'     => 0,
		);

		if ( empty( $review ) ) {
			// Set the review request to show in a week
			update_option( $this->option_name, $default, 'no' );
		}

		$review              = array_merge( $default, (array) $review );
		$review['asked']     = (int) $review['asked'];
		$this->review_status = $review;
	}

	/**
	 * Add admin notices as needed for reviews
	 */
	public function review_request() {

		// Only show the review request to high-level users on business directory pages
		if ( ! current_user_can( 'administrator' ) || ! WPBDP_App_Helper::is_directory_admin() ) {
			return false;
		}

		$this->set_review_status();

		$dismissed = $this->review_status['dismissed'];
		if ( $dismissed === 'done' ) {
			return false;
		}

		if ( $dismissed === 'later' && $this->review_status['asked'] < 3 ) {
			$dismissed = false;
		}

		$week_ago = ( $this->review_status['time'] + WEEK_IN_SECONDS ) <= time();

		if ( empty( $dismissed ) && $week_ago ) {
			return $this->review();
		}
		return false;
	}


	/**
	 * Maybe show review request
	 */
	private function get_review() {

		// show the review request 3 times, depending on the number of entries
		$show_intervals = array( 50, 200, 500 );
		$asked          = $this->review_status['asked'];

		if ( ! isset( $show_intervals[ $asked ] ) ) {
			return false;
		}

		if ( $entries < 3 ) {
			return false;
		}

		$entries = WPBDP_Listing::count_listings();
		$user    = wp_get_current_user();
		if ( $entries <= 100 ) {
			// round to the nearest 10
			$entries = floor( $entries / 10 ) * 10;
		} else {
			// round to the nearest 50
			$entries = floor( $entries / 50 ) * 50;
		}
		$name = $user->first_name;
		if ( ! empty( $name ) ) {
			$name = ' ' . $name;
		}

		$title = sprintf(
			/* translators: %s: User name, %2$d: number of entries */
			esc_html__( 'Congratulations%1$s! You have collected %2$d form submissions.', 'business-directory-plugin' ),
			esc_html( $name ),
			absint( $entries )
		);

		return $this->get_message( $title, $name, $asked );
	}

	private function get_message( $title, $name, $asked ) {
		$message = str_replace( $name, '', $title );
		$message .= '<br />';
		$message .= __( 'If you are enjoying Business Directory Plugin, could you do me a BIG favor and give us a review to help me grow my little business and boost our motivation?', 'business-directory-plugin' );
		$message .= '- Steph Wells<br/>';
		$message .= '<span>' . esc_html__( 'Co-Founder and CTO of Business Directory Plugin', 'business-directory-plugin' ) . '<span>';
		$message .= '<a href="https://wordpress.org/support/plugin/business-directory-plugin/reviews/?filter=5#new-post" class="wpbdp-dismiss-review-notice wpbdp-review-out button-secondary wpbdp-button-secondary" data-link="yes" target="_blank" rel="noopener noreferrer">' .
		esc_html__( 'Ok, you deserve it', 'business-directory-plugin' ) . '</a>';
		return $message;
	}


	/**
	 * Save the request to hide the review
	 */
	public function dismiss_review() {
		$review  = get_option( $this->option_name, array() );
		if ( empty( $review ) ) {
			$review = array();
		}

		if ( isset( $review['dismissed'] ) && $review['dismissed'] === 'done' ) {
			// if feedback was submitted, don't update it again when the review is dismissed
			update_option( $this->option_name, $review, 'no' );
			wp_die();
		}

		$dismissed           = wpbdp_get_var( array( 'param' => 'link', 'default' => 'no' ), 'post' );
		$review['time']      = time();
		$review['dismissed'] = $dismissed === 'done' ? true : 'later';
		$review['asked']     = isset( $review['asked'] ) ? $review['asked'] + 1 : 1;

		update_option( $this->option_name, $review, 'no' );
		wp_die();
	}
}
