<?php
/*
Plugin Name: WPMissionControl
Plugin URI: https://wpmissioncontrol.com
Description: Remote maintenance and security system for Wordpress websites provided by WPMissionControl Center.
Author: WPMissionControl Team
Version: 1.0
Requires PHP: 5.3.0
Author URI: https://wpmissioncontrol.com
Text Domain: wpmc
*/

if ( !class_exists( 'WPMC_Plugin' ) ) {

	if ( !defined( 'WPMC_PLUGIN_DIR_URL' ) ) {
		define( 'WPMC_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
	}
	if ( !defined( 'WPMC_PLUGIN_DIR_PATH' ) ) {
		define( 'WPMC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
	}
	if ( !defined( 'WPMC_MAINTENANCE_EMAIL') ) {
		define( 'WPMC_MAINTENANCE_EMAIL', 'maintenance@wpmissioncontrol.com' );
	}
	if ( !defined( 'WPMC_STATIC_ASSETS_URL') ) {
		define( 'WPMC_STATIC_ASSETS_URL', 'https://wpmc-static-assets.s3.eu-central-1.amazonaws.com' );
	}

	require_once dirname( __FILE__ ) . '/includes/WPMC_Plugin.php';
	require_once dirname( __FILE__ ) . '/includes/WPMC_Security.php';

	register_activation_hook(   __FILE__, array( 'WPMC_Plugin', 'activate_plugin' ) );
	register_deactivation_hook( __FILE__, array( 'WPMC_Plugin', 'deactivate_plugin' ) );
	register_uninstall_hook(    __FILE__, array( 'WPMC_Plugin', 'uninstall_plugin' ) );

	add_action( 'plugins_loaded', array( 'WPMC_Plugin', 'init' ) );
}