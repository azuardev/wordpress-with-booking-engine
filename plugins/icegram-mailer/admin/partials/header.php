<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<header>
	<nav aria-label="Global" class="py-3 w-full">
		<div class="brand-logo">
			<span>
				<!--- TODO: Fix @layer component CSS getting stripped after building tailwind CSS even if we are using the classes -->
				<a class="outline-none" href="<?php echo esc_url( admin_url( '?page=icegram_mailer_dashboard' ) ); ?>">
					<img src="<?php echo esc_url( ICEGRAM_MAILER_PLUGIN_URL . '/admin/images/ig-logo.svg' ); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" class="h-8 w-auto" alt="brand logo" />
				</a>
				<div class="divide"></div>
				<h1><?php echo esc_html__('Icegram Mailer', 'icegram-mailer'); ?></h1>
			</span>
		</div>

		<div class="cta">
			<a href="<?php echo esc_url( $nav_button_url ); ?>">
				<button href="#" class="primary">
					<?php echo esc_html( $nav_button_text ); ?>
				</button>
			</a>
		</div>
	</nav>
</header>
