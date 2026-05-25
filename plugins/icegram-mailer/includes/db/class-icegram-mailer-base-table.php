<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icegram_Mailer_Base_Table base class
 *
 * @since 4.0
 */
abstract class Icegram_Mailer_Base_Table {

	/**
	 * Table name
	 *
	 * @since 4.0.0
	 * @var $table_name
	 */
	public $table_name;

	/**
	 * Table DB version
	 *
	 * @since 4.0.0
	 * @var $version
	 */
	public $version;

	/**
	 * Table primary key column name
	 *
	 * @since 4.0.0
	 * @var $primary_key
	 */
	public $primary_key;

	/**
	 * Icegram_Mailer_Base_Table constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
	}

	/**
	 * Get default columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Get columns default values
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Insert a new row
	 *
	 * @param $data
	 * @param string $type
	 *
	 * @return int
	 *
	 * @since 4.0.0
	 */
	public function insert( $data, $type = '' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'icegram_mailer_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		$wpdb_insert_id = $wpdb->insert_id;

		do_action( 'icegram_mailer_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Update a specific row
	 *
	 * @param $row_id
	 * @param array  $data
	 * @param string $where
	 *
	 * @return bool
	 *
	 * @since 4.0.0
	 */
	public function update( $row_id, $data = array(), $where = '' ) {

		global $wpdb;

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );
		
		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return false;
		}
		return true;
	}

}
