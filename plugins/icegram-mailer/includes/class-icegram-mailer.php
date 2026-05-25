<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://icegram.com
 * @since      1.0.0
 *
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/includes
 * @author     Icegram <hello@icegram>
 */
class Icegram_Mailer {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */

	/**
	 * ES instance
	 *
	 * @since 4.2.1
	 *
	 * @var Email_Subscribers The one true Email_Subscribers
	 */
	private static $instance;

	/**
	 * Icegram_Mailer_Client object
	 *
	 * @var object|Icegram_Mailer_Client
	 */
	public $client;

	/**
	 * Icegram_Mailer_Email_Logs_Table object
	 *
	 * @var object|Icegram_Mailer_Email_Logs_Table
	 */
	public $email_logs_table;

	public function __construct() {

		if ( defined( 'ICEGRAM_MAILER_VERSION' ) ) {
			$this->version = ICEGRAM_MAILER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'icegram-mailer';
	}

	public static function instance() {
		global $icegram_mailer_feedback, $icegram_mailer_tracker;

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Icegram_Mailer();

			self::$instance->define_constants();
			self::$instance->load_dependencies();
			self::$instance->set_locale();
			self::$instance->load_admin();

			$icegram_mailer_tracker = 'IG_Tracker_V_' . str_replace( '.', '_', ICEGRAM_MAILER_TRACKER_VERSION );
			if ( is_admin() ) {
				$ig_feedback_class = 'IG_Feedback_V_' . str_replace( '.', '_', ICEGRAM_MAILER_FEEDBACK_VERSION );
				$icegram_mailer_feedback     = new $ig_feedback_class( 'Icegram Mailer', 'icegram-mailer', 'ig_mailer', 'igmailer.', false );
				$icegram_mailer_feedback->render_deactivate_feedback();
			}
		}

		return self::$instance;
	}

	/**
	 * Define Contstants
	 *
	 * @since 4.0.0
	 */
	public function define_constants() {
		
		global $wpdb;

		$upload_dir = wp_upload_dir( null, false );

		if ( ! defined( 'ICEGRAM_MAILER_LOG_DIR' ) ) {
			define( 'ICEGRAM_MAILER_LOG_DIR', $upload_dir['basedir'] . '/icegram-mailer-logs/' );
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Icegram_Mailer_Loader. Orchestrates the hooks of the plugin.
	 * - Icegram_Mailer_I18n. Defines internationalization functionality.
	 * - Icegram_Mailer_Admin. Defines all hooks for the admin area.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {

		$files_to_load = [
			'/includes/helper-functions.php',
			'/includes/class-icegram-mailer-common.php',
			'/includes/db/class-icegram-mailer-base-table.php',
			'/includes/db/class-icegram-mailer-logs-table.php',
			'/includes/services/class-icegram-mailer-services.php',
			'/includes/services/class-icegram-mailer-email-delivery-check.php',
			'/includes/class-icegram-mailer-loader.php',
			'/includes/class-icegram-mailer-client.php',
			'/includes/class-icegram-mailer-i18n.php',
			'/includes/class-icegram-mailer-account.php',
			'/includes/class-icegram-mailer-message.php',
			'/includes/class-icegram-mailer-base-mailer.php',
			'/includes/class-icegram-mailer-ess-mailer.php',
			'/includes/class-icegram-mailer-express-integration.php',
			'/includes/class-icegram-mailer-router.php',
			'/includes/controllers/class-icegram-mailer-onboarding-controller.php',
			'/includes/controllers/class-icegram-mailer-dashboard-controller.php',
			'/includes/controllers/class-icegram-mailer-settings-controller.php',
			'/includes/feedback/class-ig-feedback.php',
			'/includes/feedback.php',
			'/includes/feedback/class-ig-tracker.php',
			'/admin/class-icegram-mailer-list-table.php',
			'/admin/class-icegram-mailer-email-list-table.php',
			'/admin/class-icegram-mailer-admin.php',
			'/admin/class-icegram-mailer-settings.php',
			'/admin/class-icegram-mailer-admin-notice.php',
			'/admin/notices/class-icegram-mailer-plugin-review-notice.php',
			'/includes/class-icegram-mailer-notifications.php',
		];

		foreach ( $files_to_load as $file ) {
			if ( is_file( ICEGRAM_MAILER_PLUGIN_PATH . $file ) ) {
				require_once ICEGRAM_MAILER_PLUGIN_PATH . $file;
			}
		}

		$this->client           = new Icegram_Mailer_Client();
		$this->email_logs_table = new Icegram_Mailer_Logs_Table();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Icegram_Mailer_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new Icegram_Mailer_I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function load_admin() {

		$plugin_admin = new Icegram_Mailer_Admin( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
