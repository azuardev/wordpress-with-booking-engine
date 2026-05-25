<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Icegram_Mailer_List_Table extends WP_List_Table {

	/**
	 * Hide top pagination but keep bulk actions
	 *
	 * @param string $which
	 *
	 * @since 4.6.6
	 */
	public function pagination( $which ) {

		if ( 'bottom' == $which ) {
			parent::pagination( $which );
		}
	}

	/**
	 * Display extra tablenav (top only for bulk actions)
	 *
	 * @param string $which
	 *
	 * @since 1.0.8
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			// Show search box aligned with bulk actions
			if ( method_exists( $this, 'search_box' ) ) {
                $this->search_box( __( 'Search Logs', 'icegram-mailer' ), 'email-log' );
            }
		}
	}

}
