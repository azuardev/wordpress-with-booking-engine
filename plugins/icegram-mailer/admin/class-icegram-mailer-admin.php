<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://icegram.com
 * @since      1.0.0
 *
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Icegram_Mailer
 * @subpackage Icegram_Mailer/admin
 * @author     Icegram <hello@icegram.com>
 */
class Icegram_Mailer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->register_admin_hooks();
	}

	public function register_admin_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name . '-admin', ICEGRAM_MAILER_PLUGIN_URL . '/admin/css/admin.css', [], $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-tailwind', ICEGRAM_MAILER_PLUGIN_URL . '/assets/css/tailwind.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( ! self::is_plugin_page() ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name . 'admin-js', ICEGRAM_MAILER_PLUGIN_URL . '/admin/js/admin.js', [ 'jquery' ], $this->version, true );
		$admin_js_data = [
			'_wpnonce' => wp_create_nonce( 'icegram-mailer-admin-ajax-nonce' )
		];
		wp_localize_script( $this->plugin_name . 'admin-js', 'icegram_mailer_admin_js_data', $admin_js_data );
	}

	public function add_admin_menu() {
		$settings = new Icegram_Mailer_Settings();
		add_menu_page( 'Icegram Mailer', 'Icegram Mailer', 'manage_options', 'icegram_mailer_dashboard', [ $this, 'show_dashboard' ], 'dashicons-email', 30 );
		add_submenu_page( 'icegram_mailer_dashboard', __( 'Dashboard', 'icegram-mailer' ), __( 'Dashboard', 'icegram-mailer' ), 'manage_options', 'icegram_mailer_dashboard', [ $this, 'show_dashboard' ] );
		add_submenu_page( 'icegram_mailer_dashboard', __( 'Settings', 'icegram-mailer' ), __( 'Settings', 'icegram-mailer' ), 'manage_options', 'icegram_mailer_settings', array( $settings, 'show_settings' ) );

		add_submenu_page( 'icegram_mailer_dashboard', __( 'Other awesome plugins', 'icegram-mailer' ), __( 'Other awesome plugins', 'icegram-mailer' ), 'manage_options', 'icegram_other_plugins', array( $this, 'show_icegram_other_plugins' ) );
	}

	public static function is_plugin_page() {
		$current_page = isset( $_GET['page'] ) ? sanitize_title( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$plugins_pages = self::get_plugins_pages();

		return false !== array_search( $current_page, $plugins_pages, true );
	}

	public static function get_plugins_pages() {

		$plugin_pages = [
			'icegram_mailer_dashboard',
			'icegram_mailer_settings',
			'icegram_other_plugins'
		];

		return $plugin_pages;
	}

	public function show_dashboard() {
		$ess_data            = Icegram_Mailer_Account::get_ess_data();
		$ess_onboarding_step = Icegram_Mailer_Onboarding_Controller::get_onboarding_step();
		$settings_url        = admin_url( 'admin.php?page=icegram_mailer_settings' );
		self::get_view(
			'dashboard',
			[
				'ess_data'            => $ess_data,
				'ess_onboarding_step' => $ess_onboarding_step,
				'settings_url'        => $settings_url,
			]
		);
	}

	public function show_icegram_other_plugins() {
		
		require_once plugin_dir_path( __FILE__ ) . 'templates/other-icegram-plugins.php'; 
	}
	

	/**
	 * Method to load admin views
	 *
	 * @since 5.5.4
	 *
	 * @param string $view View name.
	 * @param array  $imported_variables Passed variables.
	 * @param mixed  $path Path to view file.
	 */
	public static function get_view( $view, $imported_variables = array(), $path = false ) {

		if ( $imported_variables && is_array( $imported_variables ) ) {
			extract( $imported_variables ); // phpcs:ignore
		}

		$path = ICEGRAM_MAILER_PLUGIN_PATH . '/admin/partials/';

		include $path . $view . '.php';
	}

}
