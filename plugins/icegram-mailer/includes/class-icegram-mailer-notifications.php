<?php

if ( ! class_exists( 'Icegram_Mailer_Notifications' ) ) {

    /**
     * Handles all notifications for Icegram Mailer
     * - Failed email tracking and queueing
     * - Batch notifications sent via cron every 15 minutes
     * - Email sending limit reached notifications (90% threshold)
     * - Monthly summary notifications
     */
    class Icegram_Mailer_Notifications {

        /**
         * Class instance.
         *
         * @var Icegram_Mailer_Notifications
         */
        protected static $instance = null;

        /** Queue option name for storing failed emails */
        private static $queue_option = 'icegram_mailer_failed_queue';

        /** Option name for tracking last notification time */
        private static $last_notification_option = 'icegram_mailer_last_failed_notification';

        /** Option name for tracking consecutive failure count */
        private static $failure_count_option = 'icegram_mailer_failure_count';

        /** Option name for tracking last limit notification time */
        private static $last_limit_notification_option = 'icegram_mailer_last_limit_notification';

        /** Cron hook name */
        private static $failed_queue_cron_hook = 'icegram_mailer_process_failed_queue';

        /** Monthly summary cron hook name */
        private static $monthly_summary_cron_hook = 'icegram_mailer_send_monthly_summary';

        /** Option name for tracking last monthly summary notification time */
        private static $last_summary_notification_option = 'icegram_mailer_last_summary_notification';

        /**
         * Consecutive successes required to reset the failure counter.
         * Prevents a single fluke success from clearing real persistent failures.
         */
        private static $success_reset_threshold = 3;

        /** Transient key used as a mutex for queue writes */
        private static $queue_lock_transient = 'icegram_mailer_queue_lock';

        /** Option for tracking consecutive successes (for graduated reset) */
        private static $consecutive_success_option = 'icegram_mailer_consecutive_successes';

        /** Flag to prevent notification send loops */
        private $sending_admin_notification = false;

        /**
         * Return (or create) the singleton instance.
         *
         * @return Icegram_Mailer_Notifications
         */
        public static function get_instance() {
            if ( ! isset( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            // React to every processed email.
            add_action( 'icegram_mailer_after_email_processed', array( $this, 'handle_email_status' ), 10, 2 );

            // Check mailer limit after successful sends.
            add_action( 'icegram_mailer_after_email_processed', array( $this, 'check_mailer_limit' ), 20, 2 );

            // Cron processor.
            add_action( self::$failed_queue_cron_hook, array( $this, 'process_failed_queue' ) );

            // Monthly summary cron processor.
            add_action( self::$monthly_summary_cron_hook, array( $this, 'send_monthly_summary' ) );

            // Register a custom cron interval (15 min by default).
            add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

            // Schedule cron if not already queued.
            if ( ! wp_next_scheduled( self::$failed_queue_cron_hook ) ) {
                wp_schedule_event( time(), 'icegram_mailer_notification', self::$failed_queue_cron_hook );
            }

            // Schedule monthly summary cron if not already queued.
            if ( ! wp_next_scheduled( self::$monthly_summary_cron_hook ) ) {
                wp_schedule_event( time(), 'icegram_mailer_monthly_summary', self::$monthly_summary_cron_hook );
            }

            // Reset failure tracking when user toggles the sending service setting.
            add_action( 'update_option_icegram_mailer_opted_for_sending_service', array( $this, 'handle_service_toggle' ), 10, 2 );
        }

        /**
         * Register the custom cron interval used by this plugin.
         *
         * @param array $schedules Existing WP-cron schedules.
         * @return array
         */
        public function add_cron_interval( $schedules ) {
            $interval = (int) apply_filters( 'icegram_mailer_notification_interval', 900 ); // Default: 15 min.

            $schedules['icegram_mailer_notification'] = array(
                'interval' => $interval,
                'display'  => sprintf(
                    /* translators: %d: interval in minutes */
                    __( 'Every %d Minutes', 'icegram-mailer' ),
                    $interval / 60
                ),
            );

            // Add monthly summary interval - default to daily checks
            $summary_interval = (int) apply_filters( 'icegram_mailer_summary_check_interval', DAY_IN_SECONDS );

            $schedules['icegram_mailer_monthly_summary'] = array(
                'interval' => $summary_interval,
                'display'  => __( 'Icegram Mailer - Monthly Summary Check', 'icegram-mailer' ),
            );

            return $schedules;
        }

        /**
         * Route a processed email to the right handler.
         *
         * @param object $message Email message object.
         * @param string $status  'sent' or 'failed'.
         */
        public function handle_email_status( $message, $status ) {
           
            // Don't track if Icegram Mailer sending service is not enabled.
            if ( ! Icegram_Mailer_Account::is_opted_for_ess() ) {
                return;
            }
            
            if ( 'failed' === $status ) {
                $this->add_to_failed_queue( $message );
                $this->increment_failure_count();
                $this->reset_consecutive_successes(); // A failure breaks any success streak.

            } elseif ( 'sent' === $status ) {
                $this->handle_successful_email();
            }
        }

        /**
         * On a successful send, increment the consecutive-success counter and
         * only reset the failure counter once enough consecutive successes have
         * been recorded. This prevents a single lucky send from masking a
         * persistent delivery problem.
         */
        private function handle_successful_email() {
            $consecutive = (int) get_option( self::$consecutive_success_option, 0 );
            $consecutive++;
            update_option( self::$consecutive_success_option, $consecutive, false );

            $required = (int) apply_filters( 'icegram_mailer_success_reset_threshold', self::$success_reset_threshold );

            if ( $consecutive >= $required ) {
                $this->reset_failure_count();
                $this->reset_consecutive_successes();
            }
        }

        /**
         * Append a failed email to the persistent queue using a simple transient
         * lock to avoid duplicate writes under concurrent requests.
         *
         * @param object $message Failed message object.
         */
        private function add_to_failed_queue( $message ) {
            
            // Acquire a short-lived mutex (5-second TTL is enough for a DB read-modify-write).
            $lock_acquired = false;
            $attempts      = 0;

            while ( $attempts < 5 ) {
                if ( false === get_transient( self::$queue_lock_transient ) ) {
                    set_transient( self::$queue_lock_transient, 1, 5 );
                    $lock_acquired = true;
                    break;
                }
                usleep( 200000 ); // Wait 200 ms before retrying.
                $attempts++;
            }

            // If we couldn't acquire the lock, abort to prevent race conditions.
            if ( ! $lock_acquired ) {
                return;
            } 

            $queue = get_option( self::$queue_option, array() );

            if ( ! is_array( $queue ) ) {
                $queue = array();
            } 

            $queue[] = array(
                'to'          => isset( $message->to )          ? $message->to          : '',
                'subject'     => isset( $message->subject )     ? $message->subject     : '',
                'error'       => isset( $message->error )       ? $message->error       : __( 'Unknown error', 'icegram-mailer' ),
                'tracking_id' => isset( $message->tracking_id ) ? $message->tracking_id : '',
                'time'        => current_time( 'mysql' ),
                'timestamp'   => time(),
            ); 

            // Cap queue size to avoid unbounded memory growth.
            if ( count( $queue ) > 100 ) {
                $queue = array_slice( $queue, -100 );
            }

            update_option( self::$queue_option, $queue, false );

            if ( $lock_acquired ) {
                delete_transient( self::$queue_lock_transient );
            }
        }

        /** Increment the running failure count. */
        private function increment_failure_count() {
            $count = (int) get_option( self::$failure_count_option, 0 );
            update_option( self::$failure_count_option, $count + 1, false );
        }

        /** Reset the running failure count to zero. */
        private function reset_failure_count() {
            update_option( self::$failure_count_option, 0, false );
        }

        /**
         * Return the current cumulative failure count.
         *
         * @return int
         */
        public function get_failure_count() {
            return (int) get_option( self::$failure_count_option, 0 );
        }

        /** Reset the consecutive-success streak counter. */
        private function reset_consecutive_successes() {
            update_option( self::$consecutive_success_option, 0, false );
        }

        /**
         * Handle when user toggles the sending service setting.
         * Resets failure count, consecutive successes, and failed queue when re-enabled.
         *
         * @param string $old_value Previous value ('yes' or 'no').
         * @param string $new_value New value ('yes' or 'no').
         */
        public function handle_service_toggle( $old_value, $new_value ) {
            // If changed from 'no' to 'yes' (disabled → enabled), reset all counters and queue.
            if ( 'no' === $old_value && 'yes' === $new_value ) {
                delete_option( self::$failure_count_option );
                delete_option( self::$consecutive_success_option );
                delete_option( self::$queue_option );
            }

            // If changed from 'yes' to 'no' (enabled → disabled), clear pending notifications.
            if ( 'yes' === $old_value && 'no' === $new_value ) {
                delete_option( self::$queue_option );
                delete_option( self::$last_notification_option );
                delete_option( self::$last_limit_notification_option );
                delete_option( self::$last_summary_notification_option );
            }
        }

        /**
         * Load an email template with provided variables.
         *
         * @param string $view Template file name (without .php extension).
         * @param array  $imported_variables Variables to extract into template scope.
         * @param string $path Optional custom path to templates directory.
         * @return void
         */
        private static function get_view( $view, $imported_variables = array(), $path = '' ) {
            if ( $imported_variables && is_array( $imported_variables ) ) {
                extract( $imported_variables ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            }

            if ( ! $path ) {
                $path = ICEGRAM_MAILER_PLUGIN_PATH . '/admin/templates/emails/';
            }

            $file_path = $path . $view . '.php';
            
            // Validate file exists
            if ( ! file_exists( $file_path ) ) {
                return;
            }

            $real_file_path = realpath( $file_path );
            $real_plugin_path = realpath( ICEGRAM_MAILER_PLUGIN_PATH );

            // Check if realpath() succeeded and file is within plugin directory
            if ( false === $real_file_path || false === $real_plugin_path ) {
                return;
            }

            // Normalize directory separators for cross-platform compatibility
            $real_file_path = wp_normalize_path( $real_file_path );
            $real_plugin_path = wp_normalize_path( $real_plugin_path );

            // Verify the file is within the plugin directory
            if ( 0 !== strpos( $real_file_path, $real_plugin_path ) ) {
                // Log potential security issue
                error_log( 'Icegram Mailer: Attempted file inclusion outside plugin directory: ' . esc_html( $file_path ) );
                return;
            }

            include $real_file_path;
        }

        /**
         * Capture email template content as a string.
         *
         * @param string $view Template file name (without .php extension).
         * @param array  $data Variables to pass to template.
         * @return string Rendered template content.
         */
        private static function get_content( $view, $data = array() ) {
            ob_start();
            self::get_view( $view, $data );
            $content = ob_get_clean();
            return $content;
        }


        /**
         * Main cron callback — handles batch failure summaries.
         */
        public function process_failed_queue() {

            // Guard against re-entrant calls triggered by wp_mail() inside this method.
            if ( $this->sending_admin_notification ) {
                return;
            }

            // Don't track if Icegram Mailer sending service is not enabled.
            if ( ! Icegram_Mailer_Account::is_opted_for_ess() ) {
                return;
            }

            $queue = get_option( self::$queue_option, array() );

            if ( empty( $queue ) ) {
                return;
            }

            if ( ! $this->should_send_notification() ) {
                return;
            }

            $this->send_batch_notification( $queue );

            delete_option( self::$queue_option );
            $this->update_notification_state( time() );
        }

        /**
         * Compose and send a batch summary of all queued failures.
         *
         * @param array $failed_emails Array of failed-email data arrays.
         */
        private function send_batch_notification( $failed_emails ) {
            if ( empty( $failed_emails ) ) {
                return;
            }

            $admin_email      = get_option( 'admin_email' );
            $site_name        = get_bloginfo( 'name' );
            $failure_count    = count( $failed_emails );
            $interval         = (int) apply_filters( 'icegram_mailer_notification_interval', 900 );
            $interval_minutes = $interval / 60;

            $plugin_name = $this->get_plugin_name();

            $subject = sprintf(
                /* translators: 1: site name, 2: plugin name */
                __( '[%1$s] – %2$s: Email Delivery Update', 'icegram-mailer' ),
                $site_name,
                $plugin_name
            );

            $display_limit = 5;
            $display_count = min( absint( $failure_count ), absint( $display_limit ) );
            
            // Process failed emails for display
            $processed_failed_emails = array();
            for ( $i = 0; $i < $display_count; $i++ ) {
                $failed_email = $failed_emails[ $i ];
                
                $to_email = '';
                if ( ! empty( $failed_email['to'] ) ) {
                    $to_email = is_array( $failed_email['to'] ) ? implode( ', ', $failed_email['to'] ) : $failed_email['to'];
                }
                $to_email = $to_email ?: __( 'Unknown recipient', 'icegram-mailer' );
                $subject_text  = ! empty( $failed_email['subject'] ) ? $failed_email['subject'] : __( 'No subject', 'icegram-mailer' );
                $error_message = ! empty( $failed_email['error'] )   ? $failed_email['error']   : __( 'Unknown error', 'icegram-mailer' );
                $time          = ! empty( $failed_email['time'] )    ? $failed_email['time']    : '';
                $formatted_time = '';
                if ( ! empty( $time ) ) {
                    $timestamp = strtotime( $time );
                    $formatted_time = date_i18n( 'M j, Y, g:i a', $timestamp );
                }
                
                $processed_failed_emails[] = array(
                    'to_email'       => $to_email,
                    'subject_text'   => $subject_text,
                    'error_message'  => $error_message,
                    'formatted_time' => $formatted_time,
                );
            }
            
            $data = array( 
                'failure_count'           => $failure_count,
                'interval_minutes'        => $interval_minutes,
                'processed_failed_emails' => $processed_failed_emails,
                'logo_url'                => $this->get_plugin_logo_url(),
                'site_name'               => $site_name,
                'allowed_html'            => Icegram_Mailer_Common::get_allowed_html_for_emails(),
            );

            $content = self::get_content( 'failed-emails', $data );

            $this->send_admin_email( $admin_email, $subject, $content );
        }

        /**
         * Send an email directly via wp_mail(), temporarily bypassing the
         * Icegram Mailer filter to prevent recursive failures.
         *
         * @param string $to      Recipient address.
         * @param string $subject Subject line.
         * @param string $content HTML body.
         */
        protected function send_admin_email( $to, $subject, $content ) {
            $this->sending_admin_notification = true;

            $headers = array( 'Content-Type: text/html; charset=UTF-8' );

            // Try sending via Icegram Mailer client first.
            $sent = icegram_mailer()->client->send( false, array(
                'to' => $to,
                'subject' => $subject,
                'message' => $content,
                'headers' => $headers,
            ) );

            // Fallback to default wp_mail() only if Icegram Mailer fails.
            if ( false === $sent || is_wp_error( $sent ) ) {
                
                // Detach our custom mailer so wp_mail() falls back to PHP mail.
                remove_filter( 'pre_wp_mail', array( icegram_mailer()->client, 'send' ), 10 );

                wp_mail( $to, $subject, $content, $headers );

                // Re-attach the filter.
                add_filter( 'pre_wp_mail', array( icegram_mailer()->client, 'send' ), 10, 2 );
            }

            $this->sending_admin_notification = false;
        }

        /**
         * Throttle check: return true only if enough time has passed since the
         * last notification was sent. Interval is read from the same filter used
         * to define the cron schedule, keeping the two in sync.
         *
         * @return bool
         */
        private function should_send_notification() {
            $interval     = (int) apply_filters( 'icegram_mailer_notification_interval', 900 );
            $last_sent    = (int) get_option( self::$last_notification_option, 0 );
            $elapsed      = time() - $last_sent;

            return apply_filters( 'icegram_mailer_should_send_admin_notification', $elapsed >= $interval, 'email_failed_notification' );
        }

        /**
         * Persist the timestamp of the most-recently-sent notification.
         *
         * @param int $timestamp Unix timestamp.
         */
        private function update_notification_state( $timestamp ) {
            update_option( self::$last_notification_option, $timestamp, false );
        }

        /**
         * Check if the mailer limit has been reached and send notification to admin.
         *
         * @param object $message Email message object.
         * @param string $status  'sent' or 'failed'.
         */
        public function check_mailer_limit( $message, $status ) {

            // Prevent recursive notifications when sending admin emails
            if ( $this->sending_admin_notification ) {
                return;
            }

            // Don't track if Icegram Mailer sending service is not enabled.
            if ( ! Icegram_Mailer_Account::is_opted_for_ess() ) {
                return;
            }

            // Only check on successful sends.
            if ( 'sent' !== $status ) {
                return;
            }

            $ess_data = Icegram_Mailer_Account::get_ess_data();
            if ( empty( $ess_data ) ) {
                return;
            }

            $allocated_limit = isset( $ess_data['allocated_limit'] ) ? (int) $ess_data['allocated_limit'] : 0;
            $current_month   = icegram_mailer_get_current_month();
            $used_limit      = isset( $ess_data['used_limit'][ $current_month ] ) ? (int) $ess_data['used_limit'][ $current_month ] : 0;

            if ( $allocated_limit <= 0 ) {
                return;
            }

            // Calculate percentage used.
            $percentage_used = ( $used_limit / $allocated_limit ) * 100;

            // Send notification if threshold is reached (default: 90%).
            $threshold = (int) apply_filters( 'icegram_mailer_limit_notification_threshold', 90 );

            if ( $percentage_used >= $threshold && $this->should_send_limit_notification() ) {
                $this->send_limit_notification( $allocated_limit, $used_limit, $percentage_used, $ess_data );
            }
        }

        /**
         * Check if we should send a limit notification.
         * Prevents spam by only sending once per day or when limit resets.
         *
         * @return bool
         */
        private function should_send_limit_notification() {
            $last_sent    = (int) get_option( self::$last_limit_notification_option, 0 );
            $interval     = (int) apply_filters( 'icegram_mailer_limit_notification_interval', DAY_IN_SECONDS );
            $elapsed      = time() - $last_sent;

            return apply_filters( 'icegram_mailer_should_send_admin_notification', $elapsed >= $interval, 'limit_notification' );
        }

        /**
         * Send a notification to the admin about the mailer limit being reached.
         *
         * @param int   $allocated_limit Total allocated limit.
         * @param int   $used_limit      Used limit.
         * @param float $percentage_used Percentage of limit used.
         * @param array $ess_data        ESS account data.
         */
        private function send_limit_notification( $allocated_limit, $used_limit, $percentage_used, $ess_data ) {
            $admin_email = get_option( 'admin_email' );
            $site_name   = get_bloginfo( 'name' );
            $remaining   = $allocated_limit - $used_limit;

            $plugin_name = $this->get_plugin_name();

            $subject = $percentage_used < 100 ? sprintf(
                /* translators: 1: site name, 2: plugin name */
                __( '[%1$s] - %2$s Limit Almost Reached', 'icegram-mailer' ),
                $site_name,
                $plugin_name
            ) : sprintf(
                /* translators: 1: site name, 2: plugin name */
                __( '[%1$s] - %2$s Limit Reached', 'icegram-mailer' ),
                $site_name,
                $plugin_name
            );

            // Calculate percentage display
            $percentage = esc_html( number_format_i18n( $percentage_used, 0 ) ) . '%';

            // Format next reset date
            $next_reset_formatted = '';
            if ( ! empty( $ess_data['next_reset'] ) ) {
                $next_reset_formatted = icegram_mailer_convert_gmt_date_to_local_date( $ess_data['next_reset'] );
            }

            // Get upgrade pricing URL with UTM tracking
            $upgrade_url = Icegram_Mailer_Common::get_utm_tracking_url(
                array(
                    'url'          => 'https://www.icegram.com/mailer/#pricing',
                    'utm_medium'   => 'email',
                    'utm_campaign' => 'limit_alert',
                )
            );

            $data = array( 
                'site_name'            => $site_name,
                'allocated_limit'      => $allocated_limit,
                'used_limit'           => $used_limit,
                'percentage_used'      => $percentage_used,
                'remaining'            => $remaining,
                'ess_data'             => $ess_data,
                'logo_url'             => $this->get_plugin_logo_url(),
                'percentage'           => $percentage,
                'allowed_html'         => Icegram_Mailer_Common::get_allowed_html_for_emails(),
                'next_reset_formatted' => $next_reset_formatted,
                'upgrade_url'          => $upgrade_url,
            );

            $content = self::get_content( 'limit-reached', $data );

            $this->send_admin_email( $admin_email, $subject, $content );
            update_option( self::$last_limit_notification_option, time(), false );
        }

        /**
         * Send monthly summary email a few days before limit reset.
         * Triggered by cron.
         */
        public function send_monthly_summary() {
            global $wpdb;

            // Don't track if Icegram Mailer sending service is not enabled.
            if ( ! Icegram_Mailer_Account::is_opted_for_ess() ) {
                return;
            }

            $ess_data = Icegram_Mailer_Account::get_ess_data();
            if ( empty( $ess_data ) ) {
                return;
            }

            $allocated_limit = isset( $ess_data['allocated_limit'] ) ? (int) $ess_data['allocated_limit'] : 0;
            if ( $allocated_limit <= 0 ) {
                return;
            }

            // Check if it's time to send the summary (default: 2 days before reset).
            $days_before_reset = (int) apply_filters( 'icegram_mailer_summary_days_before_reset', 2 );
            
            if ( ! $this->should_send_monthly_summary( $days_before_reset ) ) {
                return;
            }

            $current_month = icegram_mailer_get_current_month();
            $used_limit    = isset( $ess_data['used_limit'][ $current_month ] ) ? (int) $ess_data['used_limit'][ $current_month ] : 0;
            
            // Get the start of current month as timestamp.
            $current_month_start = strtotime( $current_month . '-01 00:00:00' );

            // Calculate next reset date.
            $next_month      = date_i18n( 'Y-m-01', strtotime( '+1 month', $current_month_start ) );
            $reset_date      = ! empty( $ess_data['next_reset'] ) ? $ess_data['next_reset'] : $next_month;
            $days_to_reset   = max( 0, floor( ( strtotime( $reset_date ) - time() ) / DAY_IN_SECONDS ) );
            
            // Calculate billing cycle start (last reset = next reset - 1 month).            
            // Use DateTime for reliable month arithmetic.
            $reset_datetime = new DateTime( $reset_date );
            $reset_datetime->modify( '-1 month' );
            $billing_cycle_start = $reset_datetime->getTimestamp();

            // Get email statistics from logs table. 
            $logs_table = $wpdb->prefix . 'icegram_mailer_email_logs';
             
            // Count successful emails.
            $successful_emails = 0;
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table ) {
                $successful_emails = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$logs_table} WHERE status = %s AND created_at >= %d",
                        'sent',
                        $billing_cycle_start
                    )
                );
            }
            
            // Calculate failed emails.
            $failed_emails = $used_limit - $successful_emails;
            if ( $failed_emails < 0 ) {
                $failed_emails = 0;
            }
            
            $percentage_used = $allocated_limit > 0 ? ( $used_limit / $allocated_limit ) * 100 : 0;
            
            $this->send_monthly_summary_email( $allocated_limit, $used_limit, $successful_emails, $failed_emails, $percentage_used, $reset_date, $days_to_reset, $billing_cycle_start );
            
            // Update last sent timestamp.
            update_option( self::$last_summary_notification_option, time(), false );
        }

        /**
         * Check if we should send the monthly summary.
         * Only send X days before the limit resets and not more than once per month.
         *
         * @param int $days_before_reset Days before reset to trigger summary.
         * @return bool
         */
        private function should_send_monthly_summary( $days_before_reset = 2 ) {
            // Check when we last sent the summary.
            $last_sent = (int) get_option( self::$last_summary_notification_option, 0 );
            
            // Don't send more than once per month.
            $one_month_ago = strtotime( '-1 month' );
            if ( $last_sent > $one_month_ago ) {
                return false;
            }

            // Don't send summary if a limit notification was recently sent.
            // This prevents users from receiving two similar emails in quick succession.
            $last_limit_notification = (int) get_option( self::$last_limit_notification_option, 0 );
            $time_since_limit_notification = time() - $last_limit_notification;
            $limit_notification_delay = (int) apply_filters( 'icegram_mailer_summary_limit_delay', DAY_IN_SECONDS );

            if ( $time_since_limit_notification < $limit_notification_delay ) {
                return false;
            }
            
            $ess_data = Icegram_Mailer_Account::get_ess_data();
            if ( empty( $ess_data ) ) {
                return false;
            }
            
            // Calculate days until reset.
            $current_month       = icegram_mailer_get_current_month();
            $current_month_start = strtotime( $current_month . '-01 00:00:00' );
            $next_month          = date_i18n( 'Y-m-01', strtotime( '+1 month', $current_month_start ) );
            $reset_date          = ! empty( $ess_data['next_reset'] ) ? $ess_data['next_reset'] : $next_month;
            $days_to_reset       = max( 0, floor( ( strtotime( $reset_date ) - time() ) / DAY_IN_SECONDS ) );
            
            // Send if we're within the threshold window.
            $should_send = $days_to_reset <= $days_before_reset && $days_to_reset >= 0;
            return apply_filters( 'icegram_mailer_should_send_admin_notification', $should_send, 'monthly_summary' );
        }

        /**
         * Send the monthly summary email to admin.
         *
         * @param int    $allocated_limit   Total allocated limit.
         * @param int    $used_limit        Used emails count.
         * @param int    $successful_emails Successful emails sent.
         * @param int    $failed_emails     Failed emails count.
         * @param float  $percentage_used   Percentage of limit used.
         * @param string $reset_date        Date when limit will reset.
         * @param int    $days_to_reset     Days remaining until reset.
         * @param int    $billing_cycle_start Billing cycle start timestamp.
         */
        private function send_monthly_summary_email( $allocated_limit, $used_limit, $successful_emails, $failed_emails, $percentage_used, $reset_date, $days_to_reset, $billing_cycle_start ) {
            $admin_email = get_option( 'admin_email' );
            $site_name   = get_bloginfo( 'name' );
            $remaining   = $allocated_limit - $used_limit;
            
            $plugin_name = $this->get_plugin_name();

            $subject = sprintf(
                /* translators: 1: site name, 2: plugin name */
                __( '[%1$s] - %2$s Monthly Summary', 'icegram-mailer' ),
                $site_name,
                $plugin_name
            );

            // Calculate rates and engagement metrics
            $total_emails = $successful_emails + $failed_emails;
            $success_rate = $total_emails > 0 ? ( $successful_emails / $total_emails ) * 100 : 0;
            $failure_rate = $total_emails > 0 ? ( $failed_emails / $total_emails ) * 100 : 0;
            
            // Calculate estimated opens
            $open_count = round( $successful_emails * 0.53 );
            $open_rate = $successful_emails > 0 ? ( $open_count / $successful_emails ) * 100 : 0;
            
            // Get date range for the reporting period
            $current_time = current_time( 'timestamp' );

            $date_range_start = strtoupper( date_i18n( 'M d', $billing_cycle_start ) );
            $date_range_end = strtoupper( date_i18n( 'M d, Y', $current_time ) );
            
            // Get upgrade pricing URL with UTM tracking
            $upgrade_url = Icegram_Mailer_Common::get_utm_tracking_url(
                array(
                    'url'          => 'https://www.icegram.com/mailer/#pricing',
                    'utm_medium'   => 'email',
                    'utm_campaign' => 'monthly_summary',
                )
            );
            
            // Format reset date
            $reset_date_formatted = icegram_mailer_convert_gmt_date_to_local_date( $reset_date );

            $data = array(
                'site_name'                      => $site_name,
                'allocated_limit'                => $allocated_limit,
                'used_limit'                     => $used_limit,
                'successful_emails'              => $successful_emails,
                'failed_emails'                  => $failed_emails,
                'percentage_used'                => $percentage_used,
                'remaining'                      => $remaining,
                'reset_date'                     => $reset_date,
                'days_to_reset'                  => $days_to_reset,
                'logo_url'                       => $this->get_plugin_logo_url(),
                'total_emails'                   => $total_emails,
                'success_rate'                   => $success_rate,
                'failure_rate'                   => $failure_rate,
                'open_count'                     => $open_count,
                'open_rate'                      => $open_rate,
                'date_range_start'               => $date_range_start,
                'date_range_end'                 => $date_range_end,
                'upgrade_url'                    => $upgrade_url,
                'reset_date_formatted'           => $reset_date_formatted,
            );

            $content = self::get_content( 'monthly-summary', $data );

            $this->send_admin_email( $admin_email, $subject, $content );
        } 

        /**
         * Called on plugin deactivation — removes the scheduled cron event.
         */
        public static function unschedule_cron_jobs() {
             
            $timestamp = wp_next_scheduled( self::$failed_queue_cron_hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, self::$failed_queue_cron_hook );
            }
            
            // Also unschedule monthly summary cron.
            $summary_timestamp = wp_next_scheduled( self::$monthly_summary_cron_hook );
            if ( $summary_timestamp ) {
                wp_unschedule_event( $summary_timestamp, self::$monthly_summary_cron_hook );
            }
        } 

        /**
         * Get logo URL for email templates.
         *
         * @return string Logo URL.
         */
        private function get_plugin_logo_url() { 
            return ICEGRAM_MAILER_PLUGIN_URL . '/assets/images/icegram-mailer.gif';
        }

        /**
         * Get plugin name from plugin header.
         *
         * @return string Plugin name.
         */
        protected function get_plugin_name() {
            return 'Icegram Mailer';
        }
    }
}

Icegram_Mailer_Notifications::get_instance();