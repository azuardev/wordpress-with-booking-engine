<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://icegram.com
 * @since      1.0.0
 *
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 * @author     Icegram <hello@icegram>
 */
class Icegram_Mailer_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		// Unschedule notification cron jobs
		if ( class_exists( 'Icegram_Mailer_Notifications' ) ) {
			Icegram_Mailer_Notifications::unschedule_cron_jobs();
		}
	}

}
