<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Fired during plugin activation
 *
 * @link       https://icegram.com
 * @since      1.0.0
 *
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 * @author     Icegram <hello@icegram>
 */
class Icegram_Mailer_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once dirname( __FILE__ ) . '/class-icegram-mailer-installer.php';
		Icegram_Mailer_Installer::install();
		do_action( 'icegram_mailer_plugin_activated' );
	}

}
