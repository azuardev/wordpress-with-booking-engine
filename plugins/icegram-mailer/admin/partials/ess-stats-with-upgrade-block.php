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
<div class="grid max-w-4xl mx-auto mt-4">
	<div class="flex justify-between items-center">
		<div class="h-full py-4 rounded-lg w-1/2">
			<div class="bg-white shadow-lg rounded-lg p-4 mr-4">
				<div class="flex justify-between items-center">
					<p class="text-lg font-medium leading-6 text-gray-400 text-left w-1/3">
						<?php echo esc_html__( 'Plan usage', 'icegram-mailer' ); ?>
					</p>
					<p class="text-sm font-medium leading-6 text-gray-400 text-right w-2/3" id="ig-mailer-next-reset">
						<?php echo ! empty( $next_reset_date ) ? esc_html__( 'resets on ', 'icegram-mailer' ) . esc_html( $next_reset_date ) : ''; ?>
					</p>
				</div>
				<div class="flex justify-between items-center my-2">
					<p class="mt-1 font-medium leading-6 text-gray-500 text-left w-1/3">
						<?php echo esc_html__( 'Email sent', 'icegram-mailer' ); ?>
					</p>
					<p class="mt-1 font-medium text-gray-500 text-right w-2/3" id="ig-mailer-email-sent">
						<?php
							/* translators: 1. Used limit count 2. Allocated limit 3. Used limit percentage */
							echo sprintf( esc_html__( '%1$s of %2$s ( %3$s%% ) used', 'icegram-mailer' ), esc_html( number_format_i18n( $used_limit ) ), esc_html( number_format_i18n( $allocated_limit ) ), esc_html( number_format_i18n( $percentage_used, 2 ) ) );
						?>
					</p>
				</div>
				<div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
					<div class="<?php echo esc_html( $progress_bar_class ); ?> h-2.5 rounded-full" id="ig-mailer-progress-bar" style="width: <?php echo esc_attr( $percentage_used ); ?>%"></div>
				</div>
				<div class="flex justify-between items-center mt-1">
					<p class="font-medium leading-6 text-gray-500" id="ig-mailer-remaining">
						<?php
							/* translators: 1. Remaining limit count 2. Remaining limit percentage */
							echo sprintf( esc_html__( '%1$s ( %2$s%% ) remaining', 'icegram-mailer' ), esc_html( number_format_i18n( $remaining_limit ) ), esc_html( number_format_i18n( $percentage_remaining, 2 ) ) );
						?>
					</p>
					<button class="refresh-now-btn flex items-center px-3 py-1 text-gray-500 outline-none shadow-none focus:outline-none focus:ring-0 focus:shadow-none" id="refresh-now-btn">
						<svg class="w-4 h-4 mt-0 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
						</svg>
						<?php echo esc_html__( 'Refresh now', 'icegram-mailer' ); ?>
					</button>
				</div>
			</div>
			<div class="bg-white shadow-lg rounded-lg p-4 mt-4 mr-4">
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
		<div class="h-full py-4 pl-0 rounded-lg w-1/2">
			<div class="h-full bg-white shadow-lg rounded-lg p-4">
				<div>
					<p class="text-lg font-medium leading-6 text-gray-400 text-left ">
						<?php echo esc_html__( 'Ready to send more?', 'icegram-mailer' ); ?>
					</p>
					<p class="mt-1 font-medium leading-6 text-gray-500 text-left">
						<?php echo esc_html__( 'Unlock higher email sending limits and elevate your communication strategy.', 'icegram-mailer' ); ?>
					</p>
					<ul class="py-3 space-y-2 text-sm font-medium leading-5 text-gray-400">
						<li class="flex items-start group">
							<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
								<span></span>
							</div>
							<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
								<?php echo esc_html__( 'Substantially increased monthly sending capacity', 'icegram-mailer' ); ?>
							</p>
						</li>
						<li class="flex items-start group">
							<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
								<span></span>
							</div>
							<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'Optimized performance for high-volume campaigns', 'icegram-mailer' ); ?></p>
						</li>
						<li class="flex items-start group">
							<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
								<span></span>
							</div>
							<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
							<?php echo esc_html__( 'Access to expert support when you need it', 'icegram-mailer' ); ?>
							</p>
						</li>
					</ul>
					<a href="https://www.icegram.com/mailer/?utm_source=in_app&utm_medium=icegram-mailer&utm_campaign=ess_upsell" >
						<button  class="primary">
							<?php echo esc_html__( 'Upgrade plan', 'icegram-mailer' ); ?>
						</button>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
