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
					'nav_button_url'  => $dashboard_url,
					'nav_button_text' => __( 'Dashboard', 'icegram-mailer' )
				]
			);
			?>
		<?php if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div id="message" class="updated notice is-dismissible">
					<p>
						<strong>
							<?php echo esc_html__( 'Settings saved.', 'icegram-mailer' ); ?>
						</strong>
					</p>
				</div>
		<?php } ?>
		<form method="post" class="icegram-mailer-form">
			<div class="w-1/2 mx-auto bg-white p-6 rounded-lg shadow-md mt-4 mx-10">
				<div class="flex">
					<div class="w-1/3">
						<div>
							<label for="enableService" class="text-lg font-semibold">
								<?php echo esc_html__( 'Enable Icegram mailer', 'icegram-mailer' ); ?>
							</label>
							<p class="help-text">
								<?php echo esc_html__( 'Turn this on to allow Icegram Mailer to handle your emails.', 'icegram-mailer' ); ?>
							</p>
						</div>
					</div>
					<div class="w-2/3">
						<input name="icegram-mailer-settings[is-opted-for-ess]" type="checkbox" id="enableService" class="mr-2" <?php checked( $is_opted_for_ess, true, true ); ?> value="yes">
					</div>
				</div>
				<hr class="mt-4">

				<div class="flex mt-4 mb-4">
					<div class="w-1/3">
						<div>
							<label class="text-lg font-semibold">
								<?php echo esc_html__( 'Sender', 'icegram-mailer' ); ?>
							</label>
							<p class="help-text">
								<?php echo esc_html__( 'The "From name", "From email", "Reply-To name" and "Reply-To email" people will see when they receive emails.', 'icegram-mailer' ); ?>
							</p>
						</div>
					</div>
					<div class="w-2/3">  
						<div class="flex gap-2 mt-1">
							<div class="w-1/2">
								<label class="block text-xs font-medium">
									<?php echo esc_html__( 'From name', 'icegram-mailer' ); ?>
								</label>
								<input type="text" name="icegram-mailer-settings[from-name]" class="icegram-mailer-input w-full p-2 border rounded mt-1" value="<?php echo esc_attr( $from_name ); ?>" placeholder="<?php echo esc_attr__( 'Name', 'icegram-mailer' ); ?>">
								
							</div>
							<div class="w-1/2">
								<label class="block text-xs font-medium">
									<?php echo esc_html__( 'From email', 'icegram-mailer' ); ?>
								</label>
								<div class="flex items-center">
									<input type="text" readonly name="icegram-mailer-settings[from-email-user-name]" class="icegram-mailer-input w-full p-2 border rounded-l mt-1" value="<?php echo esc_attr( $from_email_user_name ); ?>@igeml.com" placeholder="<?php echo esc_attr__( 'Email', 'icegram-mailer' ); ?>">									
								</div>
							</div>
						</div> 
						
						<div class="flex gap-2 mt-1">
							<div class="w-1/2">
								<label class="block text-xs font-medium mt-2">
									<?php echo esc_html__( 'Reply-To Name', 'icegram-mailer' ); ?>
								</label>
								<input type="text" 
									name="icegram-mailer-settings[reply-to-name]" 
									class="icegram-mailer-input w-full p-2 border rounded" 
									value="<?php echo esc_attr( $reply_to_name ); ?>"
									placeholder="<?php echo esc_attr__( 'Name', 'icegram-mailer' ); ?>">
							</div>
							<div class="w-1/2">
								<label class="block text-xs font-medium mt-2">
									<?php echo esc_html__( 'Reply-To Email', 'icegram-mailer' ); ?>
								</label>

								<input type="email" 
									name="icegram-mailer-settings[reply-to-email]" 
									class="icegram-mailer-input w-full p-2 border rounded" 
									value="<?php echo esc_attr( $reply_to_email ); ?>"
									placeholder="<?php echo esc_attr__( 'Email', 'icegram-mailer' ); ?>">
							</div>
						</div>
					</div>
				</div>

				<hr class="mt-4">

				<div class="flex mt-4 mb-4">
					<div class="w-1/3">
						<div>
							<label for="tracking" class="text-lg font-semibold">
								<?php echo esc_html__( 'Open tracking', 'icegram-mailer' ); ?>
							</label>
							<p class="help-text">
								<?php echo esc_html__( 'Do you want to track opens when people view your emails?', 'icegram-mailer' ); ?>
							</p>
						</div>
					</div>
					<div class="w-2/3">
						<input type="checkbox" id="open-tracking" name="icegram-mailer-settings[is_open_tracking_enabled]" class="mr-2" value="yes" <?php checked( $is_open_tracking_enabled, 'yes', true ); ?>>
					</div>
				</div>
				
				<hr class="mt-2">

				<div class="flex mt-4 mb-4">
                    <div class="w-1/3">
                        <div>
                            <label for="show-sent-by-icegram" class="text-lg font-semibold">
                                <?php echo esc_html__( 'Show "Sent by Icegram"', 'icegram-mailer' ); ?>
                                <?php if ( $is_free_plan ) : ?>
                                    <a href="<?php echo esc_url( Icegram_Mailer_Common::get_utm_tracking_url( array( 'utm_source' => 'in_app', 'utm_medium' => 'settings', 'utm_campaign' => 'upgrade_to_premium' ) ) ); ?>" target="_blank" class="inline-flex items-center ml-2" title="<?php echo esc_attr__( 'Upgrade to Premium', 'icegram-mailer' ); ?>">
                                        <img src="<?php echo esc_url( ICEGRAM_MAILER_PLUGIN_URL . '/admin/images/crown-icon.svg' ); ?>" alt="<?php echo esc_attr__( 'Upgrade to Premium', 'icegram-mailer' ); ?>" class="w-4 h-4 inline-block" />
                                    </a>
                                <?php endif; ?>
                            </label>
                            <p class="help-text">
                                <?php echo esc_html__( 'Include "Sent by Icegram" link in the footer of your emails.', 'icegram-mailer' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="w-2/3">
                        <input type="checkbox" id="show-sent-by-icegram" name="icegram-mailer-settings[branding_enabled]" class="mr-2 <?php echo $is_free_plan ? 'opacity-50 cursor-not-allowed' : ''; ?>" value="yes" <?php checked( $branding_enabled, 'yes', true ); ?> <?php disabled( $is_free_plan, true, true ); ?>>
                    </div>
                </div>

				<hr class="mt-2">
				
				<div class="flex mt-4 mb-4">
					<div class="w-1/3">
						<div>
							<label class="text-lg font-semibold">
								<?php echo esc_html__( 'Email testing', 'icegram-mailer' ); ?>
							</label>
							<p class="help-text">
                                <?php echo esc_html__( 'Enter email address to test your configuration.', 'icegram-mailer' ); ?>
                            </p>
						</div>
					</div>
					<div class="w-2/3">
						<input id="icegram-mailer-test-email" type="text" class="icegram-mailer-input w-full p-2 border rounded mt-2" placeholder="<?php echo esc_attr__( 'Enter email', 'icegram-mailer' ); ?>" value="<?php echo esc_attr( $test_email ); ?>">
					
					<div>
					<button id="icegram-mailer-send-test-email" class="lighter-gray mt-2 px-4 py-2 text-white rounded">
						<span class="button-text">
							<?php echo esc_html__( 'Send email', 'icegram-mailer' ); ?>
						</span>
						<svg aria-hidden="true" role="status" class="loader hidden inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
							<path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
						</svg>
					</button>
					<div id="icegram-mailer-send-result-message"></div>
					</div>
					</div>
				</div>
				
				<div class="flex justify-end">
					<input type="hidden" name="icegram-mailer-form-submitted" value="submitted" />
					<?php
						wp_nonce_field( 'icegram-mailer-save-settings' );
					?>
					<button type="submit" class="primary">
						<?php echo esc_html__( 'Save', 'icegram-mailer' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>
</div>
