<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Icegram_Mailer_Services {

	/**
	 * API URL
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public $api_url = 'https://api.icegram.com';

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 4.6.0
	 */
	public $cmd = '';

	/**
	 * Icegram_Mailer_Services constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {

	}

	/**
	 * Send Request
	 *
	 * @param array  $options
	 * @param string $method
	 *
	 * @since 4.6.0
	 */
	public function send_request( $options = array(), $method = 'POST', $validate_request = true ) {

		$response = array();

		if ( empty( $this->cmd ) ) {
			return new WP_Error( '404', 'Command Not Found' );
		}


		$url = $this->api_url . $this->cmd;

		$options = [
			'timeout' => 30
		];

		if ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $options );
		} else {
			$response = wp_remote_get( $url, $options );
		}

		if ( ! is_wp_error( $response ) ) {

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

				$response_data = $response['body'];

				if ( 'error' != $response_data ) {

					$response_data = json_decode( $response_data, true );

					do_action( 'icegram_mailer_service_response_received', $response_data, $options );

					return $response_data;
				}
			}
		}

		return $response;

	}
}
