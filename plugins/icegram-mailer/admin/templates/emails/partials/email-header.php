<?php
/**
 * Email Header Partial - Logo/Branding Section
 *
 * Available variables:
 * @var string $logo_url Logo URL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Logo Icon -->
<div class="logo-container" style="margin-bottom: 24px;">
	<div class="logo-icon" style="width: 60px; height: 60px; border-radius: 16px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Icegram Mailer', 'icegram-mailer' ); ?>" style="max-width: 100%; height: auto;" />
	</div>
</div>
