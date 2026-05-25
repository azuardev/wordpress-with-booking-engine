<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://icegram.com
 * @since             1.0.0
 * @package           Icegram_Mailer
 *
 * @wordpress-plugin
 * Plugin Name:       Icegram Mailer
 * Plugin URI:        https://icegram.com
 * Description:       Plugin to send emails via Icegram sending service
 * Version:           1.0.11
 * Author:            Icegram
 * Requires at least: 4.7
 * Tested up to: 	  6.9
 * Requires PHP: 	  7.0
 * Author URI:        https://icegram.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       icegram-mailer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'ICEGRAM_MAILER_VERSION' ) ) {
	define( 'ICEGRAM_MAILER_VERSION', '1.0.11' );
}

if ( ! defined( 'ICEGRAM_MAILER_FEEDBACK_VERSION' ) ) {
	define( 'ICEGRAM_MAILER_FEEDBACK_VERSION', '1.2.12' );
}

if ( ! defined( 'ICEGRAM_MAILER_TRACKER_VERSION' ) ) {
	define( 'ICEGRAM_MAILER_TRACKER_VERSION', '1.2.6' );
}

if ( ! defined( 'ICEGRAM_MAILER_PLUGIN_PATH' ) ) {
	define( 'ICEGRAM_MAILER_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'ICEGRAM_MAILER_PLUGIN_URL' ) ) {
	define( 'ICEGRAM_MAILER_PLUGIN_URL', untrailingslashit(  plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'ICEGRAM_MAILER_IMAGE_URL' ) ) {
	define( 'ICEGRAM_MAILER_IMAGE_URL', ICEGRAM_MAILER_PLUGIN_URL . '/assets/images/' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-icegram-mailer-activator.php
 */

if ( ! function_exists( 'icegram_mailer_activate' ) ) {
	function icegram_mailer_activate() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-icegram-mailer-activator.php';
		Icegram_Mailer_Activator::activate();
	}
	
	register_activation_hook( __FILE__, 'icegram_mailer_activate' );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-icegram-mailer-deactivator.php
 */
if ( ! function_exists( 'icegram_mailer_deactivate' ) ) {
	function icegram_mailer_deactivate() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-icegram-mailer-deactivator.php';
		Icegram_Mailer_Deactivator::deactivate();
	}
	register_deactivation_hook( __FILE__, 'icegram_mailer_deactivate' );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-icegram-mailer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'icegram_mailer' ) ) {
	function icegram_mailer() {
		return Icegram_Mailer::instance();
	}
	icegram_mailer();
}
