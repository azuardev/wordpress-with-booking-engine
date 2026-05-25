<?php
/**
 * Email template for failed emails batch notification
 *
 * Available variables: 
 * @var int    $failure_count Total number of failures
 * @var int    $interval_minutes Interval in minutes
 * @var array  $processed_failed_emails Array of processed failed email data
 * @var string $logo_url Logo URL
 * @var string $site_name Site name
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body>
<div class="email-body" style="background: #F7F7F7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; padding: 40px 20px;">
	<div class="container" style="max-width: 600px; margin: 0 auto;">
		<div class="email-wrapper" style="background: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.05);"> 
			<div class="email-content" style="padding: 48px 40px; text-align: center;">
				<!-- Header Section -->
				<div class="email-header" style="margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid #E5E7EB;">
					<!-- Branding Icon -->
					<?php include ICEGRAM_MAILER_PLUGIN_PATH . '/admin/templates/emails/partials/email-header.php'; ?>
					
					<!-- Title -->
					<h1 class="heading" style="color: #1a202c; margin: 0 0 16px 0; font-size: 28px; font-weight: 600;"><?php esc_html_e( 'Email Delivery Update', 'icegram-mailer' ); ?></h1>
					
					<!-- Site Badge -->
					<div class="site-badge" style="display: inline-block; background: #e2e8f0; color: #1a202c; padding: 6px 16px; border-radius: 4px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0; text-transform: uppercase;">
						<?php echo esc_html( ! empty( $site_name ) ? strtoupper( $site_name ) : strtoupper( get_bloginfo( 'name' ) ) ); ?>
					</div>
				</div>

				<!-- Intro Text -->
                <p class="intro-text" style="color: #4B5563; margin: 24px 0 32px 0; font-size: 16px; line-height: 1.5;">
					<?php
 					printf(
						wp_kses(
							/* translators: %1$s: number of failed emails */
							_n(
								'%1$s email recently failed to send',
								'%1$s emails recently failed to send',
								$failure_count,
								'icegram-mailer'
							),
							$allowed_html
						),
						'<strong>' . esc_html( $failure_count ) . '</strong>'
					);
					?>
				</p>

				<!-- Email Table -->
				<table style="width: 100%; border-collapse: collapse; margin: 32px 0;">
					<!-- Table Header -->
					<thead>
						<tr style="border-bottom: 2px solid #e2e8f0;">
							<th style="color: #a0aec0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 10px; text-align: left; width: 33.33%;"><?php esc_html_e( 'RECIPIENT', 'icegram-mailer' ); ?></th>
							<th style="color: #a0aec0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 10px; text-align: left; width: 33.33%;"><?php esc_html_e( 'SUBJECT', 'icegram-mailer' ); ?></th>
							<th style="color: #a0aec0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 10px; text-align: left; width: 33.33%;"><?php esc_html_e( 'DATE & TIME', 'icegram-mailer' ); ?></th>
						</tr>
					</thead>
					<!-- Table Body -->
					<tbody>
					<?php foreach ( $processed_failed_emails as $icegram_mailer_email ) : ?>
						<tr style="border-bottom: 1px solid #e2e8f0;">
							<td style="color: #1a202c; font-size: 13px; font-weight: 500; padding: 14px 10px; vertical-align: top; text-align: left;"> 
								<?php echo esc_html( $icegram_mailer_email['to_email'] ); ?>
							</td>
							<td style="color: #718096; font-size: 13px; padding: 14px 10px; vertical-align: top; text-align: left;">
							<?php 
							echo esc_html( wp_trim_words( $icegram_mailer_email['subject_text'], 3, '...' ) );
							?>
							</td>
							<td style="color: #a0aec0; font-size: 13px; padding: 14px 10px; text-align: left; vertical-align: top; white-space: nowrap;">
								<?php 
								if ( ! empty( $icegram_mailer_email['formatted_time'] ) ) {
									echo esc_html( $icegram_mailer_email['formatted_time'] );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Bottom Info Text -->
				<p class="intro-text" style="color: #4a5568; margin: 24px 0; font-size: 15px; text-align: center;">
					<?php esc_html_e( 'These issues are usually temporary. You may want to review your delivery logs.', 'icegram-mailer' ); ?>
				</p>

				<!-- Call to Action Button -->
				<div class="cta-container" style="margin: 32px 0 0 0;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=icegram_mailer_dashboard' ) ); ?>" class="cta-button" style="display: inline-block; background: #8b5cf6; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(139,92,246,.3);">
						<?php esc_html_e( 'Review Delivery Logs', 'icegram-mailer' ); ?>
					</a>
				</div>

			</div>

			<!-- Footer -->
			<?php include ICEGRAM_MAILER_PLUGIN_PATH . '/admin/templates/emails/partials/email-footer.php'; ?>			
		</div>
	</div>
</div>
</body>
</html>
