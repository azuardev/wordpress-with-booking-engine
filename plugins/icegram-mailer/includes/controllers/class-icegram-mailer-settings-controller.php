<?php

if ( ! class_exists( 'Icegram_Mailer_Settings_Controller' ) ) {

	class Icegram_Mailer_Settings_Controller {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
		protected static $instance = null;

		private static $settings_option_name = 'icegram_mailer_settings';
	
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		public static function send_test_email( $data ) {

			$test_email = isset( $data['test_email'] ) ? sanitize_email( $data['test_email'] ) : '';
			if ( ! is_email( $test_email ) ) {
				$response['status']  = 'error';
				$response['message'] = __( 'Email is invalid.', 'icegram-mailer' );
				return $response;
			}

			$subject = icegram_mailer()->client->get_test_email_subject( $test_email );
			$charset = get_bloginfo( 'charset' );
			$content = icegram_mailer()->client->get_test_email_content();
			$headers = array(
			'Content-Type: text/html; charset="' . $charset . '"',
			);
			$email_sent = wp_mail( $test_email, $subject, $content, $headers );
			if ( $email_sent ) {
				$response['status'] = 'success';
			} else {
				global $phpmailer;

				if ( is_object( $phpmailer ) && $phpmailer->ErrorInfo ) {
					$message = wp_strip_all_tags( $phpmailer->ErrorInfo );
				} else {
					$message = __( 'WP Mail Error: Unknown', 'icegram-mailer' );
				}

				$response['status']  = 'error';
				$response['message'] = $message;
			}

			return $response;
		}


		public static function save_settings( $new_settings ) {
			$ess_data             = Icegram_Mailer_Account::get_ess_data();
			$is_opted_for_ess     = ! empty( $new_settings['is-opted-for-ess'] ) ? 'yes' : 'no';
			$from_name            = ! empty( $new_settings['from-name'] ) ? $new_settings['from-name'] : get_option( 'blogname', '' );

			$ess_data['from_name']  = ! empty( $from_name ) ? $from_name : $ess_data['from_name'];

			$settings = self::get_settings();

			$settings['is_open_tracking_enabled'] = isset( $new_settings['is_open_tracking_enabled'] ) ? $new_settings['is_open_tracking_enabled'] : 'no';
		   	
			$settings['reply_to_name']  = isset( $new_settings['reply-to-name'] ) ? sanitize_text_field( $new_settings['reply-to-name'] ) : '';
			
			$reply_to_email_input = isset( $new_settings['reply-to-email'] ) ? sanitize_email( $new_settings['reply-to-email'] ) : '';
			if ( ! empty( $reply_to_email_input ) && is_email( $reply_to_email_input ) ) {
				$settings['reply_to_email'] = $reply_to_email_input;
			} else {
				$settings['reply_to_email'] = ''; 
			}

		    // Update branding option separately
		    // For free plan users, branding is always kept enabled
			$is_free_plan = Icegram_Mailer_Common::is_free_plan_by_limit();

			if ( $is_free_plan ) {
				update_option( 'icegram_mailer_branding_enabled', 'yes', false );
			} else {
				$branding_enabled = isset( $new_settings['branding_enabled'] ) ? 'yes' : 'no';
				update_option( 'icegram_mailer_branding_enabled', $branding_enabled, false );
			}

			update_option( self::$settings_option_name, $settings );

			Icegram_Mailer_Account::update_ess_data( $ess_data );
			Icegram_Mailer_Account::update_ess_status_option( $is_opted_for_ess );
		}

		public static function get_settings() {
			$settings = get_option( self::$settings_option_name, [] );
			return $settings;
		}

		public static function get_settings_view_args() {
			$settings                 = self::get_settings();
			$is_open_tracking_enabled = ! empty( $settings['is_open_tracking_enabled'] ) ? $settings['is_open_tracking_enabled'] : 'no';
			$is_opted_for_ess         = Icegram_Mailer_Account::is_opted_for_ess();
			$ess_data                 = Icegram_Mailer_Account::get_ess_data();
			$from_name                = ! empty( $ess_data['from_name'] ) ? $ess_data['from_name'] : get_option( 'blogname', '' );
			$from_email               = ! empty( $ess_data['from_email'] ) ? $ess_data['from_email'] : '';
			$from_email_user_name     = ! empty( $from_email ) ? icegram_mailer_get_user_name_from_email( $from_email ) : '';
			$dashboard_url            = admin_url( 'admin.php?page=icegram_mailer_dashboard' );
			$test_email               = get_option( 'admin_email' );
			$current_user 			  = wp_get_current_user();
			$test_email   			  = ! empty( $current_user->user_email ) ? $current_user->user_email : get_option( 'admin_email' );
			$is_free_plan             = Icegram_Mailer_Common::is_free_plan_by_limit();
			$reply_to_name            = ! empty( $settings['reply_to_name'] ) ? $settings['reply_to_name'] : '';
    		$reply_to_email           = ! empty( $settings['reply_to_email'] ) ? $settings['reply_to_email'] : '';
    
			if ( $is_free_plan ) {
				$branding_enabled = 'yes';
			} else {
				$branding_enabled = get_option( 'icegram_mailer_branding_enabled', 'yes' );
			}

			return [
			'is_opted_for_ess'         => $is_opted_for_ess,
			'from_name'                => $from_name,
			'from_email_user_name'     => $from_email_user_name,
			'is_open_tracking_enabled' => $is_open_tracking_enabled,
			'dashboard_url'            => $dashboard_url,
			'test_email'               => $test_email,
			'is_free_plan'             => $is_free_plan,
			'branding_enabled'         => $branding_enabled,
			'reply_to_name'            => $reply_to_name,
        	'reply_to_email'           => $reply_to_email,        
			];
		}

	}
}

Icegram_Mailer_Settings_Controller::get_instance();
