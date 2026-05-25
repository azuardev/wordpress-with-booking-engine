<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! function_exists( 'icegram_mailer_is_valid_json' ) ) {
	function icegram_mailer_is_valid_json( $string ) {
	
		return is_string( $string ) && is_array( json_decode( $string, true ) ) && ( json_last_error() === JSON_ERROR_NONE ) ? true : false;
	}
}


if ( ! function_exists( 'icegram_mailer_get_db_version' ) ) {
	/**
	 * Get current db version
	 */
	function icegram_mailer_get_db_version() {

		$option = get_option( 'icegram_mailer_db_version', '1.0.0' );

		return $option;
	}
}

if ( ! function_exists( 'icegram_mailer_get_current_date_time' ) ) {
	/**
	 * Get current date time
	 *
	 * @return false|string
	 */
	function icegram_mailer_get_current_date_time() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'icegram_mailer_get_current_month' ) ) {
	/**
	 * Get current month
	 *
	 * @return false|string
	 */
	function icegram_mailer_get_current_month() {
		return gmdate( 'Y-m' );
	}
}


if ( ! function_exists( 'icegram_mailer_convert_gmt_date_to_local_date' ) ) {
	function icegram_mailer_convert_gmt_date_to_local_date( $gmt_date ) {
		$convert_date_format = get_option( 'date_format' );
		$convert_time_format = get_option( 'time_format' );

		return date_i18n( "$convert_date_format $convert_time_format", strtotime( $gmt_date ) );
	}
}


if ( ! function_exists( 'icegram_mailer_convert_gmt_timestamp_to_local_date' ) ) {

	function icegram_mailer_convert_gmt_timestamp_to_local_date ( $gmt_timestamp) {
		return ! empty( $gmt_timestamp ) ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $gmt_timestamp)) : '';
	}
}

if ( ! function_exists( 'icegram_mailer_get_current_gmt_timestamp' ) ) {
	/**
	 * Get current date time
	 *
	 * @return false|string
	 *
	 * @since 4.2.0
	 */
	function icegram_mailer_get_current_gmt_timestamp() {
		return strtotime( gmdate( 'Y-m-d H:i:s' ) );
	}
}

if ( ! function_exists( 'icegram_mailer_get_current_date' ) ) {
	/**
	 * Get current date
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function icegram_mailer_get_current_date() {
		return gmdate( 'Y-m-d' );
	}
}

if ( ! function_exists( 'icegram_mailer_get_user_name_from_email' ) ) {
	/**
	 * Get current date
	 *
	 * @return false|string
	 *
	 * @since 4.1.15
	 */
	function icegram_mailer_get_user_name_from_email( $email ) {
		if ( false === strpos( $email, '@' ) ) {
			return '';
		}

		return explode( '@', $email )[0];
	}
}

if ( ! function_exists( 'icegram_mailer_encode_request_data' ) ) {
	/**
	 * Encode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function icegram_mailer_encode_request_data( $data ) {
		return rtrim( base64_encode( json_encode( $data ) ), '=' );
	}
}

if ( ! function_exists( 'icegram_mailer_decode_request_data' ) ) {
	/**
	 * Decode request data
	 *
	 * @param $data
	 *
	 * @return string
	 *
	 * @since 4.2.0
	 */
	function icegram_mailer_decode_request_data( $data ) {
		$data = json_decode( base64_decode( $data ), true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		return $data;
	}
}

if ( ! function_exists( 'icegram_mailer_get_ip' ) ) {
	/**
	 * Get Contact IP
	 *
	 * @return mixed|string|void
	 *
	 * @since 4.2.0
	 */
	function icegram_mailer_get_ip() {

		// Get real visitor IP behind CloudFlare network
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_REAL_IP'] );
		} elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_FORWARDED'] );
		} else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'UNKNOWN';
		}

		return $ip;
	}
}
