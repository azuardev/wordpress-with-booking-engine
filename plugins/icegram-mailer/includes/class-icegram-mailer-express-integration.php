<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Express_Integration' ) ) {
	class Icegram_Mailer_Express_Integration {

		public function __construct() {
			$this->register_express_ess_hooks();
		}

		private function register_express_ess_hooks() {
			add_filter( 'ig_es_ess_data_option', [ $this, 'override_express_ess_data_option' ] );
			add_filter( 'ig_es_current_mailer_class', [ $this, 'override_express_ess_mailer' ] );
			add_action( 'icegram_mailer_plugin_activated', [ $this, 'migrate_express_ess_settings_into_mailer' ] );
		}

		public function override_express_ess_data_option( $ess_data_option ) {
			return Icegram_Mailer_Account::get_ess_data_option();
		}

		public function override_express_ess_mailer( $express_mailer_class ) {
			/**
			 * ES_Icegram_Mailer: ESS Mailer class in Express plugin
			 * Icegram_Mailer_ESS_Mailer: ESS Mailer class in Icegram mailer plugin
			 * 
			 * Switch to Icegram Mailer only if ESS Mailer used in Express
			 */
			if ( 'ES_Icegram_Mailer' === $express_mailer_class ) {
				$express_mailer_class = 'Icegram_Mailer_ESS_Mailer';
			}
			return $express_mailer_class;
		}

		public function migrate_express_ess_settings_into_mailer() {
			$express_mailer_settings_mappings = [
				'ig_es_ess_onboarding_step' => 'icegram_mailer_onboarding_step',
				'ig_es_ess_data' => 'icegram_mailer_ess_data',
				'ig_es_ess_opted_for_sending_service' => 'icegram_mailer_opted_for_sending_service',
				'ig_es_ess_status' => 'icegram_mailer_status',
				'ig_es_ess_onboarding_complete' => 'icegram_mailer_onboarding_complete',
			];

			foreach ( $express_mailer_settings_mappings as $express_option_name => $mailer_option_name ) {
				$express_option_value = get_option( $express_option_name );
				if ( ! empty( $express_option_value ) && empty( get_option( $mailer_option_name ) ) ) {
					update_option( $mailer_option_name, $express_option_value );
				}
			}
		}
		
	}
}

new Icegram_Mailer_Express_Integration();
