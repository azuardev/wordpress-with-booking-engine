<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="icegram-mailer-container" class="font-sans dark">
	<div class="sticky top-0 z-10">
	<?php
		Icegram_Mailer_Admin::get_view(
			'header',
			[
				'nav_button_url' => $settings_url,
				'nav_button_text' => __( 'Settings', 'icegram-mailer' )
			]
		);
		if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
			Icegram_Mailer_Admin::get_view( 
				'ess-optin-popup',
				[
					'ess_onboarding_step' => 1,
				]
			);
		} else {
			$allocated_limit      = ! empty( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit'] : 0;
			$current_month        = icegram_mailer_get_current_month();
			/** TODO: Remove this fix. In Older mailer version used_limit stored in ess_data['used_limit'][$current_month] format. New version format ess_data['used_limit'] */
			$ess_data['used_limit'] = ! empty( $ess_data['used_limit'][$current_month] ) ? $ess_data['used_limit'][$current_month] : 0;
			$used_limit           = ! empty( $ess_data['used_limit'] ) ? $ess_data['used_limit'] : 0;
			$next_reset_date      = ! empty( $ess_data['next_reset'] ) ? icegram_mailer_convert_gmt_date_to_local_date( $ess_data['next_reset'] ): '';
			$percentage_used      = $allocated_limit > 0 ? ( ( $used_limit * 100 ) / $allocated_limit ) : 0;
			$remaining_limit      = $allocated_limit > 0 ? $allocated_limit - $used_limit : 0;
			$percentage_remaining = 100 - $percentage_used;

			$total_sent   = icegram_mailer()->email_logs_table->get_logs( array( 'created_within_days' => 30, 'status' => 'sent' ), ARRAY_A, true );
			$total_opened = icegram_mailer()->email_logs_table->get_logs( array( 'opened_within_days' => 30 ), ARRAY_A, true );
			$total_failed = icegram_mailer()->email_logs_table->get_logs( array( 'created_within_days' => 30, 'status' => 'failed' ), ARRAY_A, true );
			
			$view_name = did_action( 'icegram_mailer_notice_displayed_limit_exhausted' ) || did_action( 'icegram_mailer_notice_displayed_limit_expiring' ) ? 'ess-stats' : 'ess-stats-with-upgrade-block';
			Icegram_Mailer_Admin::get_view( 
				$view_name,
				[
					'allocated_limit'      => $allocated_limit,
					'used_limit'           => $used_limit,
					'next_reset_date'      => $next_reset_date,
					'percentage_used'      => $percentage_used,
					'remaining_limit'      => $remaining_limit,
					'percentage_remaining' => $percentage_remaining,
					'total_sent'           => $total_sent,
					'total_opened'         => $total_opened,
					'total_failed'         => $total_failed,
				]
			);

			$email_list_table = new Icegram_Mailer_Email_List_Table();
			$email_list_table->render();
		}
		?>
	</div>
</div>
