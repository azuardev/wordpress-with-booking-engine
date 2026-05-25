<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Settings' ) ) {

	class Icegram_Mailer_Settings {

		public function __construct() {
			add_action( 'admin_init', [ $this, 'maybe_save_settings' ] );
		}

		public function maybe_save_settings() {

			if ( isset( $_POST['icegram-mailer-form-submitted'] ) ) {

				check_admin_referer( 'icegram-mailer-save-settings' );
				
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'Unauthorized', 'icegram-mailer' ) );
				}
		
				$settings = isset( $_POST['icegram-mailer-settings'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['icegram-mailer-settings'] ) ) : [];
		
				Icegram_Mailer_Settings_Controller::save_settings( $settings );
		
				$settings_url = admin_url( 'admin.php?page=icegram_mailer_settings&settings-updated' );
				wp_safe_redirect( $settings_url );
				exit();
			}
		}

		public function show_settings() {

			if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
				$dashboard_page_url = admin_url( 'admin.php?page=icegram_mailer_dashboard' );
				wp_safe_redirect( $dashboard_page_url );
				exit();
			}

			$args = Icegram_Mailer_Settings_Controller::get_settings_view_args();
		
			Icegram_Mailer_Admin::get_view( 'settings', $args );
		}

	}
}
