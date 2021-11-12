<?php

namespace WPBDP\Tests;

class WPUnitTestCase extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	public function setUp(): void {
		parent::setUp();
		$this->after_setup();
	}

	public static function tearDownAfterClass(): void {
		global $wpdb;
		@$wpdb->check_connection();
		self::before_tear_down();
		parent::tearDownAfterClass();
	}

	/**
	 * Class called after test is set up
	 */
	protected function after_setup() {

	}

	/**
	 * Action called before test is cleaned and completed
	 */
	protected static function before_tear_down() {

	}
}
