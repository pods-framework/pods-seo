<?php
/**
 * Class Pods_SEO_WPSEO
 */
class Pods_SEO_WPSEO {

	const XML_OPTION_NAME = 'pods_wpseo_xml';

	const ACT_OPTION_PREFIX = 'pods_act-';

	const SITEMAP_PREFIX = 'pods_';

	/**
	 *
	 */
	public function __construct () {

		add_action( 'wpseo_xmlsitemaps_config', array( $this, 'xmlsitemaps_config' ) );
		add_filter( 'pre_update_option_wpseo_xml', array( $this, 'pre_update_option_wpseo_xml' ) );
		add_filter( 'wpseo_sitemap_index', array( $this, 'sitemap_index' ) );
		add_action( 'init', array( $this, 'register_xml_hooks' ) );
	}

	/**
	 * Add checkboxes for Pods' ACTs into the XML sitemaps form
	 */
	public function xmlsitemaps_config () {

		// Bail now if pods or  activated
		if ( !function_exists( 'pods' ) || !function_exists( 'wpseo_init' ) ) {
			return;
		}

		// Look for ACTs with the detail_url set
		$all_acts = pods_api()->load_pods( array( 'type' => 'pod' ) );
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
			echo $this->act_checkbox( self::ACT_OPTION_PREFIX . $this_act[ 'name' ], $this_act[ 'label' ] . ' (<code>' . $this_act[ 'name' ] . '</code>)' );
		}
	}

	/**
	 * Save our settings when the WordPress SEO XML sitemaps settings are saved.  We're hooking the WordPress
	 * filter in lieu of a reliable action hook (WordPress will exit update_option() without hooking the
	 * update_option_{$option} action if no changes were made to the options being saved). Be careful to always
	 * return $value untouched here, it belongs to WordPress SEO.
	 *
	 * @param $value
	 *
	 * @return
	 */
	public function pre_update_option_wpseo_xml ( $value ) {

		$option_name = self::XML_OPTION_NAME;

		// No options selected, clear them all
		if ( !isset( $_POST[ $option_name ] ) ) {
			delete_option( $option_name );
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
	public function sitemap_index () {

		/** @global WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		// Can't do anything if pods has been deactivated
		if ( !function_exists( 'pods' ) ) {
			return '';
		}

		$base_url = $wp_rewrite->using_index_permalinks() ? 'index.php/' : '';
		$option_name = self::XML_OPTION_NAME;
		$xml_options = ( function_exists( 'is_network_admin' ) && is_network_admin() ) ? get_site_option( $option_name ) : get_option( $option_name );

		// Nothing to be done if no options are set
		if ( !is_array( $xml_options ) || 0 == count( $xml_options ) ) {
			return '';
		}

		// $xml_options: key = prefixed pod name, value = 'on'
		$output = '';
		foreach ( $xml_options as $key => $value ) {

			$pod_name = $this->remove_prefix( self::ACT_OPTION_PREFIX, $key );
			$pod = pods_api()->load_pod( $pod_name );

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

			$xml_filename = self::SITEMAP_PREFIX . $pod_name . '-sitemap.xml';

			$output .= "<sitemap>\n";
			$output .= "<loc>" . home_url( $base_url . $xml_filename ) . "</loc>\n";
			$output .= "<lastmod>$lastmod</lastmod>\n";
			$output .= "</sitemap>\n";
		}

		return $output;
	}

	/**
	 * Add action hooks for each of the xml files we've added to the index
	 * Add filter hooks for Pods item save/delete
	 */
	public function register_xml_hooks () {

		// Bail if either Pods or WordPress SEO are missing
		if ( !class_exists( 'WPSEO_Sitemaps' ) || !function_exists( 'pods' ) ) {
			return;
		}

		$option_name = self::XML_OPTION_NAME;
		$xml_options = ( function_exists( 'is_network_admin' ) && is_network_admin() ) ? get_site_option( $option_name ) : get_option( $option_name );

		// Nothing to be done if no options are set
		if ( !is_array( $xml_options ) || 0 == count( $xml_options ) ) {
			return;
		}

		// $xml_options: key = prefixed pod name, value = 'on'
		foreach ( $xml_options as $key => $value ) {

			$pod_name = $this->remove_prefix( self::ACT_OPTION_PREFIX, $key );
			$pod = pods_api()->load_pod( $pod_name );

			// Skip if we couldn't find the pod or it doesn't have a detail_url set
			if ( !is_array( $pod ) || !isset( $pod[ 'options' ][ 'detail_url' ] ) ) {
				continue;
			}

			$sitemap_action_hook = 'wpseo_do_sitemap_' . self::SITEMAP_PREFIX . $pod_name;
			$pods_save_hook = 'pods_api_post_save_pod_item_' . $pod_name;
			$pods_delete_hook = 'pods_api_post_delete_pod_item_' . $pod_name;

			add_action( $sitemap_action_hook, array( $this, 'xml_sitemap' ) );
			add_filter( $pods_save_hook, array( $this, 'ping_search_engines' ) );
			add_filter( $pods_delete_hook, array( $this, 'ping_search_engines' ) );
		}
	}

	/**
	 * ! This is a filter hook, ALWAYS return $pieces
	 *
	 * @param $pieces
	 *
	 * @return mixed
	 */
	public function ping_search_engines ( $pieces ) {

		// Bail if WordPress SEO isn't activated
		if ( !function_exists( 'wpseo_ping_search_engines' ) ) {
			return $pieces;
		}

		// Run now or delayed, as appropriate
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
	public function xml_sitemap () {

		/** @global WPSEO_Sitemaps $wpseo_sitemaps */
		global $wpseo_sitemaps;

		// Bail if either Pods or WordPress SEO are missing
		if ( !class_exists( 'WPSEO_Sitemaps' ) || !function_exists( 'pods' ) ) {
			return;
		}

		// Rewrite rules will set sitemap=pods_foo for pods_foo-sitemap.xml
		$sitemap = get_query_var( 'sitemap' );
		if ( empty( $sitemap ) ) {
			return;
		}

		// Get the pod info first, we need to know if there is a 'modified' field to sort on
		$pod_name = $this->remove_prefix( self::SITEMAP_PREFIX, $sitemap);
		$pod = pods_api( $pod_name );
		$params = array( 'limit' => -1 );
		$params[ 'orderby' ] = ( isset( $pod->fields[ 'modified' ] ) ) ? 't.modified DESC' : 't.ID DESC';

		// Load all the sorted items
		$pod = pods( $pod_name, $params );

		//Build the full sitemap
		$sitemap = "<urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' ";
		$sitemap .= "xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' ";
		$sitemap .= "xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

		while ( $pod->fetch() ) {

			// Use modified field if it exits, or current date/time
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

	/**
	 * Based on the markup in wordpress-seo
	 *
	 * @param $var
	 * @param $label
	 *
	 * @return string
	 */
	private function act_checkbox ( $var, $label ) {

		$option_name = self::XML_OPTION_NAME;

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
	 * @param $needle
	 * @param $haystack
	 *
	 * @return string
	 */
	private function remove_prefix( $needle, $haystack ) {

		if ( substr( $haystack, 0, strlen( $needle ) ) == $needle ) {
			return substr( $haystack, strlen( $needle ) );
		}

		return $haystack;
	}
}