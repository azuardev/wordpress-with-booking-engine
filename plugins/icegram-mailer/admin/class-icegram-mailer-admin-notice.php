<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Admin Notice Framework with Dismissal Capability
 */
class Icegram_Mailer_Admin_Notice {

	private $notice_id;
	private $notice_text;
	private $notice_type;
	private $capability;
	private $allowed_pages;

	/**
	 * Class constructor
	 * 
	 * @param string $notice_id    Unique ID for the notice
	 * @param string $notice_text  HTML content of the notice
	 * @param string $notice_type  Type of notice (success|error|warning|info)
	 * @param string $capability   User capability required to see the notice
	 */
	public function __construct( $notice_id, $notice_text, $notice_type = 'info', $capability = 'manage_options', $allowed_pages = array() ) {
		$this->notice_id     = sanitize_key($notice_id);
		$this->notice_text   = $notice_text;
		$this->notice_type   = $notice_type;
		$this->capability    = $capability;
		$this->allowed_pages = $allowed_pages;

		$this->init();
	}

	private function init() {
		add_action('admin_notices', [$this, 'display_es_plugin_notice']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_dismiss_script']);
		add_action("wp_ajax_dismiss_{$this->notice_id}_notice", [$this, 'handle_dismiss']);
	}

	public function display_es_plugin_notice() {
		if ( ! $this->should_display_notice() ) {
return;
		}

		echo '<div class="icegram-mailer-admin-notice notice notice-' . esc_attr($this->notice_type) . ' is-dismissible" data-notice-id="' . esc_attr($this->notice_id) . '">';
		echo '<p>' . wp_kses_post( $this->notice_text ) . '</p>';
		echo '</div>';
		do_action( 'icegram_mailer_notice_displayed' );
		do_action( 'icegram_mailer_notice_displayed_' . $this->notice_id );
	}

	public function enqueue_dismiss_script() {
		if (!$this->should_display_notice()) {
			return;
		}
	}

	public function handle_dismiss() {
		check_ajax_referer('icegram-mailer-admin-ajax-nonce', 'security');
		
		if (!current_user_can($this->capability)) {
			wp_die(-1, 403);
		}

		update_user_meta(get_current_user_id(), "icegram_mailer_dismissed_{$this->notice_id}_notice", true);
		wp_send_json_success();
	}

	private function should_display_notice() {
		if (!current_user_can($this->capability)) {
			return false;
		}

		if ( did_action( 'icegram_mailer_notice_displayed' ) ) {
			return false;
		}

		if ( ! Icegram_Mailer_Admin::is_plugin_page() ) {
			return false;
		}

		
		if ( get_user_meta(get_current_user_id(), "icegram_mailer_dismissed_{$this->notice_id}_notice", true) ) {
			return false;
		}

		return apply_filters( "icegram_mailer_should_display_{$this->notice_id}_notice", true );
	}
}
