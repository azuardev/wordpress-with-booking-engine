<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$es_install_url            = admin_url( 'plugin-install.php?s=email+subscribers&tab=search&type=term' );
$ig_install_url            = admin_url( 'plugin-install.php?s=icegram&tab=search&type=term' );
$rainmaker_install_url     = admin_url( 'plugin-install.php?s=rainmaker&tab=search&type=term' );
$duplicate_install_url     = admin_url( 'plugin-install.php?s=icegram&tab=search&type=author' );
$tlwp_install_url          = admin_url( 'plugin-install.php?s=temporary+login+without+password&tab=search&type=term' );

if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$all_plugins               = array_keys( get_plugins() );

$args = array(
  'url' => 'https://icegram.com',
  'utm_campaign' => 'ig_upsell'
);

$ig_url = Icegram_Mailer_Common::get_utm_tracking_url( $args );

$ig_plugins = array(

	array(
		'title'       => __( 'Icegram Express', 'icegram-mailer' ),
		'logo'        => ICEGRAM_MAILER_IMAGE_URL . 'icegram-express-icon.png',
		'desc'        => __( 'Simple and Effective Email Marketing WordPress Plugin. Email Subscribers is a complete newsletter plugin that lets you collect leads, send automated new blog post notification emails, create & send broadcasts', 'icegram-mailer' ),
		'name'        => 'email-subscribers/email-subscribers.php',
		'install_url' => $es_install_url,
		'plugin_url'  => 'https://wordpress.org/plugins/email-subscribers/',
	),
	array(
		'title'       => __( 'Icegram Engage', 'icegram-mailer' ),
		'logo'        => ICEGRAM_MAILER_IMAGE_URL . 'icegram-engage-icon.png',
		'desc'        => __( 'The best WP popup plugin that creates a popup. Customize popup, target popups to show offers, email signups, social buttons, etc and increase conversions on your website.', 'icegram-mailer' ),
		'name'        => 'icegram/icegram.php',
		'install_url' => $ig_install_url,
		'plugin_url'  => 'https://wordpress.org/plugins/icegram/',
	),

	array(
		'title'       => __( 'Icegram Collect', 'icegram-mailer' ),
		'logo'        => ICEGRAM_MAILER_IMAGE_URL . 'icegram-collect-icon.png',
		'desc'        => __( 'Get readymade contact forms, email subscription forms and custom forms for your website. Choose from beautiful templates and get started within seconds', 'icegram-mailer' ),
		'name'        => 'icegram-rainmaker/icegram-rainmaker.php',
		'install_url' => $rainmaker_install_url,
		'plugin_url'  => 'https://wordpress.org/plugins/icegram-rainmaker/',
	),
	array(
		'title'       => __( 'Temporary Login Without Password', 'icegram-mailer' ),
		'logo'        => ICEGRAM_MAILER_IMAGE_URL . 'tlwp-icon.png',
		'desc'        => __( 'Create self-expiring, automatic login links for WordPress. Give them to developers when they ask for admin access to your site.', 'icegram-mailer' ),
		'name'        => 'temporary-login-without-password/temporary-login-without-password.php',
		'install_url' => $tlwp_install_url,
		'plugin_url'  => 'https://wordpress.org/plugins/temporary-login-without-password/',
	),
	array(
		'title'       => __( 'Duplicate Pages and Posts', 'icegram-mailer' ),
		'logo'        => ICEGRAM_MAILER_IMAGE_URL . 'duplicate-post-page-copy-clone-wp-icon.svg',
		'desc'        => __( 'A Duplicate Pages and Posts Plugin is a tool for WordPress that enables users to easily create duplicate versions of existing posts, pages, or custom post types with just a click.', 'icegram-mailer' ),
		'name'        => 'duplicate-post-page-copy-clone-wp/wp-duplicate-post-page-copy-clone.php',
		'install_url' => $duplicate_install_url,
		'plugin_url'  => 'https://wordpress.org/plugins/duplicate-post-page-copy-clone-wp/',
	),

);

?>
<div class="mt-2 ml-4 mr-5 mb-0" id="icgram-mailer-other-plugins">
<div class="container flex flex-wrap w-full mt-4 mb-7">
	<div class="block mt-1 text-center">
		<h3 class="text-2xl font-bold leading-9 text-gray-700 sm:truncate mb-3 text-center">
			<?php echo sprintf('Other awesome plugins from <a href="%s" target="_blank" class="text-blue-600 underline">Icegram</a>', esc_url($ig_url)); ?>
		</h3>
	</div>
	<div class="grid w-full grid-cols-3 ">
		<?php foreach ( $ig_plugins as $ig_plugin ) { ?>
			<div class="flex flex-col mb-4 mr-3 bg-white rounded-lg shadow">
				<div class="flex h-48">
					<div class="flex pl-1">
						<div class="flex w-1/4 rounded">
							<div class="flex flex-col w-full h-6">
								<div>
									<img class="mx-auto my-4 border-0 h-15" src="<?php echo esc_url( $ig_plugin['logo'] ); // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" alt="">
								</div>
							</div>
						</div>
						<div class="flex w-3/4 pt-2">
							<div class="flex flex-col">
								<div class="flex w-full">
									<a href="<?php echo esc_url( $ig_plugin['plugin_url'] ); ?>" target="_blank"><h3 class="pb-2 pl-2 mt-2 text-lg font-medium text-indigo-600"><?php echo esc_html( $ig_plugin['title'] ); ?></h3></a>
								</div>
								<div class="flex w-full pl-2 leading-normal xl:pb-4 lg:pb-2 md:pb-2">
									<h4 class="pt-1 pr-4 text-sm text-gray-700"><?php echo esc_html( $ig_plugin['desc'] ); ?></h4>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="flex flex-row mb-0 border-t">
					<div class="flex w-2/3 px-3 py-5 text-sm"><?php echo esc_html__( 'Status', 'icegram-mailer' ); ?>:
						<?php if ( is_plugin_active( $ig_plugin['name'] ) ) { ?>
							<span class="font-bold text-green-600">&nbsp;<?php echo esc_html__( 'Active', 'icegram-mailer' ); ?></span>
						<?php } elseif (  in_array( $ig_plugin['name'], $all_plugins, true ) && ! is_plugin_active( $ig_plugin['name'] ) ) { ?>
							<span class="font-bold text-red-600">&nbsp;<?php echo esc_html__( 'Inactive', 'icegram-mailer' ); ?></span>
						<?php } else { ?>
							<span class="font-bold text-orange-500">&nbsp;<?php echo esc_html__( 'Not Installed', 'icegram-mailer' ); ?></span>
						<?php } ?>
					</div>
					<div class="flex justify-center w-1/3 py-3 md:pr-4">
		  <span class="rounded-md shadow-sm">
				<?php if ( ! is_plugin_active( $ig_plugin['name'] ) ) { ?>
			  <a href="<?php echo esc_url( $ig_plugin['install_url'] ); ?>" target="_blank">
					<?php
				}

				if ( ! in_array( $ig_plugin['name'], $all_plugins ) ) {
					?>
						<button type="button" class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-500 focus:outline-none focus:shadow-outline-blue">
					<?php 
					if ( isset( $ig_plugin['is_premium'] ) && true === $ig_plugin['is_premium'] ) {
						echo esc_html__( 'Buy Now', 'icegram-mailer' );
					} else {
						echo esc_html__( 'Install', 'icegram-mailer' );
					} 
					?>
						 </button>
					<?php } elseif ( ! is_plugin_active( $ig_plugin['name'] ) ) { ?>
						<button type="button" class="inline-flex justify-center w-full px-4 py-2 text-sm font-medium leading-5 text-white transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 focus:outline-none focus:shadow-outline-blue">
					<?php echo esc_html__( 'Activate', 'icegram-mailer' ); ?> </button>
					<?php } ?>
			  </a>
			</span>
					</div>
				</div>
			</div>
		<?php } ?>

	</div>
</div>
</div>
