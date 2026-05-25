<?php

if ( ! class_exists( 'Icegram_Mailer_Onboarding_Controller' ) ) {

	class Icegram_Mailer_Onboarding_Controller {

	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
		protected static $instance = null;

	/**
	 * Added Logger Context
	 *
	 * @since 4.6.0
	 * @var array
	 */
		protected static $logger_context = array(
		'source' => 'icegram_mailer_onboarding',
	);

	/**
	 * API URL
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public static $api_url = 'https://api.igeml.com/'; /**
	 * Service command
	 *
	 * @var string
	 *
	 * @since 4.6.1
	 */
	public $cmd = 'accounts/register';

	

	/**
	 * Option name for current task name.
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $onboarding_current_task_option = 'icegram_mailer_onboarding_current_task';

	/**
	 * Option name which holds common data between tasks.
	 *
	 * E.g. created subscription form id from create_default_subscription_form function so we can use it in add_widget_to_sidebar
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $onboarding_tasks_data_option = 'icegram_mailer_onboarding_tasks_data';

	/**
	 * Option name which holds tasks which are done.
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $onboarding_tasks_done_option = 'icegram_mailer_onboarding_tasks_done';

	/**
	 * Option name which holds tasks which are failed.
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $onboarding_tasks_failed_option = 'icegram_mailer_onboarding_tasks_failed';

	/**
	 * Option name which holds tasks which are skipped due to dependency on other tasks.
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $onboarding_tasks_skipped_option = 'icegram_mailer_onboarding_tasks_skipped';

	/**
	 * Option name which store the step which has been completed.
	 *
	 * @since 4.6.0
	 * @var string
	 */
		public static $onboarding_step_option = 'icegram_mailer_onboarding_step';

	/**
	 * Icegram_Mailer_Onboarding_Controller constructor.
	 *
	 * @since 4.6.1
	 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
		}

		public function init() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

	/**
	 * Variable to hold all onboarding tasks list.
	 * 
	 * UPDATE : Added ess cron scheduling in 5.6.11
	 *
	 * @since 4.6.0
	 * @var array
	 */
		private static $all_onboarding_tasks = array(
		'configuration_tasks' => array(
			'create_ess_account',
			'set_sending_service_consent',
			'subscribe_to_es',	
		),
		'email_delivery_check_tasks' => array(
			'dispatch_emails_from_server',
			'check_test_email_on_server',
		),
		'completion_tasks' => array(
			'complete_ess_onboarding',
		),
	);


   /**
	 * Register the JavaScript for ESS onboarding
	 */
		public function enqueue_scripts() {

			if ( ! Icegram_Mailer_Admin::is_plugin_page() ) {
				return;
			}
			wp_register_script( 'icegram-mailer-sending-service-js', ICEGRAM_MAILER_PLUGIN_URL . '/admin/js/ess-onboarding.js', array( 'jquery' ), ICEGRAM_MAILER_VERSION, true );
			wp_enqueue_script( 'icegram-mailer-sending-service-js' );
			$onboarding_data                  = $this->get_onboarding_data();
			$onboarding_data['next_task']     = $this->get_next_onboarding_task();
			$onboarding_data['error_message'] = __( 'An error occured. Please try again later.', 'icegram-mailer' );
			$onboarding_data['security']      = wp_create_nonce( 'icegram-mailer-admin-ajax-nonce' );
			wp_localize_script( 'icegram-mailer-sending-service-js', 'icegram_mailer_ess_onboarding_data', $onboarding_data );
		}

	/**
	 * Method to get next task for onboarding.
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
		public function get_next_onboarding_task() {
			$all_onboarding_tasks = self::$all_onboarding_tasks;
			$current_task         = get_option( self::$onboarding_current_task_option, '' );

			// Variable to hold tasks list without any grouping.
			$onboarding_tasks = array();
			foreach ( $all_onboarding_tasks as $task_group => $grouped_tasks ) {
				foreach ( $grouped_tasks as $task ) {
					$onboarding_tasks[] = $task;
				}
			}

			$next_task = '';
			if ( ! empty( $current_task ) ) {
				$current_task_index = array_search( $current_task, $onboarding_tasks, true );
				if ( ! empty( $current_task_index ) ) {

					$next_task_index = $current_task_index + 1;
					$next_task       = ! empty( $onboarding_tasks[ $next_task_index ] ) ? $onboarding_tasks[ $next_task_index ] : '';

					// Check if previous required tasks are completed then only return next task else return blank task.
					if ( ! self::is_required_tasks_completed( $next_task ) ) {
						$next_task = '';
					}
				}
			}

			return $next_task;
		}

	/**
	 * Method to get the onboarding data options used in onboarding process.
	 *
	 * @since 4.6.0
	 */
		public function get_onboarding_data_options() {

			$onboarding_options = array(
			self::$onboarding_tasks_done_option,
			self::$onboarding_tasks_failed_option,
			self::$onboarding_tasks_data_option,
			self::$onboarding_tasks_skipped_option,
			self::$onboarding_step_option,
			self::$onboarding_current_task_option,
			);

			return $onboarding_options;
		}

	/**
	 * Method to get saved onboarding data.
	 *
	 * @since 4.6.0
	 */
		public function get_onboarding_data() {

			$onboarding_data = array();

			$onboarding_options = $this->get_onboarding_data_options();

			foreach ( $onboarding_options as $option ) {
				$option_data                = get_option( $option );
				$onboarding_data[ $option ] = $option_data;
			}

			return $onboarding_data;
		}
	/**
	 * Method to get the current onboarding step
	 *
	 * @return int $onboarding_step Current onboarding step.
	 *
	 * @since 4.6.0
	 */
		public static function get_onboarding_step() {
			$onboarding_step = (int) get_option( self::$onboarding_step_option, 1 );
			return $onboarding_step;
		}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
		public static function is_onboarding_completed() {

			$onboarding_complete = get_option( 'icegram_mailer_onboarding_complete', 'no' );

			if ( 'yes' === $onboarding_complete ) {
				return true;
			}

			return false;
		}

	/**
	 * Method to check if all required task has been completed.
	 *
	 * @param string $task_name Task name.
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
		public static function is_required_tasks_completed( $task_name = '' ) {

			if ( empty( $task_name ) ) {
				return false;
			}

			$required_tasks = self::get_required_tasks( $task_name );

			// If there are not any required tasks which means this task can run without any dependency.
			if ( empty( $required_tasks ) ) {
				return true;
			}

			$done_tasks = get_option( self::$onboarding_tasks_done_option, array() );

			// Variable to hold list of all done tasks without any grouping.
			$all_done_tasks         = array();
			$is_required_tasks_done = false;
			if ( ! empty( $done_tasks ) ) {
				foreach ( $done_tasks as $task_group => $grouped_tasks ) {
					foreach ( $grouped_tasks as $task ) {
						$all_done_tasks[] = $task;
					}
				}
			}

			$remaining_required_tasks = array_diff( $required_tasks, $all_done_tasks );

			// Check if there are not any required tasks remaining.
			if ( empty( $remaining_required_tasks ) ) {
				$is_required_tasks_done = true;
			}

			return $is_required_tasks_done;
		}

	/**
	 * Method to get lists of required tasks which should be completed successfully for this task.
	 *
	 * @return array $required_tasks List of required tasks.
	 */
		public static function get_required_tasks( $task_name = '' ) {

			if ( empty( $task_name ) ) {
				return array();
			}

			$required_tasks_mapping = array(
			'set_sending_service_consent' => array(
				'create_ess_account',
			),
			'dispatch_emails_from_server' => array(
				'set_sending_service_consent',
			),
			'check_test_email_on_server' => array(
				'dispatch_emails_from_server',
			),
			'subscribe_to_es' => array(
				'create_ess_account',
			),
			);

			$required_tasks = ! empty( $required_tasks_mapping[ $task_name ] ) ? $required_tasks_mapping[ $task_name ] : array();

			return $required_tasks;
		}

	/**
	 * Method to perform configuration and list, ES form, campaigns creation related operations in the onboarding
	 *
	 * @since 4.6.0
	 */
		public static function ajax_perform_configuration_tasks() {
			$step = 2;
			self::update_onboarding_step( $step );
			return self::perform_onboarding_tasks( 'configuration_tasks' );
		}

		public static function create_ess_account() {

			$response = array(
			'status' => 'error',
			);

			$plan       = 'lite';
			$email      = get_option( 'admin_email' );
			$from_email = $email;
			$from_name  = get_option( 'blogname', '' );
			$home_url   = home_url();
			$parsed_url = wp_parse_url( $home_url );
			$domain     = ! empty( $parsed_url['host'] ) ? $parsed_url['host'] : '';
			$source     = 'icegram-mailer';
			$limit      = 3000;

			if ( empty( $domain ) ) {
				$response['message'] = __( 'Site url is not valid. Please check your site url.', 'icegram-mailer' );
				return $response;
			}
		
			if ( empty( $from_name ) ) {
				$from_name = explode( '@', $from_email )[0];
			}

			$data = array(
			'limit'      => $limit,
			'domain'     => $domain,
			'email'      => $email,
			'from_email' => $from_email,
			'from_name'  => $from_name,
			'plan'		 => $plan,
			'source'     => $source,
			);

			$options = array(
			'timeout' => 50,
			'method'  => 'POST',
			'body'    => $data,
			);

			$request_url = self::$api_url . 'accounts/register/';

			$request_response = wp_remote_post( $request_url, $options );

			if ( is_wp_error( $request_response ) ) {
				$response['message'] = ! empty( $request_response->get_error_message() ) ? $request_response->get_error_message() : __( 'An error has occurred while creating your account. Please try again later', 'icegram-mailer' );
				return $response;
			}

			$response_code = wp_remote_retrieve_response_code( $request_response );
			$response_body = wp_remote_retrieve_body( $request_response );
			if ( 200 !== $response_code || ! icegram_mailer_is_valid_json( $response_body ) ) {
				$response['message'] = __( 'Unable to create your account. Please try again later or contact us at https://icegram.com/contact-us if issue persist.', 'icegram-mailer' );
				return $response;
			}
		
			$response_data = json_decode( $response_body, true );

			if ( empty( $response_data['account_id'] ) || empty( $response_data['api_key'] ) || empty( $response_data['allocated_limit'] ) ) {
				$response['message'] = __( 'Unable to create your account. Please try again later or contact us at https://icegram.com/contact-us if issue persist.', 'icegram-mailer' ); 
				return $response;
			}

			$account_id      = $response_data['account_id'];
			$api_key         = $response_data['api_key'];
			$allocated_limit = $response_data['allocated_limit'];
			$from_email      = $response_data['from_email'];
			$plan			 = $response_data['plan'];
			$next_reset		 = ! empty( $response_data['next_reset'] ) ? $response_data['next_reset'] : '';

			$ess_data = array(
			'account_id'      => $account_id,
			'allocated_limit' => $allocated_limit,
			'api_key'         => $api_key,
			'from_email'      => $from_email,
			'from_name'      => $from_name,
			'plan'			  => $plan,
			'next_reset'	  => $next_reset,
			);

			update_option( 'icegram_mailer_ess_data', $ess_data );

			$response['status'] = 'success';

			return $response;
		}

		public static function set_sending_service_consent() {

			$response = array(
			'status' => 'error',
			);
			update_option( 'icegram_mailer_opted_for_sending_service', 'yes', 'no' );
			update_option( 'icegram_mailer_status', 'success' );

			$response['status'] = 'success';
		
			return $response;
		}
	
	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
		public static  function ajax_dispatch_emails_from_server() {
			return self::perform_onboarding_tasks( 'email_delivery_check_tasks', 'dispatch_emails_from_server' );
		}


	/**
	 * Method to perform email delivery tasks.
	 *
	 * @since 4.6.0
	 */
		public static  function ajax_check_test_email_on_server() {

			return self::perform_onboarding_tasks( 'email_delivery_check_tasks', 'check_test_email_on_server' );
		}

	/**
	 * Method to send default broadcast campaign.
	 *
	 * @since 4.6.0
	 */
		public static function dispatch_emails_from_server() {

			$response = array(
			'status' => 'error',
			);

			$test_email = Icegram_Mailer_Common::get_test_email();
			$result  = self::send_test_mail( $test_email );
			if ( ! empty( $result['status'] ) && 'success' === $result['status'] ) {
				$response['status'] = 'success';
			}
		
			return $response;
		}

	/**
	 * Create and send test mail
	 *
	 * @param string $address
	 *
	 * @return void
	 * @throws Throwable
	 */
		public static function send_test_mail( $address ) {

			/* translators: %s is the timestamp */
			$msg = icegram_mailer()->client->get_test_email_content();
			$email = [
			'to' => $address,
			'subject' => icegram_mailer()->client->get_test_email_subject( $address ),
			'message' => $msg,
			'headers' => 'Content-Type: text/html',
			];
			$message = icegram_mailer()->client->build_message( $email );
			$mailer = new Icegram_Mailer_ESS_Mailer();
			if ( $mailer->send( $message ) ) {
				return array( 'status' => 'success' );
			} else {
				return array( 'status' => 'error' );
			}
		}

	/**
	 * Method to check if test email is received on Icegram servers.
	 *
	 * @since 4.6.0
	 */
		public static function check_test_email_on_server() {

			$response = array(
			'status' => 'error',
			);

			$onboarding_tasks_failed           = get_option( self::$onboarding_tasks_failed_option, array() );
			$email_delivery_check_tasks_failed = ! empty( $onboarding_tasks_failed['email_delivery_check_tasks'] ) ? $onboarding_tasks_failed['email_delivery_check_tasks'] : array();

			$task_failed = in_array( 'dispatch_emails_from_server', $email_delivery_check_tasks_failed, true );

			// Peform test email checking if dispatch_emails_from_server task hasn't failed.
			if ( ! $task_failed ) {
				$service  = new Icegram_Mailer_Email_Delivery_Check();
				$response = $service->test_email_delivery();
			} else {
				$response['status'] = 'failed';
			}

			return $response;
		}

		public static function subscribe_to_es() {

			$name  = get_option( 'blogname', '' );
			$email = get_option( 'admin_email' );
			$list  = '4781d3aa09c0';

			$sign_up_data = array(
			'name'  => $name,
			'email' => $email,
			'list'  => $list,
			);

			Icegram_Mailer_Common::send_ig_sign_up_request( $sign_up_data );
			return array(
			'status' => 'success',
			);
		}

	/**
	 * Method to perform give onboarding tasks types.
	 *
	 * @param string $task_group Tasks group
	 * @param string $task_name Specific task
	 *
	 * @since 4.6.0
	 */
		public static function perform_onboarding_tasks( $task_group = '', $task_name = '' ) {

			$response = array(
			'status' => '',
			'tasks'  => array(),
			);

			$task_group = ! empty( $task_group ) ? $task_group : 'configuration_tasks';

			$all_onboarding_tasks = self::$all_onboarding_tasks;

			$current_tasks = array();
			if ( ! empty( $all_onboarding_tasks[ $task_group ] ) ) {
				// Get specific task else all tasks in a group.
				if ( ! empty( $task_name ) ) {
					$task_index = array_search( $task_name, $all_onboarding_tasks[ $task_group ], true );
					if ( false !== $task_index ) {
						$current_tasks = array( $task_name );
					}
				} else {
					$current_tasks = $all_onboarding_tasks[ $task_group ];
				}
			}

			$onboarding_tasks_done = get_option( self::$onboarding_tasks_done_option, array() );
			$current_tasks_done    = ! empty( $onboarding_tasks_done[ $task_group ] ) ? $onboarding_tasks_done[ $task_group ] : array();

			$onboarding_tasks_failed = get_option( self::$onboarding_tasks_failed_option, array() );
			$current_tasks_failed    = ! empty( $onboarding_tasks_failed[ $task_group ] ) ? $onboarding_tasks_failed[ $task_group ] : array();

			$onboarding_tasks_skipped = get_option( self::$onboarding_tasks_skipped_option, array() );
			$current_tasks_skipped    = ! empty( $onboarding_tasks_skipped[ $task_group ] ) ? $onboarding_tasks_skipped[ $task_group ] : array();

			$onboarding_tasks_data = get_option( self::$onboarding_tasks_data_option, array() );
			if ( ! empty( $current_tasks ) ) {
				foreach ( $current_tasks as $current_task ) {
					if ( ! in_array( $current_task, $current_tasks_done, true ) ) {

						if ( self::is_required_tasks_completed( $current_task ) ) {
							if ( is_callable( array( self::class, $current_task ) ) ) {
								//$logger->info( 'Doing Task:' . $current_task, self::$logger_context );
	
								// Call callback function.
								$task_response = call_user_func( array( self::class, $current_task ) );
								if ( 'success' === $task_response['status'] ) {
									if ( ! empty( $task_response['tasks_data'] ) ) {
										if ( ! isset( $onboarding_tasks_data[ $current_task ] ) ) {
											$onboarding_tasks_data[ $current_task ] = array();
										}
										$onboarding_tasks_data[ $current_task ] = array_merge( $onboarding_tasks_data[ $current_task ], $task_response['tasks_data'] );
									}
									//$logger->info( 'Task Done:' . $current_task, self::$logger_context );
									// Set success status only if not already set else it can override error/skipped statuses set previously from other tasks.
									if ( empty( $response['status'] ) ) {
										$response['status'] = 'success';
									}
									$current_tasks_done[] = $current_task;
								} elseif ( 'skipped' === $task_response['status'] ) {
									$response['status']      = 'skipped';
									$current_tasks_skipped[] = $current_task;
								} else {
									//$logger->info( 'Task Failed:' . $current_task, self::$logger_context );
									$response['status']     = 'error';
									$current_tasks_failed[] = $current_task;
								}
	
								$response['tasks'][ $current_task ] = $task_response;
	
								$onboarding_tasks_done[ $task_group ]    = $current_tasks_done;
								$onboarding_tasks_failed[ $task_group ]  = $current_tasks_failed;
								$onboarding_tasks_skipped[ $task_group ] = $current_tasks_skipped;
	
								update_option( self::$onboarding_tasks_done_option, $onboarding_tasks_done );
								update_option( self::$onboarding_tasks_failed_option, $onboarding_tasks_failed );
								update_option( self::$onboarding_tasks_skipped_option, $onboarding_tasks_skipped );
								update_option( self::$onboarding_tasks_data_option, $onboarding_tasks_data );
								update_option( self::$onboarding_current_task_option, $current_task );
							}
						} else {
							$response['status']      = 'skipped';
							$current_tasks_skipped[] = $current_task;
						}
					} else {
						$response['tasks'][ $current_task ] = array(
						'status' => 'success',
						);
						//$logger->info( 'Task already done:' . $current_task, self::$logger_context );
					}
				}
			}

			return $response;
		}

	/**
	 * Method to check if onboarding is completed
	 *
	 * @return string
	 *
	 * @since 4.6.0
	 */
		public static function ajax_complete_ess_onboarding() {
			$response       = array();
			$option_updated = update_option( 'icegram_mailer_onboarding_complete', 'yes', false );
			if ( $option_updated ) {
				$response['status'] = 'success';
			}
			return $response;
		}

	/**
	 * Method to update the onboarding step
	 *
	 * @return bool
	 *
	 * @since 4.6.0
	 */
		public static function update_onboarding_step( $step = 1 ) {
			if ( ! empty( $step ) ) {
				update_option( self::$onboarding_step_option, $step );
				return true;
			}

			return false;
		}

	}
}

Icegram_Mailer_Onboarding_Controller::get_instance();
