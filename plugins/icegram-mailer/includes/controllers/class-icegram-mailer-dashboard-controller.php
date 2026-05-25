<?php

if ( ! class_exists( 'Icegram_Mailer_Dashboard_Controller' ) ) {

	class Icegram_Mailer_Dashboard_Controller {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
		protected static $instance = null;


		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public static function get_lists( $args = array()) {

			$do_count_only = ! empty( $args['do_count_only'] );
			return icegram_mailer()->email_logs_table->get_logs( $args, ARRAY_A, $do_count_only );

		}

		/**
		 * Resend email from logs
		 *
		 * @param array $data Contains email_id.
		 * @return array Response with status and message.
		 * @since 1.0.8
		 */
		public static function resend_email( $data ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to resend email.', 'icegram-mailer' ),
			);

			// Validate email ID.
			$email_id = isset( $data['email_id'] ) ? absint( $data['email_id'] ) : 0;
			if ( empty( $email_id ) ) {
				$response['message'] = __( 'Invalid email ID.', 'icegram-mailer' );
				return $response;
			}

			// Get email from logs.
			$email_log = icegram_mailer()->email_logs_table->get( $email_id );

			if ( empty( $email_log ) ) {
				$response['message'] = __( 'Email not found in logs.', 'icegram-mailer' );
				return $response;
			}

			// Prepare email data for resending.
			$to      = $email_log['to'];
			$subject = $email_log['subject'];
			$body    = $email_log['body'];
			$headers = ! empty( $email_log['headers'] ) && icegram_mailer_is_valid_json( $email_log['headers'] )
				? json_decode( $email_log['headers'], true )
				: $email_log['headers'];
			$attachments = ! empty( $email_log['attachments'] ) && icegram_mailer_is_valid_json( $email_log['attachments'] )
				? json_decode( $email_log['attachments'], true )
				: array();

			// Build message.
			$email = array(
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $body,
				'headers'     => $headers,
				'attachments' => $attachments,
			);

			$message = icegram_mailer()->client->build_message( $email );

			// Store the original tracking ID to update the same log entry.
			$original_tracking_id = $email_log['tracking_id'];
			$message->tracking_id = $original_tracking_id;

			// Temporarily disable logging of new entry.
			remove_action( 'icegram_mailer_after_email_processed', array( icegram_mailer()->client, 'log_email' ), 10 );

			// Send email.
			$mailer = new Icegram_Mailer_ESS_Mailer();
			$result = $mailer->send( $message );

			// Re-enable logging.
			add_action( 'icegram_mailer_after_email_processed', array( icegram_mailer()->client, 'log_email' ), 10, 2 );

			if ( ! is_wp_error( $result ) ) {
				// Update the existing log entry.
				icegram_mailer()->email_logs_table->update(
					$email_id,
					array(
						'status'     => 'sent',
						'error'      => '',
						'updated_at' => icegram_mailer_get_current_gmt_timestamp(),
					)
				);

				$response['status']  = 'success';
				$response['message'] = __( 'Email resent successfully!', 'icegram-mailer' );
			} else {
				// Update with new error if resend also failed.
				icegram_mailer()->email_logs_table->update(
					$email_id,
					array(
						'status'     => 'failed',
						'error'      => $result->get_error_message(),
						'updated_at' => icegram_mailer_get_current_gmt_timestamp(),
					)
				);

				$response['message'] = $result->get_error_message();
			}

			return $response;
		}

		/**
		 * Get email details for preview
		 *
		 * @param array $data Contains email_id.
		 * @return array Response with email details.
		 * @since 1.0.8
		 */
		public static function get_email_details( $data ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to retrieve email.', 'icegram-mailer' ),
			);

			$email_id = isset( $data['email_id'] ) ? absint( $data['email_id'] ) : 0;
			if ( empty( $email_id ) ) {
				return $response;
			}

			$email_log = icegram_mailer()->email_logs_table->get( $email_id );

			if ( ! empty( $email_log ) ) {
				$response['status'] = 'success';
				$response['data']   = $email_log;
			}

			return $response;
		}

		/**
		 * Sync account statistics from ESS API
		 *
		 * @param array $data Request data (currently unused).
		 * @return array Response with status, message, and calculated data.
		 * @since 1.0.9
		 */
		public static function sync_account_stats( $data = array() ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to sync account statistics.', 'icegram-mailer' ),
			);

			// Check if ESS account is created
			if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
				$response['message'] = __( 'ESS account not found. Please setup your account first.', 'icegram-mailer' );
				return $response;
			}

			// Fetch and update ESS limit
			try {
				$limit_updated = Icegram_Mailer_Account::fetch_and_update_ess_limit();
				
				if ( ! $limit_updated ) {
					$response['message'] = __( 'Failed to refresh usage data.', 'icegram-mailer' );
					return $response;
				}

				// Get the updated ESS data
				$ess_data = get_option( 'icegram_mailer_ess_data', array() );
				
				// Calculate all values server-side (same logic as dashboard.php)
				$allocated_limit = isset( $ess_data['allocated_limit'] ) ? absint( $ess_data['allocated_limit'] ) : 0;
				$used_limit      = 0;
				
				// Get current month's usage
				if ( ! empty( $ess_data['used_limit'] ) && is_array( $ess_data['used_limit'] ) ) {
					$current_month_key = icegram_mailer_get_current_month();
					$used_limit        = isset( $ess_data['used_limit'][ $current_month_key ] ) ? absint( $ess_data['used_limit'][ $current_month_key ] ) : 0;
				}
				
				// Calculate percentages and remaining limit
				$percentage_used      = $allocated_limit > 0 ? ( ( $used_limit * 100 ) / $allocated_limit ) : 0;
				$remaining_limit      = $allocated_limit > 0 ? $allocated_limit - $used_limit : 0;
				$percentage_remaining = 100 - $percentage_used;
				
				// Determine progress bar color class
				$ig_mailer_progress_bar_class = 'bg-indigo-600';
				if ( $percentage_used >= 100 ) {
					$ig_mailer_progress_bar_class = 'bg-red-600';
				} elseif ( $percentage_used >= 80 ) {
					$ig_mailer_progress_bar_class = 'bg-orange-600';
				} elseif ( $percentage_used >= 50 ) {
					$ig_mailer_progress_bar_class = 'bg-yellow-600';
				}
				
				// Format the next reset date
				$next_reset_formatted = '';
				if ( ! empty( $ess_data['next_reset'] ) ) {
					$next_reset_formatted = date_i18n( 'F j, Y g:i a', strtotime( $ess_data['next_reset'] ) );
				}
				
				// Format display strings (same as in template)
				$email_sent_text = sprintf(
					/* translators: 1. Used limit count 2. Allocated limit 3. Used limit percentage */
					__( '%1$s of %2$s ( %3$s%% ) used', 'icegram-mailer' ),
					number_format_i18n( $used_limit ),
					number_format_i18n( $allocated_limit ),
					number_format_i18n( $percentage_used, 2 )
				);
				
				$remaining_text = sprintf(
					/* translators: 1. Remaining limit count 2. Remaining limit percentage */
					__( '%1$s ( %2$s%% ) remaining', 'icegram-mailer' ),
					number_format_i18n( $remaining_limit ),
					number_format_i18n( $percentage_remaining, 2 )
				);
				
				$response['status']  = 'success';
				$response['message'] = __( 'Account statistics synced successfully!', 'icegram-mailer' );
				$response['data']    = array(
					'allocated_limit'      => $allocated_limit,
					'used_limit'           => $used_limit,
					'percentage_used'      => $percentage_used,
					'remaining_limit'      => $remaining_limit,
					'percentage_remaining' => $percentage_remaining,
					'progress_bar_class'   => $ig_mailer_progress_bar_class,
					'next_reset'           => $next_reset_formatted,
					'email_sent_text'      => $email_sent_text,
					'remaining_text'       => $remaining_text,
				);
				
			} catch ( Exception $e ) {
				$response['message'] = $e->getMessage();

			}

			return $response;
		}

		/**
		 * Delete email from logs
		 *
		 * @param array $data Contains email_id.
		 * @return array Response with status and message.
		 * @since 1.0.8
		 */
		public static function delete_email( $data ) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Unable to delete email.', 'icegram-mailer' ),
			);

			// Validate email ID.
			$email_id = isset( $data['email_id'] ) ? absint( $data['email_id'] ) : 0;
			if ( empty( $email_id ) ) {
				$response['message'] = __( 'Invalid email ID.', 'icegram-mailer' );
				return $response;
			}

			// Delete email from logs.
			$deleted = icegram_mailer()->email_logs_table->delete( $email_id );

			if ( $deleted ) {
				$response['status']  = 'success';
				$response['message'] = __( 'Email deleted successfully!', 'icegram-mailer' );
			} else {
				$response['message'] = __( 'Email not found or could not be deleted.', 'icegram-mailer' );
			}

			return $response;
		}


	}
}

Icegram_Mailer_Dashboard_Controller::get_instance();
