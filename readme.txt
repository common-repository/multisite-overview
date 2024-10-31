=== Multisite Overview ===
Contributors: jokr
Tags: description, multi-site, overview
Requires at least: 3.5.2
Tested up to: 3.6.0
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugins enables sites of a network to display information and posts from other sites via shortcodes.

== Description ==

With this plugin each site of a network can decide whether its content can be displayed by other sites as well via specific shortcodes.
The information displayed depends on the used shortcode. There are three variations available:

* [multisite] displays all the available sites with a short description and the most recent posts. The sites to display can be defined via a include and exclude list.
* [multisite-featured] displays one site with a custom image and in a more prominent way.
* [multisite-all] is a index of all visible sites in alphabetical order without any additional information.

As the tagline field usually is rather a witty comment on your site rather than a actual description, this plugin also
provides you with a description field for all sites and a widget to display it accordingly.

== Installation ==

1. Upload the `multisite-overview` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in the network administration area.

== Changelog ==

= 1.1.0 =
* Added a numposts parameter to the multisite and multisite-all shortcode to define the number of recent posts displayed.
* Added a sort parameter to the multisite shortcode to define either alphabetical sorting or sorting by most recent post.
* Added a layout parameter to the multisite shortcode to switch between a two column grid layout and a table layout.
* Added the ability to set the visibility of each site globally in the network admin area.
* Multiple bugfixes.

= 1.0.1 =
* Fixed compatibility with php <= 5.3 by removing function array dereferencing

= 1.0.0 =
* Stable version.