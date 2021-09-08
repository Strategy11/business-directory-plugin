<?php
/**
 * FontAwesome handling class.
 *
 * @package BDP/Includes/Helpers/Class FontAwesome.
 */

/**
 * FontAwesome API class
 *
 * @since X.X
 */
class WPBDP_FontAwesome {

	/**
	 * The API endpoint to get information about the icons
	 * This is the free api endpoint
	 * 
	 * @since X.X
	 * 
	 */
	private $api_endpoint = 'http://cdn.jsdelivr.net/gh/mattkeys/FontAwesome-Free-Manifest/5.x/manifest.yml';

	/**
	 * Get icons on load
	 */
	public function __construct() {
		$this->get_icons();
	}

	/**
	 * Load the icons from the api endpoint
	 */
	public function get_icons() {
		$fa_icons = get_option( 'wpbdp_font_awesome_icons', array() );

		if ( empty( $fa_icons ) ) {
			$request	= wp_remote_get( $this->api_endpoint );
			if ( ! is_wp_error( $request ) && isset( $request['response']['code'] ) && '200' == $request['response']['code'] ) {
				$response = wp_remote_retrieve_body( $request );
				if ( ! empty( $response ) ) {
					require_once WPBDP_PATH . 'vendors/spyc/spyc.php';
					$parsed_icons = spyc_load( $response );

					if ( is_array( $parsed_icons ) && ! empty( $parsed_icons ) ) {
						$icons = $this->find_icons( $parsed_icons );

						if ( ! empty( $icons['details'] ) ) {
							$fa_icons = $icons;

							// Do not autoload icons
							update_option( 'wpbdp_font_awesome_icons', $fa_icons, false );
						}
					}
				}
			}
		}
		return $fa_icons;
	}

	/**
	 * Search icon set
	 * Used in autocomplete
	 * 
	 * @param string $query
	 * 
	 * @return array
	 */
	public function search_icons( $query ) {
		$results = array();
		$fa_icons = $this->get_icons();
		foreach ( $fa_icons['list'] as $prefix => $icons ) {
			$prefix_icons = array();
			foreach( $icons as $k => $v ) {

				$v = strval( $v );

				if ( is_string( $query ) && false === stripos( $v, $query ) ) {
					continue;
				}

				$prefix_icons[] = array(
					'id'	=> $k,
					'text'	=> $v
				);
			}
			$results[] = array(
				'id'		=> 'fab',
				'text'		=> $this->get_prefix_label( $prefix ),
				'children'	=> $prefix_icons
			);
		}
		$response = array(
			'results'	=> $results
		);

		return $response;
	}

	/**
	 * Load the icons from the manifest
	 * 
	 * @param array $manifest
	 * 
	 * @return array
	 */
	private function find_icons( $manifest ) {
		$icons = array(
			'list'		=> array(),
			'details'	=> array()
		);

		foreach ( $manifest as $icon => $details ) {
			foreach( $details['styles'] as $style ) {

				$prefix = $this->get_prefix( $style );

				if ( ! isset( $icons['list'][ $prefix ] ) ) {
					$icons['list'][ $prefix ] = array();
				}

				if ( 'fad' == $prefix ) {
					$icons['list'][ $prefix ][ $prefix . ' fa-' . $icon ] = '<i class="' . $prefix . ' fa-' . $icon . '"></i> ' . $icon;
				} else {
					$icons['list'][ $prefix ][ $prefix . ' fa-' . $icon ] = '<i class="' . $prefix . '">&#x' . $details['unicode'] . ';</i> ' . $icon;
				}

				$icons['details'][ $prefix ][ $prefix . ' fa-' . $icon ] = array(
					'hex'		=> '\\' . $details['unicode'],
					'unicode'	=> '&#x' . $details['unicode'] . ';'
				);
			}
		}

		return $icons;
	}

	/**
	 * Get parsed icon prefix
	 * 
	 * @param string $style
	 */
	public function get_prefix( $style ) {
		$prefix = 'far';

		switch ( $style ) {
			case 'solid':
				$prefix = 'fas';
				break;

			case 'brands':
				$prefix = 'fab';
				break;

			case 'light':
				$prefix = 'fal';
				break;

			case 'duotone':
				$prefix = 'fad';
				break;

			case 'regular':
			default:
				$prefix = 'far';
				break;
		}

		return $prefix;
	}

	/**
	 * Get icon prefix label
	 * 
	 * @param string $prefix
	 * 
	 * @return string
	 */
	public function get_prefix_label( $prefix ) {

		$label = 'Regular';

		switch ( $prefix ) {
			case 'fas':
				$label = __( 'Solid', 'business-directory-plugin' );
				break;

			case 'fab':
				$label = __( 'Brands', 'business-directory-plugin' );
				break;

			case 'fal':
				$label = __( 'Light', 'business-directory-plugin' );
				break;

			case 'fad':
				$label = __( 'Duotone', 'business-directory-plugin' );
				break;

			case 'far':
			default:
				$label = __( 'Regular', 'business-directory-plugin' );
				break;
		}

		return $label;
	}
}
