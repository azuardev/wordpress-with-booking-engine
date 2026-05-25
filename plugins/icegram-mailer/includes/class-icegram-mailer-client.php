<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Icegram_Mailer_Client' ) ) {
	/**
	 * Class Icegram_Mailer_Client
	 *
	 * @since 4.0.0
	 * @since 4.3.2 Modified structure. Made it OOP based
	 */
	class Icegram_Mailer_Client {
		/**
		 * Mailer setting
		 *
		 * @since 4.3.2
		 * @var object|ES_Base_Mailer
		 */
		public $mailer;

		/**
		 * ES_Mailer constructor.
		 *
		 * @since 4.3.2
		 */
		public function __construct() {
			if ( Icegram_Mailer_Account::is_opted_for_ess() ) {
				add_action( 'plugins_loaded', [ $this, 'set_mailer' ] );
				add_filter( 'pre_wp_mail', [ $this, 'send' ], 10, 2 );
				add_action( 'icegram_mailer_after_email_processed', [ $this, 'log_email' ], 10, 2 );
			}

			add_action( 'init', [ $this, 'maybe_track_email' ] );
		}

		public function set_mailer() {
			$mailer_class = $this->get_current_mailer_class();
			$mailer_obj   = new $mailer_class();
			$this->mailer = $mailer_obj;
		}

		public function get_current_mailer_class() {
			$malier_slug          = $this->get_current_mailer_slug();
			$current_mailer_class = 'Icegram_Mailer_' . ucfirst( $malier_slug ) . '_Mailer';

			return apply_filters( 'icegram_mailer_current_mailer_class', $current_mailer_class );
		}

		public function get_current_mailer_slug() {
			return 'ess';
		}


		public function is_open_tracking_enabled() {
			$settings = Icegram_Mailer_Settings_Controller::get_settings();
			return isset( $settings['is_open_tracking_enabled'] ) && 'yes' === $settings['is_open_tracking_enabled'];
		}

		public function build_message( $email ) {
			

			$ess_data = Icegram_Mailer_Account::get_ess_data();
			$settings = Icegram_Mailer_Settings_Controller::get_settings();

			$message  = new Icegram_Mailer_Message();

			$from_email       = ! empty( $ess_data['from_email'] ) ? $ess_data['from_email'] : '';
			$from_name        = ! empty( $ess_data['from_name'] ) ? $ess_data['from_name'] : get_bloginfo( 'name' );
			
			$reply_to_name  = ! empty( $settings['reply_to_name'] ) ? $settings['reply_to_name'] : '';
			$reply_to_email = ! empty( $settings['reply_to_email'] ) ? $settings['reply_to_email'] : get_option( 'admin_email' );			

			$headers          = ! empty( $email['headers'] ) ? $email['headers'] : [];
			$message_reply_to = $this->extract_reply_to_from_email_headers( $headers );

			$message->from           = $from_email;
			$message->from_name      = $from_name;
			$message->to             = $email['to'];
			$message->subject        = $email['subject'];
			$message->body           = $email['message'];
			$message->attachments    = ! empty( $email['attachments'] ) ? $email['attachments'] : [];
			$message->headers        = $headers;
			$message->reply_to_name  = ! empty( $message_reply_to['name'] ) ? $message_reply_to['name'] : $reply_to_name;
			$message->reply_to_email = ! empty( $message_reply_to['email'] ) ? $message_reply_to['email'] : $reply_to_email;						
			
			if ( $this->is_open_tracking_enabled() ) {

				$tracking_id = wp_generate_uuid4();

				$tracking_pixel = $this->get_tracking_pixel( $tracking_id );

				if ( false === strpos( $message->body, '<html' ) ) {
					$message->body = $message->body . $tracking_pixel;
				} else {
					$message->body = str_replace( '</body>', $tracking_pixel . '</body>', $message->body );
				}

				$message->tracking_id = $tracking_id;
			}

			return $message;
		}

		/**
		 * Extract Reply-To email headers using Regex
		 */
		public function extract_reply_to_from_email_headers( $email_headers ) {
			$reply_to = [];
			if ( is_string( $email_headers ) && ! empty( $email_headers ) && preg_match('/^Reply-To:\s*(.*)$/im', $email_headers, $matches)) {
				$reply_to_header = trim( $matches[1] );
				if (preg_match('/^(?:"?([^"]*)"?\s)?<?([\w.\-+]+@[\w.\-]+\.\w+)>?$/', $reply_to_header, $parts)) {
					$name  = isset( $parts[1]) ? trim( $parts[1] ) : '';
					$email = trim( $parts[2] );
					$reply_to['name']  = $name;
					$reply_to['email'] = $email;
				}
			}
			return $reply_to;
		}

		/**
		 * Get Tracking pixel
		 *
		 * @param array $data
		 *
		 * @return string
		 *
		 * @since 4.2.0
		 */
		public function get_tracking_pixel( $tracking_id ) {

			$tracking_image = '';

			$url_params = [
				'tracking_id' => $tracking_id,
				'action' => 'open',
			];

			$url = $this->prepare_url( $url_params );

			$tracking_image = "<img src='{$url}' width='1' height='1' alt=''/>"; // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage

			return $tracking_image;
		}

		/**
		 * Get link
		 *
		 * @param array $data
		 *
		 * @return string
		 *
		 * @since 4.0.0
		 * @since 4.2.0
		 * @since 4.3.2
		 */
		public function prepare_url( $url_params = array() ) {

			$action = ! empty( $url_params['action'] ) ? $url_params['action'] : '';

			$link = add_query_arg( 'icegram-mailer-action', $action, site_url( '/' ) );

			$url_params = icegram_mailer_encode_request_data( $url_params );

			$link = add_query_arg( 'hash', $url_params, $link );

			return $link;
		}

		/**
		 * Send email via ESS Mailer
		 *
		 * @param $sent
		 * @param array $email {
		 *      Array of the `wp_mail()` arguments.
		 *
		 * @type string|string[] $to Array or comma-separated list of email addresses to send message.
		 * @type string $subject Email subject.
		 * @type string $message Message contents.
		 * @type string|string[] $headers Additional headers.
		 * @type string|string[] $attachments Paths to files to attach.
		 *
		 * @return bool
		 * @throws Throwable
		 */
		public function send( $sent, array $email ) {

			try {

				$message = $this->build_message( $email );

				$response = $this->mailer->send( $message );

				$status        = '';
				$is_email_sent = false;
				
				if ( is_wp_error( $response ) ) {
					global $phpmailer;
					if ( ! is_object( $phpmailer ) || ! is_a( $phpmailer, 'PHPMailer' ) ) {
						$phpmailer = $this->get_phpmailer(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}
					$error_message        = $response->get_error_message();
					$message->error       = $error_message;
					$phpmailer->ErrorInfo = $error_message;
					$status               = 'failed';
					$is_email_sent        = false;
				} else {
					$is_email_sent = true;
					$status        = 'sent';
				}
				
				do_action( 'icegram_mailer_after_email_processed', $message, $status );
				return $is_email_sent;
			} catch ( Throwable $t ) {
				return $sent;
			}
		}

		public function get_test_email_subject( $email ) {
			/* translators: %s. Email address */
			$subject = 'Icegram Mailer: ' . sprintf( esc_html__( 'Test email to %s', 'icegram-mailer' ), $email );
			return $subject;
		}
	
		public function get_test_email_subject_content() {
			$content = $this->get_test_email_content();
			return $content;
		}
	
		/**
		 * Get test email content
		 *
		 * @return false|string
		 *
		 * @since 4.3.2
		 */
		public function get_test_email_content() {
			ob_start();
			$review_url = 'https://wordpress.org/support/plugin/icegram-mailer/reviews/';
			?>
			<html>
			<head></head>
			<body>
			<p><?php echo esc_html__( 'Congrats, test email was sent successfully!', 'icegram-mailer' ); ?></p>
			<p><?php echo esc_html__( 'Thank you for trying out Icegram Mailer. We are on a mission to make the best Email delivery plugin for WordPress.', 'icegram-mailer' ); ?></p>
			<p>
			<?php
				/* translators: 1: <a> 2: </a> */
				echo sprintf( esc_html__( 'If you find this plugin useful, please consider giving us %1$s5 stars review%2$s on WordPress!', 'icegram-mailer' ), '<a href="' . esc_url( $review_url ) . '">', '</a>' );
			?>
			</p>
			<p>Nirav Mehta</p>
			<p>Founder, <a href="https://www.icegram.com/">Icegram</a></p>
			</body>
			</html>
	
			<?php
			$content = ob_get_clean();
	
			return $content;
		}

		/**
		 * Get default phpmailer
		 *
		 * @return PHPMailer
		 *
		 * @since 4.7.7
		 */
		public function get_phpmailer() {

			global $wp_version;

			if ( version_compare( $wp_version, '5.5', '<' ) ) {
				require_once ABSPATH . WPINC . '/class-phpmailer.php';
				require_once ABSPATH . WPINC . '/class-smtp.php';
			} else {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

				// Check if PHPMailer class already exists before creating an alias for it.
				if ( ! class_exists( 'PHPMailer' ) ) {
					class_alias( PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer' );
				}

				// Check if phpmailerException class already exists before creating an alias for it.
				if ( ! class_exists( 'phpmailerException' ) ) {
					class_alias( PHPMailer\PHPMailer\Exception::class, 'phpmailerException' );
				}

				// Check if SMTP class already exists before creating an alias for it.
				if ( ! class_exists( 'SMTP' ) ) {
					class_alias( PHPMailer\PHPMailer\SMTP::class, 'SMTP' );
				}
			}

			$phpmailer          = new PHPMailer( true );
			$phpmailer->CharSet = 'UTF-8';

			return $phpmailer;
		}

		public function log_email( $message, $status ) {
			icegram_mailer()->email_logs_table->insert(
				array(
					'tracking_id' => $message->tracking_id,
					'to'          => is_array( $message->to ) ? implode( ',', $message->to ) : $message->to,
					'subject'     => $message->subject,
					'headers'     => is_array( $message->headers ) ? wp_json_encode( $message->headers ): $message->headers,
					'attachments'     => ! empty( $message->attachments ) ? wp_json_encode( $message->attachments ): '',
					'body'        => $message->body,
					'status'      => $status,
					'error'       => $message->error,
				)
			);
		}

		public function maybe_track_email() {
			$action = isset( $_GET['icegram-mailer-action'] ) ? sanitize_text_field( wp_unslash( $_GET['icegram-mailer-action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$hash   = isset( $_GET['hash'] ) ? sanitize_text_field( wp_unslash( $_GET['hash'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' === $action || '' === $hash ) {
				return;
			}
			
			$data = icegram_mailer_decode_request_data( $hash );
			if ( empty( $data['tracking_id'] ) ) {
				return;
			}

			$tracking_id = $data['tracking_id'];

			if ( 'open' === $action ) {
				$this->track_open( $tracking_id );
			}
		}

		public function track_open( $tracking_id ) {

			icegram_mailer()->email_logs_table->update( $tracking_id, [ 'opened_at' => icegram_mailer_get_current_gmt_timestamp() ], 'tracking_id' );

			// Output a transparent 1x1 pixel
			header('Content-Type: image/png');
			echo esc_html( base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/wcAAwAB/UVu6egAAAAASUVORK5CYII=') );
			exit;
		}
	}
}
