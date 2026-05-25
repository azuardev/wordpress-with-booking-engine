<?php
/**
 * Email template for email limit reached notification
 *
 * Available variables: 
 * @var int    $allocated_limit Total allocated limit
 * @var int    $used_limit Used emails count
 * @var float  $percentage_used Percentage of limit used
 * @var int    $remaining Remaining emails
 * @var array  $ess_data ESS account data
 * @var string $logo_url Logo URL
 * @var string $site_name Site name
 * @var string $percentage Formatted percentage string
 * @var array  $allowed_html Allowed HTML tags for wp_kses
 * @var string $next_reset_formatted Formatted next reset date
 * @var string $upgrade_url Upgrade URL with UTM tracking
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
                    <h1 class="heading" style="color: #1a202c; margin: 0 0 16px 0; font-size: 28px; font-weight: 600;"><?php esc_html_e( 'Email Usage Update', 'icegram-mailer' ); ?></h1>

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
							/* translators: %s: percentage with strong tag */
							__( 'You have reached %s of your monthly email sending limit.', 'icegram-mailer' ),
							$allowed_html	
						),
						'<strong style="color: #1F2937; font-weight: 600;">' . esc_html( $percentage ) . '</strong>'
					);
					?>
				</p>

                <!-- Stats Grid -->
                <div class="stats-grid" style="margin: 32px 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <!-- Allocated Limit -->
                            <td style="padding: 8px; width: 33.33%; vertical-align: top;">
                                <div class="stat-card" style="padding: 12px; text-align: center;">
                                    <div class="stat-value" style="font-size: 32px; font-weight: 600; color: #4B5563; margin-bottom: 8px; line-height: 1;"><?php echo esc_html( number_format_i18n( $allocated_limit ) ); ?></div>
                                    <div class="stat-label" style="font-size: 13px; color: #9CA3AF; font-weight: 500;"><?php esc_html_e( 'Allocated Limit', 'icegram-mailer' ); ?></div>
                                </div>
                            </td>
                            <!-- Used -->
                            <td style="padding: 8px; width: 33.33%; vertical-align: top;">
                                <div class="stat-card" style="padding: 12px; text-align: center;">
                                    <div class="stat-value" style="font-size: 32px; font-weight: 600; color: #4B5563; margin-bottom: 8px; line-height: 1;"><?php echo esc_html( number_format_i18n( $used_limit ) ); ?></div>
                                    <div class="stat-label" style="font-size: 13px; color: #9CA3AF; font-weight: 500;"><?php esc_html_e( 'Used', 'icegram-mailer' ); ?></div>
                                </div>
                            </td>
                            <!-- Remaining -->
                            <td style="padding: 8px; width: 33.33%; vertical-align: top;">
                                <div class="stat-card" style="padding: 12px; text-align: center;">
                                    <div class="stat-value" style="font-size: 32px; font-weight: 600; color: #4B5563; margin-bottom: 8px; line-height: 1;"><?php echo esc_html( number_format_i18n( $remaining ) ); ?></div>
                                    <div class="stat-label" style="font-size: 13px; color: #9CA3AF; font-weight: 500;"><?php esc_html_e( 'Remaining', 'icegram-mailer' ); ?></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Call to Action Button -->
                <div class="cta-container" style="margin: 32px 0 24px 0; text-align: center;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=icegram_mailer_dashboard' ) ); ?>" class="cta-button" style="display: inline-block; background: #8b5cf6; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 8px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(139,92,246,.3);">
                        <?php esc_html_e( 'View Dashboard', 'icegram-mailer' ); ?>
                    </a>
                </div>

                <!-- Reset Info -->
                <div class="reset-info" style="text-align: center; margin: 24px 0 8px 0;">
                    <?php if ( ! empty( $next_reset_formatted ) ) : ?>
                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #6B7280;">
                        <?php
                        printf(
                            /* translators: %s: reset date */
                            esc_html__( 'Next Limit Reset: %s', 'icegram-mailer' ),
                            '<strong style="color: #1F2937; font-weight: 600;">' . esc_html( $next_reset_formatted ) . '</strong>'
                        );
                        ?>
                    </p>
                    <?php endif; ?>
                    <?php if ( $percentage_used >= 80 ) : ?>
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