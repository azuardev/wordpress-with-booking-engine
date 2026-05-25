<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Icegram_Mailer_Logs_Table extends Icegram_Mailer_Base_Table {
	/**
	 * Table Name
	 *
	 * @since 4.2.1
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Version
	 *
	 * @since 4.2.1
	 * @var $version
	 */
	public $version;

	/**
	 * Primary Key
	 *
	 * @since 4.2.1
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * ES_DB_Lists constructor.
	 *
	 * @since 4.2.1
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'icegram_mailer_email_logs';
		$this->primary_key = 'id';
		$this->version     = '1.0';
	}

	/**
	 * Get table columns
	 *
	 * @return array
	 *
	 * @since 4.2.1
	 */
	public function get_columns() {
		return array(
			'id'          => '%d',
			'to'          => '%s',
			'tracking_id'          => '%s',
			'subject'     => '%s',
			'headers'     => '%s',
			'body'        => '%s',
			'status'      => '%s',
			'attachments' => '%s',
			'error'       => '%s',
			'created_at'  => '%d',
			'updated_at'  => '%d',
			'opened_at'   => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  4.2.1
	 */
	public function get_column_defaults() {
		return array(
			'to'         => '',
			'tracking_id'         => '',
			'subject'    => '',
			'headers'    => '',
			'body'       => '',
			'attachments' => '',
			'status'     => '',
			'error'       => '',
			'created_at' => icegram_mailer_get_current_gmt_timestamp(),
			'updated_at' => icegram_mailer_get_current_gmt_timestamp(),
			'opened_at'  => null,
		);
	}

	/**
	 * Get workflows based on arguements
	 *
	 * @param  array   $query_args    Query arguements.
	 * @param  string  $output        Output format.
	 * @param  boolean $do_count_only Count only flag.
	 *
	 * @return mixed $result Query result
	 *
	 * @since 4.4.1
	 */
	public function get_logs( $query_args = array(), $output = ARRAY_A, $do_count_only = false ) {

		global $wpdb;
		if ( $do_count_only ) {
			$sql = 'SELECT count(*) as total FROM ' . $this->table_name;
		} else {
			$sql = 'SELECT ';
			if ( ! empty( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
				$sql .= implode( ' ,', $query_args['fields'] );
			} else {
				$sql .= '*';
			}

			$sql .= ' FROM ' . $this->table_name;
		}

		$args  = array();
		$query = array();

		if ( isset( $query_args['status'] ) ) {
			$query[] = 'status = %s';
			$args[]  = $query_args['status'];
		}

		if ( ! empty( $query_args['created_within_days'] ) ) {
			$query[] = 'created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))';
			$args[]  = esc_sql( $query_args['created_within_days'] );
		}

		if ( ! empty( $query_args['opened_within_days'] ) ) {
			$query[] = 'opened_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %d DAY))';
			$args[]  = esc_sql( $query_args['opened_within_days'] );
		}

		if ( ! empty( $query_args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $query_args['search'] ) . '%';
			$query[] = '(`to` LIKE %s OR subject LIKE %s)';
			$args[]  = $search_term;
			$args[]  = $search_term;
		}

		if ( count( $query ) > 0 ) {
			$sql .= ' WHERE ';

			$sql .= implode( ' AND ', $query );

			if ( count( $args ) > 0 ) {
				$sql = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		if ( ! $do_count_only ) {

			$order                 = ! empty( $query_args['order'] ) ? strtolower( $query_args['order'] ) : 'desc';
			$expected_order_values = array( 'asc', 'desc' );
			if ( ! in_array( $order, $expected_order_values, true ) ) {
				$order = 'desc';
			}

			$default_order_by = esc_sql( 'created_at' );

			$expected_order_by_values = array( 'created_at', 'opened_at' );
			if ( empty( $query_args['order_by'] ) || ! in_array( $query_args['order_by'], $expected_order_by_values, true ) ) {
				$order_by_clause = " ORDER BY {$default_order_by} DESC";
			} else {
				$order_by        = esc_sql( $query_args['order_by'] );
				$order_by_clause = " ORDER BY {$order_by} {$order}, {$default_order_by} DESC";
			}

			$sql .= $order_by_clause;

			if ( ! empty( $query_args['per_page'] ) ) {
				$sql .= ' LIMIT ' . $query_args['per_page'];
				if ( ! empty( $query_args['page_number'] ) ) {
					$sql .= ' OFFSET ' . ( $query_args['page_number'] - 1 ) * $query_args['per_page'];
				}
			}

			$result = $wpdb->get_results( $sql, $output ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		} else {
			$result = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return $result;
	}

	/**
	 * Get single email log by ID
	 *
	 * @param int $id Email log ID.
	 * @return array|null Email log data or null if not found.
	 * @since 1.0.8
	 */
	public function get( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( empty( $id ) ) {
			return null;
		}

		$sql = $wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		);

		return $wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Delete email log by ID
	 *
	 * @param int $id Email log ID.
	 * @return bool True on success, false on failure.
	 * @since 1.0.8
	 */
	public function delete( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( empty( $id ) ) {
			return false;
		}

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return (bool) $result;
	}
}
