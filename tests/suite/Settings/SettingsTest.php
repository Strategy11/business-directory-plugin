<?php

namespace WPBDP\Tests\Plugin\Settings;

use Brain\Monkey\Functions;

use WPBDP\Tests\TestCase;

use WPBDP_Settings;

class SettingsTest extends TestCase {

	public function setup() {
		parent::setup();

		Functions\when( 'register_setting' )->justReturn( null );

		Functions\when( 'wp_parse_args' )->returnArg();
	}

	public function test_validate_term_permalink_does_not_accept_empty_values() {
		$group = array(
			'slug'  => 'anything',
			'title' => 'Whatever',
		);

		$setting = array(
			'id'        => 'permalinks-category-slug',
			'name'      => 'Category Slug',
			'type'      => 'text',
			'group'     => $group['slug'],
			'validator' => 'taxonomy_slug',
		);

		$newvalue = '';
		$oldvalue = 'wpbdp_category';

		Functions\expect( 'get_option' )
			->with( 'wpbdp_settings' )
			->andReturn(
				array(
					$setting['id'] => $oldvalue,
				)
			);

		$_POST['_wp_http_referer'] = 'something';

		Functions\when( 'sanitize_title' )->alias( 'urlencode' );
		Functions\when( 'add_settings_error' )->justReturn( null );

		$taxonomies = array(
			(object) array(
				'rewrite' => array( 'slug' => $oldvalue ),
			),
		);

		Functions\when( 'get_taxonomies' )->justReturn( $taxonomies );

		$settings = new WPBDP_Settings();
		$settings->register_group( $group['slug'], $group['title'] );
		$settings->register_setting( $setting );

		$value = $settings->validate_setting( $newvalue, $setting['id'] );

		$this->assertEquals( $oldvalue, $value );
	}

	public function test_validate_term_permalink_keeps_unicode_characters() {
		$group = array(
			'slug'  => 'anything',
			'title' => 'Whatever',
		);

		$setting = array(
			'id'        => 'permalinks-category-slug',
			'name'      => 'Category Slug',
			'type'      => 'text',
			'group'     => $group['slug'],
			'validator' => 'taxonomy_slug',
		);

		$newvalue = 'дейности';
		$oldvalue = 'wpbdp_category';

		Functions\expect( 'get_option' )
			->with( 'wpbdp_settings' )
			->andReturn(
				array(
					$setting['id'] => $oldvalue,
				)
			);

		$_POST['_wp_http_referer'] = 'something';

		Functions\when( 'sanitize_title' )->alias( 'urlencode' );

		$taxonomies = array(
			(object) array(
				'rewrite' => array( 'slug' => $oldvalue ),
			),
		);

		Functions\when( 'get_taxonomies' )->justReturn( $taxonomies );

		$settings = new WPBDP_Settings();
		$settings->register_group( $group['slug'], $group['title'] );
		$settings->register_setting( $setting );

		// Execution
		$value = $settings->validate_setting( $newvalue, $setting['id'] );

		// Verification
		$this->assertEquals( $newvalue, $value );
	}
}
