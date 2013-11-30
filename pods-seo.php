<?php
/*
Plugin Name: Pods SEO
Plugin URI: http://pods.io/
Description: Currently integrates with the WordPress SEO XML sitemap generation process
Version: 1.0.0
Author: Pods Framework Team
Author URI: http://pods.io/about/

Copyright 2009-2013  Pods Foundation, Inc  (email : contact@podsfoundation.org)

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

define ( 'PODS_WPSEO_XML_OPTION_NAME', 'pods_wpseo_xml' );

/**
 * Add checkboxes for Pods' ACTs into the XML sitemaps form
 */
add_action( 'wpseo_xmlsitemaps_config', 'pods_xmlsitemaps_config' );
function pods_xmlsitemaps_config () {

	// Bail now if pods or  activated
	if ( !function_exists( 'pods' ) || !function_exists( 'wpseo_init' ) ) {
		return;
	}

	// Look for ACTs with the detail_url set
	$pods_api = pods_api();
	$all_acts = $pods_api->load_pods( array( 'type' => 'pod' ) );
	$available_acts = array();
	foreach ( $all_acts as $this_act ) {
		if ( isset ( $this_act[ 'options' ][ 'detail_url' ] ) ) {
			$available_acts[ ] = $this_act;
		}
	}

	// Nothing to do if there aren't any ACTs
	if ( !is_array( $available_acts ) || 0 == count( $available_acts ) ) {
		return;
	}

	// ToDo: Replace lorem
	?>
	<h2>Pods Advanced Content Types</h2>
	<p>
		Vestibulum semper nunc sed justo volutpat malesuada. Maecenas dictum velit sit amet urna pellentesque, in aliquam justo elementum. Cras et blandit enim. Sed ac blandit urna, non blandit nulla.
	</p>
	<?php

	// Checkboxes for each ACT
	foreach ( $available_acts as $this_act ) {
		echo pods_act_checkbox( 'pods_act-' . $this_act[ 'name' ], $this_act[ 'label' ] . ' (<code>' . $this_act[ 'name' ] . '</code>)' );
	}
}

/**
 * @param $var
 * @param $label
 *
 * @return string
 */
function pods_act_checkbox ( $var, $label ) {

	$option_name = PODS_WPSEO_XML_OPTION_NAME;

	if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
		$options = get_site_option( $option_name );
	}
	else {
		$options = get_option( $option_name );
	}

	if ( !isset( $options[ $var ] ) ) {
		$options[ $var ] = false;
	}

	if ( $options[ $var ] === true ) {
		$options[ $var ] = 'on';
	}

	$output_label = '<label for="' . esc_attr( $var ) . '">' . $label . '</label>';
	$class = 'checkbox double';

	$output_input = "<input class='$class' type='checkbox' id='" . esc_attr( $var ) . "' name='" . esc_attr( $option_name ) . "[" . esc_attr( $var ) . "]' " . checked( $options[ $var ], 'on', false ) . '/>';
	$output = $output_input . $output_label;

	return $output . '<br class="clear" />';
}

/**
 * Save out settings when the WordPress SEO XML sitemaps settings are saved.  We're hooking the WordPress
 * filter in lieu of a reliable action hook (WordPress will exit update_option() without hooking the
 * update_option_{$option} action if no changes were made to the options being saved). Be careful to always
 * return $value untouched here, it belongs to WordPress SEO.
 *
 * @param $value
 * @param $old_value
 */
add_filter( 'pre_update_option_wpseo_xml', 'pods_pre_update_option_wpseo_xml', 10, 2 );
function pods_pre_update_option_wpseo_xml ( $value, $old_value ) {

	$option_name = PODS_WPSEO_XML_OPTION_NAME;

	// Nothing to do if we don't have any options in the post data
	if ( !isset( $_POST[ $option_name ] ) ) {
		return $value;
	}

	$pods_options = $_POST[ $option_name ];
	if ( !is_array( $pods_options ) ) {
		$pods_options = trim( $pods_options );
	}
	update_option( $option_name, wp_unslash( $pods_options ) );

	return $value;
}

/**
 * Inject custom item into the sitemap index
 *
 * Proof of concept only
 */
add_filter( 'wpseo_sitemap_index', 'pods_sitemap_custom_items' );
function pods_sitemap_custom_items () {

	$base = $GLOBALS[ 'wp_rewrite' ]->using_index_permalinks() ? 'index.php/' : '';

	// ToDo Loop through pods that have be selected for sitemaps; hard-coded for prototyping
	// We'll need to have an action hook for each Pod
	$output = "<sitemap>\n";
	$output .= "<loc>" . home_url( $base . 'pods-sitemap.xml' ) . "</loc>\n";
	$output .= "<lastmod>2013-11-29T21:57:43+00:00</lastmod>\n";
	$output .= "</sitemap>\n";

	return $output;
}

/**
 * Proof of concept only; we need an action hook for each Pod with a sitemap
 */
add_action( 'wpseo_do_sitemap_pods', 'pods_xml_sitemap' );
function pods_xml_sitemap () {

	if ( class_exists( 'WPSEO_Sitemaps' ) ) {

		/** @global WPSEO_Sitemaps $wpseo_sitemaps ; */
		global $wpseo_sitemaps;

		//Build the full sitemap
		$sitemap = "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
		$sitemap .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
		$sitemap .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

		// ToDo: loop through the items for this pod, this is hard-coded for testing

		$sitemap .= "<url>\n";
		$sitemap .= "<loc>" . home_url() . "/bob-is-your-uncle/" . "</loc>\n";
		$sitemap .= "<lastmod>2013-11-27T18:33:23+00:00</lastmod>\n";
		$sitemap .= "<changefreq>weekly</changefreq>\n";
		$sitemap .= "<priority>0.5</priority>\n";
		$sitemap .= "</url>\n";

		// ToDo: end of loop

		$sitemap .= "</urlset>\n";

		$wpseo_sitemaps->set_sitemap( $sitemap );
	}
}
