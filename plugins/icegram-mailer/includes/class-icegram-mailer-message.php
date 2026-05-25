<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Message' ) ) {
	/**
	 * Class Icegram_Mailer_Message
	 *
	 * @since 4.3.2
	 */
	class Icegram_Mailer_Message {
		
		/**
		 * Email Tracking ID
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $tracking_id = '';
		
		/**
		 * To email
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $to = '';

		/**
		 * To name
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $to_name = '';

		/**
		 * Message headers
		 *
		 * @var array
		 *
		 * @since 4.3.2
		 */
		public $headers = array();

		/**
		 * Message errors
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $error = '';

		/**
		 * Message subject
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $subject = '';

		/**
		 * Message body
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $body = '';

		/**
		 * Message text
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $body_text = '';

		/**
		 * Message From
		 *
		 * @var
		 *
		 * @sinc 4.3.2
		 */
		public $from;

		/**
		 * Message from name
		 *
		 * @var string
		 *
		 * @since 4.3.2
		 */
		public $from_name = '';

		/**
		 * Attachments for email
		 *
		 * @since 4.6.7
		 */
		public $attachments = array();

		
		/**
		 * Reply to name
		 *
		 * @since 1.0.2
		 */
		public $reply_to_name = '';

		/**
		 * Reply to email
		 *
		 * @since 1.0.2
		 */
		public $reply_to_email = '';

		/**
		 * Character set
		 *
		 * @since 4.6.7
		 */
		public $charset = '';

		public function __construct() {

		}

	}
}


