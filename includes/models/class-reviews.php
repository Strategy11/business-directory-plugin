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
		if ( ! empty( $name ) ) {
			$name = ' ' . $name;
		}

		$title = sprintf(
			/* translators: %s: User name, %2$d: number of entries */
			esc_html__( 'Congratulations%1$s! You have collected %2$d form submissions.', 'formidable' ),
			esc_html( $name ),
			absint( $entries )
		);

		$this->add_to_inbox( $title, $name, $asked );

		// We have a candidate! Output a review message.
		include( WPBDP_INC . 'views/shared/review.php' );
	}

	private function add_to_inbox( $title, $name, $asked ) {
		$message = new WPBDP_Inbox();
		$requests = $message->get_messages();
		$key      = $this->inbox_key . ( $asked ? $asked : '' );

		if ( isset( $requests[ $key ] ) ) {
			return;
		}

		// Remove previous requests.
		if ( $asked > 0 ) {
			$message->remove( $this->inbox_key );
		}
		if ( $asked > 1 ) {
			$message->remove( $this->inbox_key . '1' );
		}

		if ( $this->has_later_request( $requests, $asked ) ) {
			// Don't add a request that has already been passed.
			return;
		}

		$message->add_message(
			array(
				'key'     => $key,
				'message' => __( 'If you are enjoying Formidable, could you do me a BIG favor and give us a review to help me grow my little business and boost our motivation?', 'formidable' ) . '<br/>' .
					'- Steph Wells<br/>' .
					'<span>' . esc_html__( 'Co-Founder and CTO of Formidable Forms', 'formidable' ) . '<span>',
				'subject' => str_replace( $name, '', $title ),
				'cta'     => '<a href="https://wordpress.org/support/plugin/business-directory-plugin/reviews/?filter=5#new-post" class="wpbdp-dismiss-review-notice wpbdp-review-out button-secondary wpbdp-button-secondary" data-link="yes" target="_blank" rel="noopener noreferrer">' .
					esc_html__( 'Ok, you deserve it', 'formidable' ) . '</a>',
				'type'    => 'feedback',
			)
		);
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
	 * @since 4.05.02
	 */
	private function inbox_keys() {
		return array(
			$this->inbox_key,
			$this->inbox_key . '1',
			$this->inbox_key . '2',
		);
	}

	private function set_inbox_dismissed() {
		$message = new WPBDP_Inbox();
		foreach ( $this->inbox_keys() as $key ) {
			$message->dismiss( $key );
		}
	}

	private function set_inbox_read() {
		$message = new WPBDP_Inbox();
		foreach ( $this->inbox_keys() as $key ) {
			$message->mark_read( $key );
		}
	}

	/**
	 * Save the request to hide the review
	 */
	public function dismiss_review() {
		check_admin_referer( 'frm_ajax', 'nonce' );

		$user_id = get_current_user_id();
		$review  = get_user_meta( $user_id, $this->option_name, true );
		if ( empty( $review ) ) {
			$review = array();
		}

		if ( isset( $review['dismissed'] ) && $review['dismissed'] === 'done' ) {
			// if feedback was submitted, don't update it again when the review is dismissed
			$this->set_inbox_dismissed();
			wp_die();
		}

		$dismissed           = wpbdp_get_var( array( 'param' => 'link', 'default' => 'no' ), 'post' );
		$review['time']      = time();
		$review['dismissed'] = $dismissed === 'done' ? true : 'later';
		$review['asked']     = isset( $review['asked'] ) ? $review['asked'] + 1 : 1;

		update_user_meta( $user_id, $this->option_name, $review );
		$this->set_inbox_read();
		wp_die();
	}
}
