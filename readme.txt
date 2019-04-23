=== Pods SEO ===
Contributors: pglewis, keraweb, sc0ttkclark
Donate link: https://pods.io/friends-of-pods/
Tags: pods, seo, analysis, xml sitemaps
Requires at least: 4.6
Tested up to: 5.1.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates with WP SEO Analysis for custom fields and Pods Advanced Content Types with WordPress SEO XML Sitemaps

== Description ==

This plugin requires the [Pods Framework](http://wordpress.org/plugins/pods/) and [WordPress SEO](http://wordpress.org/plugins/wordpress-seo/) to run.

= Our WordPress SEO plugin integration includes =

* Includes custom field values in a post type for WP SEO Analysis (Requires Pod)
* Adds Advanced Content Types to XML Sitemaps
* Adds option to choose which Advanced Content Types will be included in XML Sitemaps (Pod must have a Detail Page URL set)

Pods SEO is designed for use with Pods Advanced Content Types. Other Pods content types integrate with WordPress SEO and other SEO plugins automatically. For more information on SEO and Pods Advanced Content Types please see [this tutorial](http://pods.io/?p=179774) or [this screencast](http://pods.io/?p=179974).

WP SEO Analysis integration includes all Text, Image, Paragraph, and WYSIWYG field content automatically. You can choose to exclude individual fields by editing those fields options under the 'Advanced Field Options' tab.

We're looking in the future at integrating with other features from WordPress SEO and integrating with other plugins too.

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Screenshots ==

1. Choose which Advanced Content Types will be included in the XML Sitemap for WordPress SEO

== Contributors ==

Check out our GitHub for a list of contributors, or search our GitHub issues to see everyone involved in adding features, fixing bugs, or reporting issues/testing.

[github.com/pods-framework/pods-seo/graphs/contributors](https://github.com/pods-framework/pods-seo/graphs/contributors)

== Changelog ==

= 2.1 - March 23rd, 2017 =
* Added: Support for Image fields in SEO analysis integration
* Added: Image information added to Yoast SEO XML sitemap entries filter

= 2.0 - June 24th, 2016 =
* Added WP SEO Analysis integration
* Fixed assignment bug issues with $lastmod (props to @avhaliullin for finding the bug and outlining the fix)

= 1.0 - December 3rd, 2013 =
* First official release!
* Found a bug? Have a great feature idea? Get on GitHub and tell us about it and we'll get right on it: [github.com/pods-framework/pods-seo/issues/new](https://github.com/pods-framework/pods-seo/issues/new)