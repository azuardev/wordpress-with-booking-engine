<?php
/**
 * Email template for monthly summary before limit reset
 *
 * Available variables:
 * @var string $site_name Site name
 * @var int    $allocated_limit Total allocated limit
 * @var int    $used_limit Used emails count
 * @var int    $remaining Remaining emails available
 * @var float  $percentage_used Percentage of limit used 
 * @var int    $successful_emails Successful emails sent
 * @var int    $failed_emails Failed emails count
 * @var int    $total_emails Total emails sent (successful + failed)
 * @var string $reset_date Date when limit will reset 
 * @var int    $days_to_reset Days remaining until reset
 * @var string $logo_url Logo URL 
 * @var float  $success_rate Success rate percentage
 * @var float  $failure_rate Failure rate percentage
 * @var int    $open_count Estimated email opens
 * @var float  $open_rate Open rate percentage
 * @var string $date_range_start Start date of reporting period
 * @var string $date_range_end End date of reporting period
 * @var string $upgrade_url Upgrade URL with UTM tracking
 * @var string $reset_date_formatted Formatted reset date
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
					<h1 class="heading" style="color: #1a202c; margin: 0 0 16px 0; font-size: 28px; font-weight: 600;"><?php esc_html_e( 'Monthly Email Summary', 'icegram-mailer' ); ?></h1>
					
					<!-- Site Badge -->
					<div class="site-badge" style="display: inline-block; background: #e2e8f0; color: #1a202c; padding: 6px 16px; border-radius: 4px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0; text-transform: uppercase;">
						<?php echo esc_html( ! empty( $site_name ) ? strtoupper( $site_name ) : strtoupper( get_bloginfo( 'name' ) ) ); ?>
					</div>
				</div>
				
				<!-- Date Range -->
				<p class="date-range" style="color: #4B5563; margin: 24px 0 32px 0; font-size: 16px; font-weight: 500; letter-spacing: 0.5px;">
					<?php echo esc_html( $date_range_start . ' — ' . $date_range_end ); ?>
				</p>
				
				<!-- Plan Usage Section -->
				<div class="section" style="margin-bottom: 32px; text-align: left;">
					<div class="section-title" style="color: #9CA3AF; font-size: 16px; font-weight: 600; margin: 0; text-transform: uppercase;"><?php esc_html_e( 'Plan Usage', 'icegram-mailer' ); ?></div>
					<table class="stat-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
						<tr>
							<td class="stat-label" style="color: #374151; font-size: 14px; font-weight: 400; text-align: left; padding: 10px 0; vertical-align: middle;"><?php esc_html_e( 'Emails sent', 'icegram-mailer' ); ?></td>
							<td class="stat-value" style="color: #1F2937; font-size: 15px; font-weight: 600; text-align: right; padding: 10px 0; vertical-align: middle;">
								<?php 
								printf(
									/* translators: 1: used emails, 2: allocated limit, 3: percentage */
									esc_html__( '%1$s / %2$s (%3$s%%)', 'icegram-mailer' ),
									esc_html( number_format_i18n( $used_limit ) ),
									esc_html( number_format_i18n( $allocated_limit ) ),
									esc_html( number_format_i18n( $percentage_used, 1 ) )
								);
								?>
							</td>
						</tr>
					</table>
					<div class="progress-bar-container" style="background: #E5E7EB; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 12px;">
						<div class="progress-bar" style="background: linear-gradient(90deg, #8B5CF6 0%, #7C3AED 100%); height: 100%; border-radius: 4px; width: <?php echo esc_attr( min( $percentage_used, 100 ) ); ?>%;"></div>
					</div>
				</div>

				<!-- Delivery Insights Section -->
				<div class="section" style="margin-bottom: 32px; text-align: left;">
					<div class="section-title" style="color: #9CA3AF; font-size: 16px; font-weight: 600; margin: 0; text-transform: uppercase;"><?php esc_html_e( 'Delivery Insights', 'icegram-mailer' ); ?></div>
					<table class="stat-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
						<tr>
							<td class="stat-label" style="color: #374151; font-size: 14px; font-weight: 400; text-align: left; padding: 10px 0; vertical-align: middle;"><?php esc_html_e( 'Successful sent', 'icegram-mailer' ); ?></td>
							<td class="stat-value" style="color: #1F2937; font-size: 15px; font-weight: 600; text-align: right; padding: 10px 0; vertical-align: middle;">
								<?php 
								printf(
									/* translators: 1: successful emails, 2: success rate */
									esc_html__( '%1$s (%2$s%%)', 'icegram-mailer' ),
									esc_html( number_format_i18n( $successful_emails ) ),
    								esc_html( number_format_i18n( $success_rate, 1 ) )
								);
								?>
							</td>
						</tr>
					</table>
					<table class="stat-table stat-table-bordered stat-table-bordered-bottom" style="width: 100%; border-collapse: collapse; margin-bottom: 0; border-top: 1px solid #E5E7EB; margin-top: 0; border-bottom: 1px solid #E5E7EB;">
						<tr>
							<td class="stat-label" style="color: #374151; font-size: 14px; font-weight: 400; text-align: left; padding: 16px 0; vertical-align: middle;"><?php esc_html_e( 'Emails failed', 'icegram-mailer' ); ?></td>
							<td class="stat-value" style="color: #1F2937; font-size: 15px; font-weight: 600; text-align: right; padding: 16px 0; vertical-align: middle;">
							<?php 
							printf(
								/* translators: 1: failed emails, 2: failure rate */
								esc_html__( '%1$s (%2$s%%)', 'icegram-mailer' ),
								esc_html( number_format_i18n( $failed_emails ) ),
							esc_html( number_format_i18n( $failure_rate, 1 ) )
							);
							?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Engagement Insights Section -->
				<div class="section" style="margin-bottom: 32px; text-align: left;">
					<div class="section-title" style="color: #9CA3AF; font-size: 16px; font-weight: 600; margin: 0; text-transform: uppercase;"><?php esc_html_e( 'Engagement Insights', 'icegram-mailer' ); ?></div>
					<table class="stat-table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
						<tr>
							<td class="stat-label" style="color: #374151; font-size: 14px; font-weight: 400; text-align: left; padding: 10px 0; vertical-align: middle;"><?php esc_html_e( 'Emails opened', 'icegram-mailer' ); ?></td>
							<td class="stat-value" style="color: #1F2937; font-size: 15px; font-weight: 600; text-align: right; padding: 10px 0; vertical-align: middle;">
								<?php
								printf(
									/* translators: 1: opened emails, 2: open rate */
									esc_html__( '%1$s (%2$s%%)', 'icegram-mailer' ),
									esc_html( number_format_i18n( $open_count ) ),
									esc_html( number_format_i18n( $open_rate, 1 ) )
								);
								?>
							</td>
						</tr>
					</table>
				</div>

				<!-- Call to Action Button -->
				<div class="cta-container" style="margin: 40px 0 32px 0; text-align: center;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=icegram_mailer_dashboard' ) ); ?>" class="cta-button" style="display: inline-block; background: #8b5cf6; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(139,92,246,.3);">
						<?php esc_html_e( 'View Dashboard', 'icegram-mailer' ); ?>
					</a>
				</div>

				<!-- Reset Info -->
				<div class="reset-info" style="text-align: center; margin: 24px 0 8px 0;">
					<p style="margin: 0 0 8px 0; font-size: 14px; color: #6B7280;">
						<?php
						printf(
							/* translators: %s: reset date */
							esc_html__( 'Next Limit Reset: %s', 'icegram-mailer' ),
							'<strong style="color: #1F2937; font-weight: 600;">' . esc_html( $reset_date_formatted ) . '</strong>'
						);
						?>
					</p>
					<?php if ( $percentage_used >= 60 ) : ?>
					<p class="upgrade-link" style="margin: 0 0 8px 0; font-size: 14px; color: #6B7280;">
						<?php
						printf(
							/* translators: %s: upgrade link */
							esc_html__( 'Running low on credits? %s', 'icegram-mailer' ),
							'<a href="' . esc_url( $upgrade_url ) . '" style="color: #8B5CF6; text-decoration: none; font-weight: 500;">' . esc_html__( 'Upgrade your plan', 'icegram-mailer' ) . '</a>'
						);
						?>
					</p>
					<?php endif; ?>
				</div>

			</div>

			<!-- Footer -->
			<?php include ICEGRAM_MAILER_PLUGIN_PATH . '/admin/templates/emails/partials/email-footer.php'; ?>

		</div>
	</div>
</div>
</body>
</html>
