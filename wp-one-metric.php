<?php 
/*
Plugin Name: WP One Metric
Plugin URI: http://digitalcube.jp
Description: 
Author: horike
Version: 1.0
Author URI: http://digitalcube.jp


Copyright 2014 Digitalcube (email : info@digitalcube.jp)

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

if ( ! defined( 'WPOMC_DOMAIN' ) )
	define( 'WPOMC_DOMAIN', '' );
	
if ( ! defined( 'WPOMC_PLUGIN_URL' ) )
	define( 'WPOMC_PLUGIN_URL', plugins_url() . '/' . dirname( plugin_basename( __FILE__ ) ));

if ( ! defined( 'WPOMC_PLUGIN_DIR' ) )
	define( 'WPOMC_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ));

if ( !class_exists( 'gapi' ) ) {
	require_once( WPOMC_PLUGIN_DIR . '/lib/gapi.class.php' );
}
require_once( WPOMC_PLUGIN_DIR . '/modules/admin.php' );
require_once( WPOMC_PLUGIN_DIR . '/modules/main.php' );

$wp_one_metric = new WP_One_Metric();
register_activation_hook( __FILE__, array( $wp_one_metric, 'set_event' ) );
register_deactivation_hook( __FILE__, array( $wp_one_metric, 'delete_event' ) );