<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Icegram_Mailer_Email_Delivery_Check extends Icegram_Mailer_Services {

	/**
	 * Service command
	 *
	 * @var string
	 *
	 * @sinc 4.6.0
	 */
	public $cmd = '/email/delivery/:mailbox';

	/**
	 * ES_Delivery_Check constructor.
	 *
	 * @since 4.6.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Test Email Delivery
	 *
	 * @return mixed
	 *
	 * @since 4.6.0
	 */
	public function test_email_delivery() {

		$mailbox = Icegram_Mailer_Common::get_test_email();

		$this->cmd = str_replace( ':mailbox', $mailbox, $this->cmd );

		$request_data     = array();
		$request_method   = 'GET';
		$validate_request = false;

		$response = $this->send_request( $request_data, $request_method, $validate_request );

		$res = array();
		if ( is_wp_error( $response ) ) {
			$res['status'] = 'error';
		} else {

			if ( ! empty( $response['status'] ) &&  'success' === $response['status'] && isset( $response['meta']['emailDelivered'] ) && true == $response['meta']['emailDelivered'] ) {
				$res['status'] = 'success';
			} else {
				$res['additional_message'] = __( ' The test email did not reach our test server. Did you get any test emails on your email? This could be a temporary problem, but it can also mean that emails are getting stuck on your server, or getting rejected by recipients.', 'icegram-mailer' );
				$res['status']             = 'error';
			}
		}

		return $res;
	}

}
