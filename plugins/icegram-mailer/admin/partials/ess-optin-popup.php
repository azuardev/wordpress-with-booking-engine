<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="form-fields ig-es-popup-container">
	<div class="ig-es-popup-overlay"></div>
	<div class="ig-es-popup w-1/3">
		<div class="px-8 py-6">
			<div class="mt-3 sm:mt-5">
				<div id="sending-service-benefits" class="<?php echo 1 !== $ess_onboarding_step ? 'hidden' : ''; ?>">
					<div class="text-center">
						<img class="inline-block" width="180" src="<?php echo esc_url(  ICEGRAM_MAILER_PLUGIN_URL . '/admin/images/overview-snippet.png'); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" alt="">
					</div>
					<h1 class="modal-headline text-center " id="modal-title">
						<?php echo esc_html__( 'Reliable email delivery with Icegram Mailer', 'icegram-mailer' ); ?>
					</h1>
					<div class="step-1  block-description">
						<ul class="py-3 space-y-2 text-sm font-medium leading-5 text-gray-400">
							<li class="flex items-start group">
								<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
									<span></span>
								</div>
								<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'Start with 200 free emails/month', 'icegram-mailer' ); ?></p></li>
							<li class="flex items-start group">
								<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
									<span></span>
								</div>
								<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500"><?php echo esc_html__( 'High speed email sending', 'icegram-mailer' ); ?></p>
							</li>
							<li class="flex items-start group">
								<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
									<span></span>
								</div>
								<p class="ml-1 xl:pr-3 2xl:pr-0 text-sm text-gray-500">
								<?php echo esc_html__( 'Reliable email delivery', 'icegram-mailer' ); ?>
								</p>
							</li>
						</ul>
						<div class="text-center">
							<a id="ig-ess-optin-cta" href="#" class="mt-8">
								<button type="button" class="primary">
									<?php echo esc_html__( 'Start sending', 'icegram-mailer' ); ?> &rarr;
								</button>
							</a>
						</div>
					</div>
				</div>
				<div id="sending-service-onboarding-tasks-list" class="<?php echo 2 !== $ess_onboarding_step ? 'hidden' : ''; ?>">
				<div class="text-center">
						<img class="inline-block" width="180" src="<?php echo esc_url(  ICEGRAM_MAILER_PLUGIN_URL . '/admin/images/overview-snippet.png'); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" alt="">
					</div>
					<h1 class="modal-headline text-center " id="modal-title">
							<?php echo esc_html__( 'Excellent! activating your free Plan', 'icegram-mailer' ); ?>
					</h1>
					
					<ul class="pt-2 pb-1 space-y-2 text-sm font-medium leading-5 text-gray-400 pt-2">
						<li id="ig-es-onboard-create_ess_account" class="flex items-start space-x-3 group">
							<div class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5">
							<span class="animate-ping absolute w-4 h-4 bg-indigo-200 rounded-full"></span>
							<span class="relative block w-2 h-2 bg-indigo-700 rounded-full"></span>
							</div>
							<p class="text-sm text-indigo-800">
							<?php
							/* translators: 1: Main List 2: Test List */
							echo esc_html__( 'Creating your account', 'icegram-mailer' );
							?>
							</p>
						</li>
						<li id="ig-es-onboard-dispatch_emails_from_server" class="flex items-start space-x-3 group">
							<div
							class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5"
							>
							<span
								class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
							</div>
							<p class="text-sm"><?php echo esc_html__( 'Sending a test email', 'icegram-mailer' ); ?></p>
						</li>
						<li id="ig-es-onboard-check_test_email_on_server" class="flex items-start space-x-3 group">
							<div
							class="item-dots relative flex items-center justify-center flex-shrink-0 w-5 h-5"
							>
							<span
								class="block w-2 h-2 transition duration-150 ease-in-out bg-gray-300 rounded-full group-hover:bg-gray-400 group-focus:bg-gray-400"
							></span>
							</div>
							<p class="text-sm">
								<?php echo esc_html__( 'Confirming email delivery', 'icegram-mailer' ); ?>
							</p>
						</li>
					</ul>
					<div class="text-center">
					<a id="ig-es-complete-ess-onboarding" href="#" class="mt-8 <?php echo 2 === $ess_onboarding_step ? '' : 'opacity-50 pointer-events-none'; ?>">
						<button type="button" class="primary mt-4">
							<span class="button-text inline-block mr-1">
							<?php echo esc_html__( 'Processing...', 'icegram-mailer' ); ?>
							</span>
							<svg style="display:none" class="es-btn-loader h-4 w-4 text-white-600 mt-0.5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
							</svg>
						</button>
					</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
