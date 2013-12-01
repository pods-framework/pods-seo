<?php
/*
Plugin Name: Pods SEO
Plugin URI: http://pods.io/
Description: Integrates Pods' ACTs with the WordPress SEO XML sitemap generation process
Version: 1.0.0 Beta 1
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

define ( 'PODS_SEO_XML_OPTION_NAME', 'pods_wpseo_xml' );
define ( 'PODS_SEO_ACT_OPTION_PREFIX', 'pods_act-' );
define ( 'PODS_SEO_SITEMAP_PREFIX', 'pods_' );

/**
 * Add checkboxes for Pods' ACTs into the XML sitemaps form
 */
add_action( 'wpseo_xmlsitemaps_config', 'pods_seo_xmlsitemaps_config' );
function pods_seo_xmlsitemaps_config () {

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

	?>
	<h2>Pods Advanced Content Types</h2>
	<p>
		Select the Pods' Advanced Content Types you would like to generate sitemaps for:
	</p>
	<?php

	// Checkboxes for each ACT
	foreach ( $available_acts as $this_act ) {
		echo pods_seo_act_checkbox( PODS_SEO_ACT_OPTION_PREFIX . $this_act[ 'name' ], $this_act[ 'label' ] . ' (<code>' . $this_act[ 'name' ] . '</code>)' );
	}
}

/**
 * @param $var
 * @param $label
 *
 * @return string
 */
function pods_seo_act_checkbox ( $var, $label ) {

	$option_name = PODS_SEO_XML_OPTION_NAME;

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
add_filter( 'pre_update_option_wpseo_xml', 'pods_seo_pre_update_option_wpseo_xml', 10, 2 );
function pods_seo_pre_update_option_wpseo_xml ( $value, $old_value ) {

	$option_name = PODS_SEO_XML_OPTION_NAME;

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
 * Add selected Pods ACTs into the sitemap index
 */
add_filter( 'wpseo_sitemap_index', 'pods_seo_sitemap_index' );
function pods_seo_sitemap_index () {

	/** @global WP_Rewrite $wp_rewrite */
	global $wp_rewrite;

	// Can't do anything if pods has been deactivated
	if ( !function_exists( 'pods' ) ) {
		return '';
	}

	$base_url = $wp_rewrite->using_index_permalinks() ? 'index.php/' : '';
	$option_name = PODS_SEO_XML_OPTION_NAME;
	$xml_options = ( function_exists( 'is_network_admin' ) && is_network_admin() ) ? get_site_option( $option_name ) : get_option( $option_name );

	// Nothing to be done if no options are set
	if ( !is_array( $xml_options ) || 0 == count( $xml_options ) ) {
		return '';
	}

	$output = '';
	$pods_api = pods_api();
	foreach ( $xml_options as $key => $value ) {

		// Pull the option prefix off to get the Pod name
		$pod_name = $key;
		if ( substr( $pod_name, 0, strlen( PODS_SEO_ACT_OPTION_PREFIX ) ) == PODS_SEO_ACT_OPTION_PREFIX ) {
			$pod_name = substr( $pod_name, strlen( PODS_SEO_ACT_OPTION_PREFIX ) );
		}

		$pod = $pods_api->load_pod( $pod_name );

		// Skip if we couldn't find the pod or it doesn't have a detail_url set
		if ( !is_array( $pod ) || !isset( $pod[ 'options' ][ 'detail_url' ] ) ) {
			continue;
		}

		// Determine last modified date
		if ( isset( $pod[ 'fields' ][ 'modified' ] ) ) {
			$params = array(
				'orderby' => 'modified DESC',
				'limit'   => 1
			);
			$newest = pods( $pod_name, $params );
			$lastmod = $newest->field( 'modified' );
			if ( !empty( $lastmod ) ) {
				mysql2date( "Y-m-d\TH:i:s+00:00", $lastmod );
			}
			else {
				$lastmod = date( 'c' );
			}
		}
		else {
			$lastmod = date( 'c' );
		}

		$xml_filename = PODS_SEO_SITEMAP_PREFIX . $pod_name . '-sitemap.xml';

		$output .= "<sitemap>\n";
		$output .= "<loc>" . home_url( $base_url . $xml_filename ) . "</loc>\n";
		$output .= "<lastmod>$lastmod</lastmod>\n";
		$output .= "</sitemap>\n";
	}

	return $output;
}

/**
 * Add action hooks for each of the xml files we've added to the index and filter hooks for Pods item save/delete
 * ToDo: this and the sitemap index generator share a lot of code; DRY
 */
add_action( 'init', 'pods_seo_register_xml_hooks' );
function pods_seo_register_xml_hooks () {

	// Bail if either Pods or WordPress SEO are missing
	if ( !class_exists( 'WPSEO_Sitemaps' ) || !function_exists( 'pods' ) ) {
		return;
	}

	$option_name = PODS_SEO_XML_OPTION_NAME;
	$xml_options = ( function_exists( 'is_network_admin' ) && is_network_admin() ) ? get_site_option( $option_name ) : get_option( $option_name );

	// Nothing to be done if no options are set
	if ( !is_array( $xml_options ) || 0 == count( $xml_options ) ) {
		return;
	}

	$pods_api = pods_api();
	foreach ( $xml_options as $key => $value ) {

		// Pull the option prefix off to get the Pod name
		$pod_name = $key;
		if ( substr( $pod_name, 0, strlen( PODS_SEO_ACT_OPTION_PREFIX ) ) == PODS_SEO_ACT_OPTION_PREFIX ) {
			$pod_name = substr( $pod_name, strlen( PODS_SEO_ACT_OPTION_PREFIX ) );
		}

		$pod = $pods_api->load_pod( $pod_name );

		// Skip if we couldn't find the pod or it doesn't have a detail_url set
		if ( !is_array( $pod ) || !isset( $pod[ 'options' ][ 'detail_url' ] ) ) {
			continue;
		}

		$sitemap_action_hook = 'wpseo_do_sitemap_' . PODS_SEO_SITEMAP_PREFIX . $pod_name;
		$pods_save_hook = 'pods_api_post_save_pod_item_' . $pod_name;
		$pods_delete_hook = 'pods_api_post_delete_pod_item_' . $pod_name;

		add_action( $sitemap_action_hook, 'pods_seo_xml_sitemap' );
		add_filter( $pods_save_hook, 'pods_seo_xml_ping', 10, 2 );
		add_filter( $pods_delete_hook, 'pods_seo_xml_ping', 10, 2 );
	}
}

/**
 * @param $pieces
 * @param $is_new_item
 */
function pods_seo_xml_ping ( $pieces, $is_new_item ) {

	// Bail if WordPress SEO isn't activated
	if ( !function_exists( 'wpseo_ping_search_engines' ) ) {
		return $pieces;
	}

	if ( WP_CACHE ) {
		wp_schedule_single_event( time() + 300, 'wpseo_hit_sitemap_index' );
	}

	if ( defined( 'YOAST_SEO_PING_IMMEDIATELY' ) && YOAST_SEO_PING_IMMEDIATELY ) {
		wpseo_ping_search_engines();
	}
	else {
		wp_schedule_single_event( ( time() + 300 ), 'wpseo_ping_search_engines' );
	}

	return $pieces;
}

/**
 * Generate individual sitemap files.  Called via the 'wpseo_do_sitemap_*' hooks
 */
function pods_seo_xml_sitemap () {

	/** @global WPSEO_Sitemaps $wpseo_sitemaps */
	global $wpseo_sitemaps;

	// Bail if either Pods or WordPress SEO are missing
	if ( !class_exists( 'WPSEO_Sitemaps' ) || !function_exists( 'pods' ) ) {
		return;
	}

	$sitemap = get_query_var( 'sitemap' );
	if ( empty( $sitemap ) ) {
		return;
	}

	// Pull the prefix off the front
	$pod_name = $sitemap;
	if ( substr( $pod_name, 0, strlen( PODS_SEO_SITEMAP_PREFIX ) ) == PODS_SEO_SITEMAP_PREFIX ) {
		$pod_name = substr( $pod_name, strlen( PODS_SEO_SITEMAP_PREFIX ) );
	}

	$pod = pods_api( $pod_name );

	$params = array( 'limit' => -1 );
	if ( isset( $pod->fields[ 'modified' ] ) ) {
		$params[ 'orderby' ] = 't.modified DESC';
	}
	else {
		$params[ 'orderby' ] = 't.ID DESC';
	}

	$pod = pods( $pod_name, $params );

	//Build the full sitemap
	$sitemap = "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
	$sitemap .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
	$sitemap .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

	while ( $pod->fetch() ) {

		$lastmod = $pod->field( 'modified' );
		if ( !empty( $lastmod ) ) {
			$lastmod = mysql2date( "Y-m-d\TH:i:s+00:00", $lastmod );
		}
		else {
			$lastmod = date( 'c' );
		}

		$sitemap .= "<url>\n";
		$sitemap .= "<loc>" . $pod->display( 'detail_url' ) . "</loc>\n";
		$sitemap .= "<lastmod>$lastmod</lastmod>\n";
		$sitemap .= "<changefreq>weekly</changefreq>\n"; // ToDo: provide filter
		$sitemap .= "<priority>0.5</priority>\n"; // ToDo: provide filter
		$sitemap .= "</url>\n";
	}

	$sitemap .= "</urlset>\n";

	$wpseo_sitemaps->set_sitemap( $sitemap );
}
