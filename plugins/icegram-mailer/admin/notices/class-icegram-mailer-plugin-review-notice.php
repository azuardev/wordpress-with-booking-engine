<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Icegram_Mailer_Plugin_Review_Notice {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'admin_init', array(  $this, 'maybe_show_plugin_review_notice' ), 10, 2 );
	}

	public function maybe_show_plugin_review_notice() {
		if ( ! Icegram_Mailer_Account::is_opted_for_ess() ) {
			return;
		}

		$cta_url = 'https://wordpress.org/support/plugin/icegram-mailer/reviews/#new-post';

		$notice_id = 'plugin_review';
        // translators: %1$s and %2$s: opening and closing <strong> tags around "Icegram Mailer", %3$s and %4$s: opening and closing <strong> tags around "5 star".
		$message = sprintf( esc_html__( 'Hey 👋, we at %1$sIcegram Mailer%2$s just wanted to thank you for using our plugin. If you’re enjoying it, we’d really appreciate a %3$s5 star%4$s review. It keeps us motivated to keep building and improving.', 'icegram-mailer' ),
					'<strong>', '</strong>', '<strong>', '</strong>' );

		$notice_html = '';
		ob_start();
		?>
		<div class="icegram-mailer-admin-notice text-gray-700">
			<p class="mb-2">
				<?php
					echo wp_kses_post( $message );
				?>
			</p>
			<a class="icegram-mailer-dismiss-notice" href="<?php echo esc_url( $cta_url ); ?>" target="_blank">
				<button class="primary">
					<?php echo esc_html__( 'Leave a review', 'icegram-mailer' ); ?>
				</button>
			</a>
			<a class="icegram-mailer-dismiss-notice" href="#">
				<button class="secondary">
					<?php echo esc_html__( 'Remind me later', 'icegram-mailer' ); ?>
				</button>
			</a>
		</div>
		<?php

		add_filter( "icegram_mailer_should_display_{$notice_id}_notice", array(  $this, 'allow_review_notice' ) );

		$notice_html = ob_get_clean();
		new Icegram_Mailer_Admin_Notice(
			$notice_id,
			$notice_html,
			'success',
			'edit_posts',
			array( 'icegram_mailer_dashboard', 'icegram_mailer_settings' )
		);
	}

	public function allow_review_notice() {
		$args = array(
			'status' => 'sent',
		);

		$sent_email_count = (int) icegram_mailer()->email_logs_table->get_logs( $args, ARRAY_A, true );
		
		return $sent_email_count >= 5;
	}
}

new Icegram_Mailer_Plugin_Review_Notice();
