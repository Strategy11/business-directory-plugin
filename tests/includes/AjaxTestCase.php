<?php

/**
 * @group ajax
 */
class AjaxTestCase extends WP_Ajax_UnitTestCase {

	public static function wpSetUpBeforeClass( $factory ) {
		$_POST = array();
	}

	function set_as_user_role( $role ) {
		// create user
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		$user    = new WP_User( $user_id );
		$this->assertTrue( $user->exists(), 'Problem getting user ' . $user_id );

		// log in as user
		wp_set_current_user( $user_id );
		$this->$user_id = $user_id;
		$this->assertTrue( current_user_can( $role ) );
	}

	function trigger_action( $action ) {
		$response = '';
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieStopException $e ) {
			$response = $e->getMessage();
			unset( $e );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		if ( '' === $response ) {
			$response = $this->_last_response;
		}

		return $response;
	}
}
