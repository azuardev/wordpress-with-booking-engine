<?php
function ig_mailer_add_deactivation_reasons( $options ) {

	$existing_options = array();
	foreach ( $options as $option ) {
		if ( isset( $option['slug'] ) ) {
			$existing_options[ $option['slug'] ] = $option;
		}
	}

	$new_slugs = array(
		'i-faced-issues' => array(
			'title' => __( 'I faced issues getting the plugin to work properly', 'icegram-mailer' ),
		),
		'free-email-exhausted' => array(
			'title' => __( 'The free email is exhausted', 'icegram-mailer' ),
		),
		'missing-feature' => array(
			'title' => __( 'Missing a feature I need', 'icegram-mailer' ),
		),
		'switching-plugin' => array(
			'title' => __( 'Switching to another plugin', 'icegram-mailer' ),
		),
		'installed-by-mistake' => array(
			'title' => __( 'Installed by mistake', 'icegram-mailer' ),
		),
		'other' => array(
			'title' => __( 'Other', 'icegram-mailer' ),
		),
	);

	$new_options = array();
	foreach ( $new_slugs as $slug => $reason ) {
		if ( isset( $existing_options[ $slug ] ) ) {
			$option = $existing_options[ $slug ];
		} else {
			$option = array( 'slug' => $slug );
		}
		$option['title'] = $reason['title'];
		$new_options[] = $option;
	}

	return $new_options;
}
add_filter( 'ig_mailer_deactivation_reasons', 'ig_mailer_add_deactivation_reasons' );

/**
 * Get additional system & plugin specific information for feedback
 */
if ( ! function_exists( 'ig_mailer_get_additional_info' ) ) {

	function ig_mailer_get_additional_info( $additional_info = array(), $system_info = false ) {
		global $icegram_mailer_tracker;

		$additional_info['version'] = ICEGRAM_MAILER_VERSION;

		if ( $system_info ) {
			$additional_info['active_plugins']   = implode( ', ', $icegram_mailer_tracker::get_active_plugins() );
			$additional_info['inactive_plugins'] = implode( ', ', $icegram_mailer_tracker::get_inactive_plugins() );
			$additional_info['current_theme']    = $icegram_mailer_tracker::get_current_theme_info();
			$additional_info['wp_info']          = $icegram_mailer_tracker::get_wp_info();
			$additional_info['server_info']      = $icegram_mailer_tracker::get_server_info();
		}

		$admin_email = get_option( 'admin_email' );
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$additional_info['email'] = $admin_email;
		$additional_info['name']  = $admin_name;

		return $additional_info;
	}
}

add_filter( 'ig_mailer_additional_feedback_meta_info', 'ig_mailer_get_additional_info', 10, 2 );


if ( ! function_exists( 'ig_mailer_subscribe_to_plugin_deactivation_list' ) ) {
	function ig_mailer_subscribe_to_plugin_deactivation_list( $data ) {
		
		$admin_email = get_bloginfo( 'admin_email' );
		$user        = get_user_by( 'email', $admin_email );
		$admin_name  = '';
		if ( $user instanceof WP_User ) {
			$admin_name = $user->display_name;
		}

		$email = $admin_email;
		$name  = $admin_name;

		// "Get Expert Help" sets skip_deactivation=1 — it's not a real deactivation, don't subscribe.
		if ( ! empty( $data['meta']['skip_deactivation'] ) ) {
			return;
		}

		switch ( $data['feedback']['value'] ) {
			case 'i-faced-issues':
				$list = '6a7aacc98417';
				break;

			case 'free-email-exhausted':
				$list = '043753cf0041';
				break;

			case 'switching-plugin':
				$list = 'd0d2b6bb23c7';
				break;

			case 'installed-by-mistake':
				$list = 'c7ce22022ce5';
				break;

			default:
				$list = '';
				break;
		}

		if ( ! empty( $list ) && is_email( $email ) ) {

			$url_params = array(
			'ig_es_external_action' => 'subscribe',
			'name'                  => $name,
			'email'                 => $email,
			'list'                  => $list,
			);

			$ip_address = icegram_mailer_get_ip();
			if ( ! empty( $ip_address ) && 'UNKNOWN' !== $ip_address ) {
				$url_params['ip_address'] = $ip_address;
			}

			$ig_url = 'https://www.icegram.com/';
			$ig_url = add_query_arg( $url_params, $ig_url );

			$args = array(
			'timeout' => 15,
			'blocking'  => false,
			);

			// Make a get request.
			wp_remote_get( $ig_url, $args );
		}
	}
}
add_action( 'ig_mailer_deactivation_feedback_submitted', 'ig_mailer_subscribe_to_plugin_deactivation_list' );



function ig_mailer_deactivation_headline( $headline ) {
	return '&#x1F614; Before you go&hellip;';
}
add_filter( 'ig_mailer_deactivation_headline', 'ig_mailer_deactivation_headline' );

/**
 * Main question shown on Screen 1 of the deactivation survey modal.
 */
function ig_mailer_deactivation_question( $question ) {
	return __( 'What made you deactivate Icegram Mailer?', 'icegram-mailer' );
}
add_filter( 'ig_mailer_deactivation_question', 'ig_mailer_deactivation_question' );

/**
 * Thank-you message shown at the bottom of Screen 2.
 */
function ig_mailer_deactivation_thankyou( $text ) {
	return '&#x2764;&#xFE0F; Thanks for trying Icegram Mailer';
}
add_filter( 'ig_mailer_deactivation_thankyou', 'ig_mailer_deactivation_thankyou' );

/**
 * Per-option follow-up configuration for Screen 2.
 * Each key maps to a deactivation reason slug and defines:
 *   heading, body, sub_options, has_textarea, textarea_placeholder, body2, buttons.
 */
function ig_mailer_deactivation_followups( $followups ) {
	return array(
		'i-faced-issues' => array(
			'heading'    => __( 'Can you tell us more about the problem?', 'icegram-mailer' ),
			'body'       => '',
			'sub_options' => array(
				__( 'Set up issue / configuration confusion', 'icegram-mailer' ),
				__( 'Delivery issue - emails were not getting sent', 'icegram-mailer' ),
				__( "I'm not sure", 'icegram-mailer' ),
			),
			'has_textarea' => false,
			'body2'       => '',
			'buttons'     => array(
				array( 'label' => __( 'Get Expert Help', 'icegram-mailer' ),    'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit', 'skip_deactivation' => true, 'success_message' => __( 'Thank you for reaching out, we are here for you.', 'icegram-mailer' ) ),
				array( 'label' => __( 'Continue Deactivation', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-ghost', 'action' => 'submit' ),

			),
		),
		'free-email-exhausted' => array(

			'heading'    => ( function() {
				if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
					return __( "It looks like you haven't set up Icegram Mailer yet.", 'icegram-mailer' );
				}

				$ess_data        = Icegram_Mailer_Account::get_ess_data();
				$allocated_limit = ! empty( $ess_data['allocated_limit'] ) ? $ess_data['allocated_limit'] : 0;
				$current_month   = icegram_mailer_get_current_month();
				$used_limit      = ! empty( $ess_data['used_limit'][ $current_month ] ) ? $ess_data['used_limit'][ $current_month ] : 0;
				$remaining       = max( 0, $allocated_limit - $used_limit );

				return sprintf(
					/* translators: %s: number of remaining emails */
					__( 'You have %s emails remaining in your sending limit.', 'icegram-mailer' ),
					'<strong>' . esc_html( number_format_i18n( $remaining ) ) . '</strong>'
				);
			} )(),
			'body'       => ( function() {
				if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
					return __( 'Set it up to start sending emails and make the most of your free sending limit.', 'icegram-mailer' );
				}

				$ess_data       = Icegram_Mailer_Account::get_ess_data();
				$next_reset_raw = ! empty( $ess_data['next_reset'] ) ? $ess_data['next_reset'] : '';

				if ( ! empty( $next_reset_raw ) ) {
					$date_format = get_option( 'date_format' );
					$reset_date  = date_i18n( $date_format, strtotime( $next_reset_raw ) );
					/* translators: %s: next email limit reset date */
					return sprintf( __( 'Use them before %s.', 'icegram-mailer' ), esc_html( $reset_date ) );
				}

				return '';
			} )(),		'sub_options' => array(),

			'has_textarea' => false,
			'body2'       => ( function() {
				if ( ! Icegram_Mailer_Account::is_ess_account_created() ) {
					return __( 'Need more than the free plan? Explore our plans.', 'icegram-mailer' );
				} else {
					return __( 'Or upgrade to keep sending without limits.', 'icegram-mailer' );
				}
			} )(),
			'buttons'     => array(
				array( 'label' => __( 'See Plans', 'icegram-mailer' ),             'cls' => 'ig-deactivation-btn-primary', 'action' => 'url', 'url' => 'https://www.icegram.com/mailer/#pricing' ),
				array( 'label' => __( 'Continue Deactivation', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-ghost',  'action' => 'submit' ),
			),
		),
		'missing-feature' => array(
			'heading'             => __( 'Missing a feature?', 'icegram-mailer' ),
			'body'                => __( 'Tell us what you were looking for — your feedback helps us improve future updates.', 'icegram-mailer' ),
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Describe the feature you need...', 'icegram-mailer' ),
			'body2'               => sprintf( __( 'You can also check <a href="%s" target="_blank">our docs</a> in case the feature already exists.', 'icegram-mailer' ), 'https://www.icegram.com/docs/category/icegram-mailers/' ),
			'buttons'             => array(
				array( 'label' => __( 'Share and Continue Deactivation', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit' ),
				array( 'label' => __( 'Deactivate', 'icegram-mailer' ),                      'cls' => 'ig-deactivation-btn-ghost',   'action' => 'deactivate' ),
			),
		),
		'switching-plugin' => array(
			'heading'             => __( 'Trying a different plugin?', 'icegram-mailer' ),
			'body'                => __( 'No worries—we’d love to know what you picked. It helps us make Icegram Mailer better.', 'icegram-mailer' ),
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Which plugin are you switching to? (optional)', 'icegram-mailer' ),
			'body2'               => '',
			'buttons'             => array(
				array( 'label' => __( 'Submit & Deactivate', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-primary', 'action' => 'submit' ),
				array( 'label' => __( 'Deactivate', 'icegram-mailer' ),          'cls' => 'ig-deactivation-btn-ghost',  'action' => 'deactivate' ),
			),
		),
		'installed-by-mistake' => array(
			'heading'    => __( 'Installed by mistake? No worries', 'icegram-mailer' ),
			'body'       => __( 'Icegram Mailer quietly improves your email delivery in the background. Want to take a quick look before you go?', 'icegram-mailer' ),
			'sub_options' => array(),
			'has_textarea' => false,
			'body2'       => '',
			'buttons'     => array(
				array( 'label' => __( 'Quick Overview', 'icegram-mailer' ),        'cls' => 'ig-deactivation-btn-primary', 'action' => 'url', 'url' => 'https://www.icegram.com/docs/category/icegram-mailers/?utm_source=in-app&utm_medium=deactivation&utm_campaign=ig-mailer' ),
				array( 'label' => __( 'Continue Deactivation', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-ghost',     'action' => 'submit' ),
			),
		),
		'other' => array(
			'heading'             => __( 'Tell us more (optional)', 'icegram-mailer' ),
			'body'                => '',
			'sub_options'          => array(),
			'has_textarea'         => true,
			'textarea_placeholder' => __( 'Your feedback (optional)...', 'icegram-mailer' ),
			'body2'               => '',
			'buttons'             => array(
				array( 'label' => __( 'Continue Deactivation', 'icegram-mailer' ), 'cls' => 'ig-deactivation-btn-ghost', 'action' => 'submit' ),
			),
		),
	);
}
add_filter( 'ig_mailer_deactivation_followups', 'ig_mailer_deactivation_followups' );
