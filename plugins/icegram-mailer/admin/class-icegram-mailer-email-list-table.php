<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Icegram_Mailer_Email_List_Table extends Icegram_Mailer_List_Table {
	/**
	 * Contact lists status array
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $contact_lists_statuses = array();

	/**
	 * Number of contacts per page
	 *
	 * @since 4.2.1
	 *
	 * @var string
	 */
	public static $option_per_page = 'emails_per_page';

	/**
	 * Array of list ids
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $list_ids = array();

	/**
	 * List name mapped to id
	 *
	 * @since 4.0.0
	 * @var array
	 */
	public $lists_id_name_map = array();

	/**
	 * Last opened at
	 *
	 * @since 4.6.5
	 * @var array
	 */
	public $items_data = array();

	/**
	 * Contacts database object
	 *
	 * @var object|ES_DB_Contacts
	 */
	public $db;

	/**
	 * ES_Contacts_Table constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Email Log', 'icegram-mailer' ),
				'plural'   => __( 'Email Logs', 'icegram-mailer' ),
				'ajax'     => false,
				'screen'   => 'icegram_mailer_dashboard',
			)
		);

		$this->db = new Icegram_Mailer_Logs_Table();
		$this->process_bulk_action();
	}

	/**
	 * Add Screen Option
	 *
	 * @since 4.2.1
	 */
	public static function screen_options() {

		if ( empty( $action ) ) {

			$option = 'per_page';
			$args   = array(
				'label'   => __( 'Number of email logs per page', 'icegram-mailer' ),
				'default' => 100,
				'option'  => self::$option_per_page,
			);

			add_screen_option( $option, $args );
		}

	}

	/**
	 * Render Audience View
	 *
	 * @since 4.2.1
	 */
	public function render() {
		?>
		<div class="font-sans">
			<div id="poststuff" class="icegram-mailer-email-log-list icegram-mailer-items-list">
				<div id="post-body" class="metabox-holder column-1">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
 							<form id="email-logs-form" method="get">
								<input type="hidden" name="page" value="icegram_mailer_dashboard" />
								
								<?php
								// Preserve current status filter
								$current_status = $this->get_current_status();
								if ( ! empty( $current_status ) ) {
									echo '<input type="hidden" name="status" value="' . esc_attr( $current_status ) . '" />';
								}
								?>

								<!-- Display views (All, Sent, Failed) -->
								<?php $this->views(); ?>						
							
								<!-- Prepare and display table with bulk actions -->
								<?php
								$this->prepare_items();
								$this->display();
								?>
							</form>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
	}

	/**
	 * Retrieve subscribers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public function get_lists( $per_page = 5, $page_number = 1, $do_count_only = false ) {
		$args = array(
			'per_page'    => $per_page,
			'page_number' => $page_number,
			'do_count_only' => $do_count_only
		);

		// Add status filter if present
		$current_status = $this->get_current_status();
		if ( ! empty( $current_status ) ) {
			$args['status'] = $current_status;
		}

		// Add search filter if present
		$search = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : '';
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}
		
		return Icegram_Mailer_Dashboard_Controller::get_lists( $args );
		
	}


	/**
	 * No contacts available
	 *
	 * @since 4.0.0
	 */
	public function no_items() {
		esc_html_e( 'No email logs available.', 'icegram-mailer' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 *
	 * @since 4.0.0
	 */
	public function column_default( $item, $column_name ) {
		$item = apply_filters( 'icegram_mailer_email_logs_data', $item, $column_name, $this );
		switch ( $column_name ) {
			case 'created_at':
				return icegram_mailer_convert_gmt_timestamp_to_local_date( $item[ 'created_at' ] );
			case 'opened_at':
				return ! empty( $item[ 'opened_at' ] ) ? icegram_mailer_convert_gmt_timestamp_to_local_date( $item[ 'opened_at' ] ) : '-';
			case 'status':
				$status = $item[ 'status' ];
				return ! empty( $status ) ? ( '<span class="icegram-mailer-email-' . $item['status'] . '" ' . ( 'failed' === $status ? 'title="' . $item['error'] . '"' : '' ) . '>' . ucfirst( $status ) . '</span>' ) : '-';
			default:
				$column_data = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '-';
				return apply_filters( 'icegram_mailer_contact_column_data', $column_data, $column_name, $item, $this );
		}
	}

	/**
	 * Render checkbox column
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 *
	 * @since 1.0.8
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" class="checkbox" name="email_ids[]" value="%s"/>',
			$item['id']
		);
	}

	/**
	 * Render subject column with row actions
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 *
	 * @since 1.0.8
	 */
	public function column_subject( $item ) {
		$subject = ! empty( $item['subject'] ) ? esc_html( $item['subject'] ) : __( '(no subject)', 'icegram-mailer' );

		$actions = array();

		// Add resend action.
		$actions['resend'] = sprintf(
			'<a href="#" class="icegram-mailer-resend-email" data-email-id="%d">%s</a>',
			$item['id'],
			__( 'Resend', 'icegram-mailer' )
		);

		$actions['delete'] = sprintf(
			'<a href="#" class="icegram-mailer-delete-email" data-email-id="%d">%s</a>',
			$item['id'],
			__( 'Delete', 'icegram-mailer' )
		);

		return sprintf( '%1$s %2$s', $subject, $this->row_actions( $actions ) );
	}

	/**
	 * Associative array of columns
	 *
	 * @return array
	 *
	 * @since 4.0.0
	 */
	public function get_columns() {

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'subject'    => __( 'Subject', 'icegram-mailer' ),
			'to'         => __( 'To', 'icegram-mailer' ),
			'status'     => __( 'Status', 'icegram-mailer' ),
			'created_at' => __( 'Sent at', 'icegram-mailer' ),
			'opened_at'  => __( 'Opened at', 'icegram-mailer' ),
		);

		return $columns;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 *
	 * @since 4.0.0
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		$per_page     = $this->get_items_per_page( self::$option_per_page, 20 );
		$current_page = $this->get_pagenum();
 
		$total_items  = $this->get_lists( 0, 0, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = $this->get_lists( $per_page, $current_page );
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 *
	 * @since 1.0.8
	 */
	protected function get_bulk_actions() {
		return array(
			'bulk_resend' => __( 'Resend', 'icegram-mailer' ),
			'bulk_delete' => __( 'Delete', 'icegram-mailer' ),
		);
	}

	/**
	 * Get filters to preserve in redirects
	 *
	 * @return array Filters to preserve
	 * @since 1.0.8
	 */
	protected function get_preserved_query_vars() {
		$filters = array();
		
		// Preserve status filter if set
		if ( ! empty( $_GET['status'] ) ) {
			$filters['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}
		
		// Preserve search query if not empty
		if ( ! empty( $_GET['s'] ) ) {
			$filters['s'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
		
		// Preserve pagination if set
		if ( ! empty( $_GET['paged'] ) ) {
			$filters['paged'] = absint( $_GET['paged'] );
		}
		
		return $filters;
	}

	/**
	 * Process bulk actions
	 *
	 * @since 1.0.8
	 */
	public function process_bulk_action() {
		$action = $this->current_action();

		if ( 'bulk_resend' === $action ) {
			// Check nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed', 'icegram-mailer' ) );
			}

			// Check user permission
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to delete email logs.', 'icegram-mailer' ) );
			}

			$email_ids = isset( $_REQUEST['email_ids'] ) ? array_map( 'absint', $_REQUEST['email_ids'] ) : array();

			if ( ! empty( $email_ids ) ) {
				$resent_count = 0;
				$failed_count = 0;

				foreach ( $email_ids as $email_id ) {
					$result = Icegram_Mailer_Dashboard_Controller::resend_email( array( 'email_id' => $email_id ) );
					if ( ! empty( $result['status'] ) && 'success' === $result['status'] ) {
						$resent_count++;
					} else {
						$failed_count++;
					}
				}

				$redirect_url = remove_query_arg( array( 'action', 'action2', 'email_ids', '_wpnonce', '_wp_http_referer', 'bulk_action', 's', 'status', 'paged' ) );
                
				$redirect_args = array_merge(
					array(
						'resent' => $resent_count,
						'failed' => $failed_count,
					),
					$this->get_preserved_query_vars()
				);

                
                $redirect_url = add_query_arg( $redirect_args, $redirect_url );

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		if ( 'bulk_delete' === $action ) {
			
			// Check nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( esc_html__( 'Security check failed', 'icegram-mailer' ) );
			}

			// Check user permission
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to delete email logs.', 'icegram-mailer' ) );
			}

			$email_ids = isset( $_REQUEST['email_ids'] ) ? array_map( 'absint', $_REQUEST['email_ids'] ) : array();

			if ( ! empty( $email_ids ) ) {
				$deleted_count = 0;

				foreach ( $email_ids as $email_id ) {
					$deleted = icegram_mailer()->email_logs_table->delete( $email_id );
					if ( $deleted ) {
						$deleted_count++;
					}
				}

				// Remove all bulk action related parameters
                $redirect_url = remove_query_arg( array( 'action', 'action2', 'email_ids', '_wpnonce', '_wp_http_referer', 'bulk_action', 's', 'status', 'paged' ) );

				$redirect_args = array_merge(
					array( 'deleted' => $deleted_count ),
					$this->get_preserved_query_vars()
				);
                
                $redirect_url = add_query_arg( $redirect_args, $redirect_url );

				wp_safe_redirect( $redirect_url );
				exit;
			}
		} 

	}


	/**
	 * Get current status from URL parameter
	 *
	 * @return string Current status or empty for all
	 * @since 1.0.8
	 */
	protected function get_current_status() {
		$status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$valid_statuses = array( 'sent', 'failed' );
		
		return in_array( $status, $valid_statuses, true ) ? $status : '';
	}


	/**
	 * Get available views/filters for the list table
	 *
	 * @return array Array of view links
	 * @since 1.0.8
	 */
	public function get_views() {
		$current_status = $this->get_current_status();
		$base_url = admin_url( 'admin.php?page=icegram_mailer_dashboard' );
		
		// Get counts for each status
		$total_count = icegram_mailer()->email_logs_table->get_logs( array(), ARRAY_A, true );

		$sent_count = icegram_mailer()->email_logs_table->get_logs( array( 'status' => 'sent' ), ARRAY_A, true );
		
		$failed_count = icegram_mailer()->email_logs_table->get_logs( array( 'status' => 'failed' ), ARRAY_A, true );
		
		$views = array();
		
		// All emails
		$views['all'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			$base_url,
			empty( $current_status ) ? 'current' : '',
			__( 'All', 'icegram-mailer' ),
			$total_count
		);
		
		// Sent emails
		$views['sent'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			add_query_arg( 'status', 'sent', $base_url ),
			'sent' === $current_status ? 'current' : '',
			__( 'Sent', 'icegram-mailer' ),
			$sent_count
		);
		
		// Failed emails
		$views['failed'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			add_query_arg( 'status', 'failed', $base_url ),
			'failed' === $current_status ? 'current' : '',
			__( 'Failed', 'icegram-mailer' ),
			$failed_count
		);
		
		return $views;
	}

	/**
	 * Display search box
	 *
	 * @param string $text The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 *
	 * @since 1.0.8
	 */
	public function search_box( $text = '', $input_id = 'search' ) {
		if ( empty( $text ) ) {
			$text = __( 'Search Logs', 'icegram-mailer' );
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e( 'Search email or subject...', 'icegram-mailer' ); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Display the list table with views/filters above
	 *
	 * @since 1.0.8
	 */
	public function display() { 
		
		// Display the standard table
		parent::display();
	}
}
