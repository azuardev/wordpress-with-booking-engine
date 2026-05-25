<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Icegram_Mailer_Installer' ) ) {

	/**
	 * Icegram_Mailer_Installer Class.
	 */
	class Icegram_Mailer_Installer {

		/**
		 * Background update class.
		 *
		 * @var object
		 */
		public static $logger;

		/**
		 * Added Logger Context
		 *
		 * @since 4.2.0
		 * @var array
		 */
		public static $logger_context = [
			'source' => 'icegram_mailer_db_updates',
		];

		/**
		 * DB updates and callbacks that need to be run per version.
		 *
		 * @since 4.0.0
		 * @var array
		 */
		private static $db_updates = [];

		/**
		 * Begin Installation
		 *
		 * @since 4.0.0
		 */
		public static function install() {

			if ( ! is_blog_installed() ) {
				return;
			}

			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'icegram_mailer_installing' ) ) {

				return;
			}

			if ( self::is_new_install() ) {

				// If we made it till here nothing is running yet, lets set the transient now.
				set_transient( 'icegram_mailer_installing', 'yes', MINUTE_IN_SECONDS * 10 );

				// Create Tables
				self::create_tables();

				// Create Default Option
				self::create_options();
			}
			delete_transient( 'icegram_mailer_installing' );

		}

		/**
		 * Is this new Installation?
		 *
		 * @return bool
		 *
		 * @since 4.0.0
		 */
		public static function is_new_install() {
			return is_null( get_option( 'icegram_mailer_db_version', null ) );
		}

		/**
		 * Get latest db version based on available updates.
		 *
		 * @return mixed
		 *
		 * @since 4.0.0
		 */
		public static function get_latest_db_version_to_update() {
			$updates         = self::get_db_update_callbacks();
			$update_versions = array_keys( $updates );
			usort( $update_versions, 'version_compare' );

			return end( $update_versions );
		}

		/**
		 * Get all database updates
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_db_update_callbacks() {
			return self::$db_updates;
		}

		/**
		 * Create default options while installing
		 *
		 * @since 4.0.0
		 */
		private static function create_options() {
			$options = self::get_options();
			foreach ( $options as $option => $values ) {
				add_option( $option, $values, '', false );
			}
		}

		/**
		 * Get default options
		 *
		 * @return array
		 *
		 * @since 4.0.0
		 */
		public static function get_options() {

			// We are setting latest_db_version as a icegram_mailer_db_version option while installation
			// So, we don't need to run the upgrade process again.
			$db_version = icegram_mailer_get_db_version();

			$options = array(
				'icegram_mailer_db_version' => $db_version,
			);

			return $options;
		}

		/**
		 * Create tables
		 *
		 * @param null $version
		 *
		 * @since 4.0.0
		 *
		 * @modify 4.4.9
		 */
		public static function create_tables( $version = null ) {

			global $wpdb;

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}
			
			$schema_fn = 'get_schema';

			$wpdb->hide_errors();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( self::$schema_fn( $collate ) );
		}

		/**
		 * Add new table
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.1
		 */
		public static function get_email_logs_table_schema( $collate = '' ) {

			global $wpdb;

			$tables = "
			CREATE TABLE `{$wpdb->prefix}icegram_mailer_email_logs` (
			  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `tracking_id` VARCHAR(255) NOT NULL,
			  `to` text NOT NULL,
			  `subject` text DEFAULT NULL,
			  `headers` text DEFAULT NULL,
			  `body` longtext DEFAULT NULL,
			  `attachments` varchar(255) DEFAULT NULL,
			  `status` varchar(255),
			  `error` varchar(255),
			  `created_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `updated_at` int(11) UNSIGNED NOT NULL DEFAULT 0,
			  `opened_at` int(11) UNSIGNED DEFAULT NULL DEFAULT 0,
			  PRIMARY KEY (id)
			) $collate;
		";

			return $tables;
		}

		/**
		 * Collect multiple version schema
		 *
		 * @param string $collate
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		private static function get_schema( $collate = '' ) {

			$tables = self::get_email_logs_table_schema( $collate );

			return $tables;
		}
	}
}
