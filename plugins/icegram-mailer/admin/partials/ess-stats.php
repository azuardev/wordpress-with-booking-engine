<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$progress_bar_class = '';
if ( $percentage_used < 50 ) {
	$progress_bar_class = 'bg-indigo-600';
} elseif ( $percentage_used >= 50 && $percentage_used < 80 ) {
	$progress_bar_class = 'bg-yellow-600';
} elseif ( $percentage_used >= 80 && $percentage_used < 100 ) {
	$progress_bar_class = 'bg-orange-600';
} else {
	$progress_bar_class = 'bg-red-600';
}
?>
<div class="grid max-w-4xl mx-auto p-6 mt-4">
	<div class="flex justify-between items-center">
		<div class="h-full p-4 rounded-lg w-1/2 bg-white shadow-lg rounded-lg mr-4">
			<div class="flex justify-between items-center">
				<p class="text-lg font-medium leading-6 text-gray-400 text-left w-1/3">
					<?php echo esc_html__( 'Plan usage', 'icegram-mailer' ); ?>
				</p>
				<p class="text-sm font-medium leading-6 text-gray-400 text-right w-2/3">
					<?php echo ! empty( $next_reset_date ) ? esc_html__( 'resets on ', 'icegram-mailer' ) . esc_html( $next_reset_date ) : ''; ?>
				</p>
			</div>
			<div class="flex justify-between items-center my-2">
				<p class="mt-1 font-medium leading-6 text-gray-500 text-left w-1/3">
					<?php echo esc_html__( 'Email sent', 'icegram-mailer' ); ?>
				</p>
				<p class="mt-1 font-medium text-gray-500 text-right w-2/3">
					<?php
						/* translators: 1. Used limit count 2. Allocated limit 3. Used limit percentage */
						echo sprintf( esc_html__( '%1$s of %2$s ( %3$s%% ) used', 'icegram-mailer' ), esc_html( number_format_i18n( $used_limit ) ), esc_html( number_format_i18n( $allocated_limit ) ), esc_html( number_format_i18n( $percentage_used, 2 ) ) );
					?>
				</p>
			</div>
			<div class="w-full bg-gray-200 rounded-full h-2. mb-2">
				<div class="<?php echo esc_html( $progress_bar_class ); ?> h-2.5 rounded-full" style="width: <?php echo esc_attr( $percentage_used ); ?>%"></div>
			</div>
			<p class="mt-1 font-medium leading-6 text-gray-500">
				<?php
					/* translators: 1. Remaining limit count 2. Remaining limit percentage */
					echo sprintf( esc_html__( '%1$s ( %2$s%% ) remaining', 'icegram-mailer' ), esc_html( number_format_i18n( $remaining_limit ) ), esc_html( number_format_i18n( $percentage_remaining, 2 ) ) );
				?>
			</p>
		</div>
		<div class="h-full p-4 rounded-lg w-1/2 bg-white shadow-lg rounded-lg">
			<div class="flex justify-between items-center">
				<p class="text-lg font-medium leading-6 text-gray-400 text-left w-1/2">
					<?php echo esc_html__( 'Insights', 'icegram-mailer' ); ?>
				</p>
				<p class="text-sm font-medium leading-6 text-gray-400 text-right w-1/2">
					<?php echo esc_html__( 'Last 30 days', 'icegram-mailer' ); ?>
				</p>
			</div>
			<div class="flex justify-between items-center mt-3">
				<div class="p-1 mr-6 kpi-div text-center">
					<span class="text-2xl font-bold leading-none text-indigo-600">
						<?php echo esc_html( number_format_i18n( $total_sent ) ); ?>
					</span>
					<p class="mt-1 font-medium leading-6 text-gray-500">
						<?php echo esc_html__( 'Total sent', 'icegram-mailer' ); ?>
					</p>
				</div>
				<div class="p-1 mr-6 kpi-div text-center">
					<span class="text-2xl font-bold leading-none text-indigo-600">
						<?php echo esc_html( number_format_i18n( $total_opened ) ); ?>
					</span>
					<p class="mt-1 font-medium leading-6 text-gray-500">
						<?php echo esc_html__( 'Total opened', 'icegram-mailer' ); ?>
					</p>
				</div>
				<div class="p-1 mr-6 kpi-div text-center">
					<span class="text-2xl font-bold leading-none text-indigo-600">
						<?php echo esc_html( number_format_i18n( $total_failed ) ); ?>
					</span>
					<p class="mt-1 font-medium leading-6 text-gray-500">
						<?php echo esc_html__( 'Total failed', 'icegram-mailer' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
