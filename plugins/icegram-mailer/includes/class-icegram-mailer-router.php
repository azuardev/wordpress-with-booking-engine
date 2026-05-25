<?php

if ( ! class_exists( 'Icegram_Mailer_Router' ) ) {

	class Icegram_Mailer_Router {

		public static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function __construct() {
			$this->register_hooks();
		}

		public function register_hooks() {
			add_action( 'wp_ajax_icegram-mailer', array( $this, 'handle_ajax_request' ) );
		}

		public function handle_ajax_request() {
			check_ajax_referer( 'icegram-mailer-admin-ajax-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'icegram-mailer' ) ) );
			}

			$handler       = isset( $_REQUEST['handler'] ) ? sanitize_text_field( wp_unslash ( $_REQUEST['handler'] ) ) : '';
			$method        = isset( $_REQUEST['method'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['method'] ) ) : '';

			$handler_class = 'Icegram_Mailer_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $handler ) ) ) . '_Controller';

			if ( empty( $handler ) || ! class_exists( $handler_class ) ) {
				wp_send_json_error( array( 'message' => __( 'No handler found.', 'icegram-mailer' ) ) );
			}

			if ( empty( $method ) || ! method_exists( $handler_class, $method ) ) {
				wp_send_json_error( array( 'message' => __( 'No method found.', 'icegram-mailer' ) ) );
			}

			$data = isset( $_REQUEST['data'] ) ? $_REQUEST['data'] : array();

			$response = call_user_func( array( $handler_class, $method ), $data );

			wp_send_json( $response ); 
		}
	}
}

Icegram_Mailer_Router::get_instance();
