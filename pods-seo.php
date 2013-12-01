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

define( 'PODS_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'PODS_SEO_DIR', plugin_dir_path( __FILE__ ) );

global $pods_seo_wpseo;

require_once PODS_SEO_DIR . 'classes/pods-seo-wpseo.php';
$pods_seo_wpseo = new Pods_SEO_WPSEO();

