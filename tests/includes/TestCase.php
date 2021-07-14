<?php

namespace WPBDP\Tests;

use PHPUnit;
use Brain\Monkey;

use function Patchwork\redefine;

class TestCase extends \PHPUnit\Framework\TestCase {

    private $patchwork = array();

	public static function wpSetUpBeforeClass( $factory ) {
		$_POST = array();
	}

	public function setup() {
        parent::setup();
        Monkey\setup();

        if ( ! defined( 'WPBDP_POST_TYPE' ) ) {
            define( 'WPBDP_POST_TYPE', 'wpbdp_listing' );
        }

        if ( ! defined( 'WPBDP_CATEGORY_TAX' ) ) {
            define( 'WPBDP_CATEGORY_TAX', 'wpbdp_category' );
        }
    }

    public function teardown() {
        array_map( 'Patchwork\restore', $this->patchwork );

        Monkey\teardown();
        parent::teardown();
    }

    protected function prepare_options( $options ) {
        $callback = function( $name, $default = null ) use ( $options ) {
            if ( isset( $options[ $name ] ) ) {
                return $options[ $name ];
            }

            return $default;
        };

        $this->redefine( 'wpbdp_get_option', $callback );
    }

    /**
     * Use it to redefine methods of the object under the test or static methods.
     *
     * To set expectations or control the behaviour of other methods/functions
     * use Brain\Monkey\Functions API.
     *
     * The same can be achieved creating a partial mock of the object under test,
     * but I find the following easier to write:
     *
     * `$this->redefine( 'ObjectUnderTest::some_method', function() { ... } );
     */
    protected function redefine( $callable, $callback ) {
        $this->patchwork[] = redefine( $callable, $callback );
    }

	/**
	 * Get a user by the specified role and set them as the current user
	 *
	 * @param string $role
	 *
	 * @return WP_User
	 */
	public function set_user_by_role( $role ) {
		$user = $this->get_user_by_role( $role );
		wp_set_current_user( $user->ID );

		$this->assertTrue( current_user_can( $role ), 'Failed setting the current user role' );

		return $user->ID;
	}

	/**
	 * Get a user of a specific role
	 *
	 * @param string $role
	 *
	 * @return WP_User
	 */
	public function get_user_by_role( $role ) {
		$users = get_users(
			array(
				'role' => $role,
				'number' => 1,
			)
		);
		if ( empty( $users ) ) {
			$this->fail( 'No users with this role currently exist.' );
			$user = null;
		} else {
			$user = reset( $users );
		}

		return $user;
	}

	protected function run_private_method( $method, $args = array() ) {
		$m = new \ReflectionMethod( $method[0], $method[1] );
		$m->setAccessible( true );
		return $m->invokeArgs( is_string( $method[0] ) ? null : $method[0], $args );
	}
}

