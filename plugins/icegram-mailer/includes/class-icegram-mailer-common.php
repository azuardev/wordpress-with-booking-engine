<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Common' ) ) {

	/**
	 * Class Email_General.
	 *
	 * @since 4.0
	 */
	class Icegram_Mailer_Common {

		/**
         * Free plan limit threshold (for branding control)
         * Accounts with allocated limit <= 200 are considered free plan
         * 
         * @since 5.7.0
         */
        const FREE_PLAN_LIMIT_THRESHOLD = 200; 

		/**
		 * Get mailbox name
		 *
		 * @return string
		 *
		 * @since 4.6.0
		 */
		public static function get_test_email() {
			$mailbox_user = get_option( 'icegram_mailer_test_mailbox_user', '' );

			if ( empty( $mailbox_user ) ) {
				$mailbox_user = self::generate_test_mailbox_user();
				update_option( 'icegram_mailer_test_mailbox_user', $mailbox_user );
			}

			return $mailbox_user . '@box.icegram.com';
		}

		/**
		 * Generate test mailbox user
		 *
		 * @return string
		 *
		 * @since 4.6.0
		 */
		public static function generate_test_mailbox_user() {

			$admin_email = get_bloginfo( 'admin_email' );

			$parts = explode( '@', $admin_email );

			if ( count( $parts ) > 0 ) {
				$user = $parts[0];
			} else {
				$user = 'test';
			}

			$blog_url = get_bloginfo( 'url' );

			// If URI is like, eg. www.way2tutorial.com/
			$blog_url = trim( $blog_url, '/' );

			// If not have http:// or https:// then prepend it
			if ( ! preg_match( '#^http(s)?://#', $blog_url ) ) {
				$blog_url = 'http://' . $blog_url;
			}

			$url_parts = wp_parse_url( $blog_url );

			// Remove www.
			$domain = preg_replace( '/^www\./', '', $url_parts['host'] );

			$hash = self::generate_hash( 5 );

			return $hash . '_' . $user . '_' . $domain;
		}

		/**
		 * Generate Hash
		 *
		 * @param $length
		 *
		 * @return false|string
		 *
		 * @since 4.2.4
		 */
		public static function generate_hash( $length ) {

			$length = ( $length ) ? $length : 12;

			return substr( md5( uniqid() . uniqid() . wp_rand( $length, 64 ) ), 0, $length );
		}

		/**
		 * Send a sign up request to ES installed on IG site.
		 * 
		 * @since 5.3.12
		 * 
		 * @param array $request_data
		 */
		public static function send_ig_sign_up_request( $request_data = array() ) {

			$response = array(
				'status' => 'error',
			);
			
			$name  = ! empty( $request_data['name'] ) ? $request_data['name']  : '';
			$email = ! empty( $request_data['email'] ) ? $request_data['email']: '';
			$lists = ! empty( $request_data['lists'] ) ? $request_data['lists']: array();
			$list  = ! empty( $request_data['list'] ) ? $request_data['list']  : '';

			if ( is_email( $email ) ) {

				$url_params = array(
					'ig_es_external_action' => 'subscribe',
					'name'                  => $name,
					'email'                 => $email,
				);

				if ( ! empty( $lists ) ) {
					$url_params['lists'] = $lists;
				}

				if ( ! empty( $list ) ) {
					$url_params['list'] = $list;
				}

				$ip_address = icegram_mailer_get_ip();
				if ( ! empty( $ip_address ) && 'UNKNOWN' !== $ip_address ) {
					$url_params['ip_address'] = $ip_address;
				}

				$ig_es_url = 'https://icegram.com/';
				$ig_es_url = add_query_arg( $url_params, $ig_es_url );

				$request_args = array(
					'timeout' => 30
				);

				// Make a get request.
				$api_response = wp_remote_get( $ig_es_url, $request_args );
				if ( ! is_wp_error( $api_response ) ) {
					$body = ! empty( $api_response['body'] ) && icegram_mailer_is_valid_json( $api_response['body'] ) ? json_decode( $api_response['body'], true ) : '';
					if ( ! empty( $body ) ) {
						// If we have received an id in response then email is successfully queued at mailgun server.
						if ( ! empty( $body['status'] ) && 'SUCCESS' === $body['status'] ) {
							$response['status'] = 'success';
						} elseif ( ! empty( $body['status'] ) && 'ERROR' === $body['status'] ) {
							$response['status']       = 'error';
							$response['message']      = $body['message'];
							$response['message_text'] = $body['message_text'];
						}
					} else {
						$response['status'] = 'success';
					}
				} else {
					$response['status'] = 'error';
				}
			}

			return $response;
		}

		/**
		 * Get utm tracking url
		 *
		 * @param array $utm_args
		 *
		 * @return mixed|string
		 *
		 * @since 4.4.5
		 */
		public static function get_utm_tracking_url( $utm_args = array() ) {

			$url          = ! empty( $utm_args['url'] ) ? $utm_args['url'] : 'https://www.icegram.com/mailer/';
			$utm_source   = ! empty( $utm_args['utm_source'] ) ? $utm_args['utm_source'] : 'in_app';
			$utm_medium   = ! empty( $utm_args['utm_medium'] ) ? $utm_args['utm_medium'] : '';
			$utm_campaign = ! empty( $utm_args['utm_campaign'] ) ? $utm_args['utm_campaign'] : 'ess_upsell';

			if ( ! empty( $utm_source ) ) {
				$url = add_query_arg( 'utm_source', $utm_source, $url );
			}

			if ( ! empty( $utm_medium ) ) {
				$url = add_query_arg( 'utm_medium', $utm_medium, $url );
			}

			if ( ! empty( $utm_campaign ) ) {
				$url = add_query_arg( 'utm_campaign', $utm_campaign, $url );
			}

			return $url;

		}

		/**
         * Get allowed HTML tags for wp_kses
         *
         * @return array Allowed HTML tags and attributes.
         */
        public static function get_allowed_html_for_emails() {
            return array(
                'strong' => array(
                    'style' => true,
                ), 
            );
        }

		 /**
		 * Check if current plan is free (lite) based on allocated limit
		 * 
		 * Lite plan: allocated limit <= 200
		 * Premium plan (pro/max): allocated limit > 200
		 * 
		 * @return bool True if free plan (lite), false if premium (pro/max)
		 * @since 5.7.0
		 */
		public static function is_free_plan_by_limit() {

			static $is_free_plan = null;
		
			if ( null === $is_free_plan ) {
				$ess_data = Icegram_Mailer_Account::get_ess_data();
				$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit'] : 0;
				$is_free_plan = ( $allocated_limit <= self::FREE_PLAN_LIMIT_THRESHOLD );
			}

			return $is_free_plan;
		}
	}
}
