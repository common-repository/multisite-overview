<?php
/*
Plugin Name: Multisite Overview
Plugin URI: http://www.aleaiactaest.ch/multisite-overview
Description: Allows the display of blog posts from all sites in a multisite network via shortcodes.
Version: 1.1.0
Network: True
Author: Joel Krebs
Author URI: http://www.aleaiactaest.ch
License: GPL2
*/

include_once dirname( __FILE__ ) . '/class-multisite-overview.php';
include_once dirname( __FILE__ ) . '/class-site-description-field-widget.php';

if ( class_exists( 'Multisite_Overview' ) ) {
	register_activation_hook( __FILE__, array('Multisite_Overview', 'activate') );
	register_deactivation_hook( __FILE__, array('Multisite_Overview', 'deactivate') );

	$multisite_overview = new Multisite_Overview();
}