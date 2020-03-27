<?php
/*
Plugin Name: Pods SEO
Plugin URI: http://pods.io/
Description: Integrates with WP SEO Analysis for custom fields and Pods Advanced Content Types with WordPress SEO XML Sitemaps
Version: 2.2
Author: Pods Framework Team
Author URI: http://pods.io/about/
Text Domain: pods-seo

Copyright 2013-2016  Pods Foundation, Inc  (email : contact@podsfoundation.org)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'PODS_SEO_VERSION', '2.2' );
define( 'PODS_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'PODS_SEO_DIR', plugin_dir_path( __FILE__ ) );

/**
 * @global Pods_SEO_WPSEO $pods_seo_wpseo
 */
global $pods_seo_wpseo;

/**
 * Initialize plugin
 */
function pods_seo_init() {
	if ( ! function_exists( 'pods' ) ) {
		return;
	}

	require_once PODS_SEO_DIR . 'classes/pods-seo-wpseo.php';

	global $pods_seo_wpseo;

	$pods_seo_wpseo = Pods_SEO_WPSEO::get_instance();
}

add_action( 'init', 'pods_seo_init' );

/**
 * Register add-on with Pods Freemius connection.
 *
 * @since 2.2
 */
function pods_seo_freemius() {
	try {
		fs_dynamic_init( [
			'id'               => '5753',
			'slug'             => 'pods-seo',
			'type'             => 'plugin',
			'public_key'       => 'pk_46fa00cfe39af82dd1ed03033bea4',
			'is_premium'       => false,
			'has_paid_plans'   => false,
			'is_org_compliant' => true,
			'parent'           => [
				'id'         => '5347',
				'slug'       => 'pods',
				'public_key' => 'pk_737105490825babae220297e18920',
				'name'       => 'Pods',
			],
			'menu'             => [
				'slug'        => 'pods-settings',
				'contact'     => false,
				'support'     => false,
				'affiliation' => false,
				'account'     => true,
				'pricing'     => false,
				'addons'      => true,
				'parent'      => [
					'slug' => 'pods',
				],
			],
		] );
	} catch ( \Exception $exception ) {
		return;
	}
}

add_action( 'pods_freemius_init', 'pods_seo_freemius' );
