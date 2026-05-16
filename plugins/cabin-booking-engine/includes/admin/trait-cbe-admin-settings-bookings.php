<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Admin_Settings_Bookings_Trait {
    public function register_settings() {
        register_setting(
            'cbe_settings_group',
            self::SETTINGS_KEY,
            array($this, 'sanitize_settings')
        );
    }

    public function sanitize_settings($settings) {
        if (!is_array($settings)) {
            $settings = array();
        }

        $current = $this->get_settings();

        $sanitized = array(
            'notification_email' => isset($settings['notification_email']) ? sanitize_email($settings['notification_email']) : $current['notification_email'],
            'whatsapp_webhook_url' => isset($settings['whatsapp_webhook_url']) ? esc_url_raw($settings['whatsapp_webhook_url']) : '',
            'whatsapp_secret' => isset($settings['whatsapp_secret']) ? sanitize_text_field($settings['whatsapp_secret']) : '',
            'auto_embed_single_cabin' => !empty($settings['auto_embed_single_cabin']) ? 1 : 0,
            'doku_enabled' => !empty($settings['doku_enabled']) ? 1 : 0,
            'doku_environment' => (isset($settings['doku_environment']) && $settings['doku_environment'] === 'production') ? 'production' : 'sandbox',
            'doku_sandbox_client_id' => isset($settings['doku_sandbox_client_id']) ? sanitize_text_field($settings['doku_sandbox_client_id']) : '',
            'doku_sandbox_shared_key' => isset($settings['doku_sandbox_shared_key']) ? sanitize_text_field($settings['doku_sandbox_shared_key']) : '',
            'doku_prod_client_id' => isset($settings['doku_prod_client_id']) ? sanitize_text_field($settings['doku_prod_client_id']) : '',
            'doku_prod_shared_key' => isset($settings['doku_prod_shared_key']) ? sanitize_text_field($settings['doku_prod_shared_key']) : '',
            'doku_expiry_time' => isset($settings['doku_expiry_time']) ? max(1, (int) $settings['doku_expiry_time']) : 60,
            'doku_auto_redirect' => !empty($settings['doku_auto_redirect']) ? 1 : 0,
        );

        // Preserve non-settings keys stored in the same option (e.g. facility_catalog).
        $saved_raw = get_option(self::SETTINGS_KEY, array());
        if (!is_array($saved_raw)) {
            $saved_raw = array();
        }

        $preserved_existing = array_diff_key($saved_raw, $sanitized);
        $preserved_incoming = array_diff_key($settings, $sanitized);

        return array_merge($preserved_existing, $preserved_incoming, $sanitized);
    }

    private function get_settings() {
        $defaults = array(
            'notification_email' => get_option('admin_email'),
            'whatsapp_webhook_url' => '',
            'whatsapp_secret' => '',
            'auto_embed_single_cabin' => 1,
            'doku_enabled' => 0,
            'doku_environment' => 'sandbox',
            'doku_sandbox_client_id' => '',
            'doku_sandbox_shared_key' => '',
            'doku_prod_client_id' => '',
            'doku_prod_shared_key' => '',
            'doku_expiry_time' => 60,
            'doku_auto_redirect' => 1,
        );

        $saved = get_option(self::SETTINGS_KEY, array());
        if (!is_array($saved)) {
            $saved = array();
        }

        return wp_parse_args($saved, $defaults);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Room Booking Settings', 'cabin-booking-engine') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('cbe_settings_group');
        echo '<table class="form-table" role="presentation">';

        echo '<tr><th scope="row"><label for="cbe_notification_email">' . esc_html__('Notification Email', 'cabin-booking-engine') . '</label></th><td><input type="email" class="regular-text" id="cbe_notification_email" name="' . esc_attr(self::SETTINGS_KEY) . '[notification_email]" value="' . esc_attr($settings['notification_email']) . '" /><p class="description">' . esc_html__('Booking notifications will be sent to this email.', 'cabin-booking-engine') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_whatsapp_webhook_url">' . esc_html__('WhatsApp Webhook URL', 'cabin-booking-engine') . '</label></th><td><input type="url" class="regular-text" id="cbe_whatsapp_webhook_url" name="' . esc_attr(self::SETTINGS_KEY) . '[whatsapp_webhook_url]" value="' . esc_attr($settings['whatsapp_webhook_url']) . '" placeholder="https://example.com/webhook" /></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_whatsapp_secret">' . esc_html__('Webhook Secret', 'cabin-booking-engine') . '</label></th><td><input type="text" class="regular-text" id="cbe_whatsapp_secret" name="' . esc_attr(self::SETTINGS_KEY) . '[whatsapp_secret]" value="' . esc_attr($settings['whatsapp_secret']) . '" /></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Auto Embed Form on Stay Page', 'cabin-booking-engine') . '</th><td><label><input type="checkbox" name="' . esc_attr(self::SETTINGS_KEY) . '[auto_embed_single_cabin]" value="1" ' . checked((int) $settings['auto_embed_single_cabin'], 1, false) . ' /> ' . esc_html__('Automatically append booking form to single room content.', 'cabin-booking-engine') . '</label></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Enable DOKU', 'cabin-booking-engine') . '</th><td><label><input type="checkbox" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_enabled]" value="1" ' . checked((int) $settings['doku_enabled'], 1, false) . ' /> ' . esc_html__('Enable direct DOKU payment without WooCommerce.', 'cabin-booking-engine') . '</label></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_environment">' . esc_html__('DOKU Environment', 'cabin-booking-engine') . '</label></th><td><select id="cbe_doku_environment" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_environment]"><option value="sandbox" ' . selected($settings['doku_environment'], 'sandbox', false) . '>' . esc_html__('Sandbox', 'cabin-booking-engine') . '</option><option value="production" ' . selected($settings['doku_environment'], 'production', false) . '>' . esc_html__('Production', 'cabin-booking-engine') . '</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_sandbox_client_id">' . esc_html__('DOKU Sandbox Client ID', 'cabin-booking-engine') . '</label></th><td><input type="text" class="regular-text" id="cbe_doku_sandbox_client_id" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_sandbox_client_id]" value="' . esc_attr($settings['doku_sandbox_client_id']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_sandbox_shared_key">' . esc_html__('DOKU Sandbox Shared Key', 'cabin-booking-engine') . '</label></th><td><input type="text" class="regular-text" id="cbe_doku_sandbox_shared_key" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_sandbox_shared_key]" value="' . esc_attr($settings['doku_sandbox_shared_key']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_prod_client_id">' . esc_html__('DOKU Production Client ID', 'cabin-booking-engine') . '</label></th><td><input type="text" class="regular-text" id="cbe_doku_prod_client_id" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_prod_client_id]" value="' . esc_attr($settings['doku_prod_client_id']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_prod_shared_key">' . esc_html__('DOKU Production Shared Key', 'cabin-booking-engine') . '</label></th><td><input type="text" class="regular-text" id="cbe_doku_prod_shared_key" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_prod_shared_key]" value="' . esc_attr($settings['doku_prod_shared_key']) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="cbe_doku_expiry_time">' . esc_html__('DOKU Payment Due Minutes', 'cabin-booking-engine') . '</label></th><td><input type="number" min="1" step="1" id="cbe_doku_expiry_time" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_expiry_time]" value="' . esc_attr($settings['doku_expiry_time']) . '" /></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('DOKU Auto Redirect', 'cabin-booking-engine') . '</th><td><label><input type="checkbox" name="' . esc_attr(self::SETTINGS_KEY) . '[doku_auto_redirect]" value="1" ' . checked((int) $settings['doku_auto_redirect'], 1, false) . ' /> ' . esc_html__('Ask DOKU to auto redirect after payment.', 'cabin-booking-engine') . '</label></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('DOKU Notification URL', 'cabin-booking-engine') . '</th><td><code>' . esc_html(rest_url('cbe/v1/doku-notification')) . '</code></td></tr>';

        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    private function send_booking_notifications($booking_id, $booking) {
        $settings = $this->get_settings();
        $this->send_booking_email_notification($settings, $booking_id, $booking);
        $this->send_booking_whatsapp_webhook($settings, $booking_id, $booking);
    }

    private function send_booking_email_notification($settings, $booking_id, $booking) {
        $to = $settings['notification_email'];
        if (empty($to) || !is_email($to)) {
            return;
        }

        $subject = sprintf(__('New Room Booking #%d', 'cabin-booking-engine'), $booking_id);
        $message = array(
            'New booking received:',
            'Booking ID: ' . $booking_id,
            'Cabin: ' . get_the_title((int) $booking['cabin_id']),
            'Check-in: ' . $booking['checkin_date'],
            'Check-out: ' . $booking['checkout_date'],
            'Nights: ' . $booking['nights'],
            'Price per night: ' . number_format((float) $booking['price_per_night'], 2),
            'Total price: ' . number_format((float) $booking['total_price'], 2),
            'Payment Method: ' . $booking['payment_method'],
            'Payment Status: ' . $booking['payment_status'],
            'Guest Name: ' . $booking['guest_name'],
            'Guest Email: ' . $booking['guest_email'],
            'Guest Phone: ' . $booking['guest_phone'],
            'Total Guests: ' . $booking['total_guests'],
            'Notes: ' . $booking['notes'],
            'Status: ' . $booking['status'],
        );

        wp_mail($to, $subject, implode("\n", $message));
    }

    private function send_booking_whatsapp_webhook($settings, $booking_id, $booking) {
        if (empty($settings['whatsapp_webhook_url'])) {
            return;
        }

        $payload = array(
            'event' => 'booking.created',
            'plugin' => 'cabin-booking-engine',
            'booking_id' => $booking_id,
            'cabin' => array(
                'id' => (int) $booking['cabin_id'],
                'title' => get_the_title((int) $booking['cabin_id']),
            ),
            'booking' => $booking,
        );

        $args = array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        );

        if (!empty($settings['whatsapp_secret'])) {
            $args['headers']['X-CBE-Secret'] = $settings['whatsapp_secret'];
        }

        wp_remote_post($settings['whatsapp_webhook_url'], $args);
    }

    public function render_admin_bookings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $selected_booking_id = isset($_GET['booking_id']) ? (int) wp_unslash($_GET['booking_id']) : 0;
        if ($selected_booking_id > 0) {
            $booking = $this->get_booking($selected_booking_id);
            if ($booking) {
                $this->render_admin_booking_detail($booking);
                return;
            }
        }

        $rows = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT 200",
            ARRAY_A
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Room Bookings', 'cabin-booking-engine') . '</h1>';

        if (empty($rows)) {
            echo '<p>' . esc_html__('No booking data yet.', 'cabin-booking-engine') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('ID', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Room', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Check-in', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Check-out', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Nights', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Total Price', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Payment', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Invoice', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Guest', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Contact', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Guests', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Status', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Action', 'cabin-booking-engine') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $cabin_title = get_the_title((int) $row['cabin_id']);
            if ($cabin_title === '') {
                $cabin_title = __('(Room removed)', 'cabin-booking-engine');
            }

            $view_url = admin_url('admin.php?page=cbe-bookings&booking_id=' . (int) $row['id']);

            $approve_url = wp_nonce_url(
                admin_url('admin-post.php?action=cbe_update_booking_status&booking_id=' . (int) $row['id'] . '&status=confirmed'),
                'cbe_update_status_' . (int) $row['id']
            );
            $cancel_url = wp_nonce_url(
                admin_url('admin-post.php?action=cbe_update_booking_status&booking_id=' . (int) $row['id'] . '&status=cancelled'),
                'cbe_update_status_' . (int) $row['id']
            );

            echo '<tr>';
            echo '<td>' . esc_html($row['id']) . '</td>';
            echo '<td>' . esc_html($cabin_title) . '</td>';
            echo '<td>' . esc_html($row['checkin_date']) . '</td>';
            echo '<td>' . esc_html($row['checkout_date']) . '</td>';
            echo '<td>' . esc_html($row['nights']) . '</td>';
            echo '<td>' . esc_html(number_format((float) $row['total_price'], 2)) . '</td>';
            echo '<td>' . esc_html($row['payment_method'] . ' / ' . $row['payment_status']) . '</td>';
            echo '<td>' . esc_html($row['payment_invoice_number'] !== '' ? $row['payment_invoice_number'] : '-') . '</td>';
            echo '<td>' . esc_html($row['guest_name']) . '</td>';
            echo '<td>' . esc_html($row['guest_email']) . '<br>' . esc_html($row['guest_phone']) . '</td>';
            echo '<td>' . esc_html($row['total_guests']) . '</td>';
            echo '<td><strong>' . esc_html($row['status']) . '</strong></td>';
            echo '<td><a class="button button-small" href="' . esc_url($view_url) . '">' . esc_html__('View', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url($approve_url) . '">' . esc_html__('Confirm', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url($cancel_url) . '">' . esc_html__('Cancel', 'cabin-booking-engine') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_admin_booking_detail($booking) {
        $back_url = admin_url('admin.php?page=cbe-bookings');
        $cabin_title = get_the_title((int) $booking['cabin_id']);
        $status_panel = $this->render_booking_status_panel($booking, '');
        $log_output = !empty($booking['payment_log']) ? nl2br(esc_html($booking['payment_log'])) : esc_html__('No payment logs yet.', 'cabin-booking-engine');

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Booking Detail', 'cabin-booking-engine') . '</h1>';
        echo '<p><a class="button" href="' . esc_url($back_url) . '">' . esc_html__('Back to Bookings', 'cabin-booking-engine') . '</a></p>';
        echo $status_panel;
        echo '<div class="cbe-admin-card">';
        echo '<h2>' . esc_html__('Booking Data', 'cabin-booking-engine') . '</h2>';
        echo '<table class="widefat striped">';
        echo '<tbody>';
        echo '<tr><td>' . esc_html__('Room', 'cabin-booking-engine') . '</td><td>' . esc_html($cabin_title) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Guest', 'cabin-booking-engine') . '</td><td>' . esc_html($booking['guest_name']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Email', 'cabin-booking-engine') . '</td><td>' . esc_html($booking['guest_email']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Phone', 'cabin-booking-engine') . '</td><td>' . esc_html($booking['guest_phone']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Guests', 'cabin-booking-engine') . '</td><td>' . esc_html($booking['total_guests']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Payment URL', 'cabin-booking-engine') . '</td><td>' . (!empty($booking['payment_url']) ? '<a href="' . esc_url($booking['payment_url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open Payment Link', 'cabin-booking-engine') . '</a>' : '-') . '</td></tr>';
        echo '<tr><td>' . esc_html__('Notes', 'cabin-booking-engine') . '</td><td>' . esc_html($booking['notes']) . '</td></tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '<div class="cbe-admin-card">';
        echo '<h2>' . esc_html__('DOKU Log', 'cabin-booking-engine') . '</h2>';
        echo '<div class="cbe-log-box">' . $log_output . '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function update_booking_status() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        $booking_id = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';

        if ($booking_id <= 0 || !in_array($status, array('confirmed', 'cancelled', 'pending', 'pending_payment', 'payment_failed'), true)) {
            wp_safe_redirect(admin_url('admin.php?page=cbe-bookings'));
            exit;
        }

        check_admin_referer('cbe_update_status_' . $booking_id);

        global $wpdb;
        $payment_status = 'unpaid';
        if ($status === 'confirmed') {
            $payment_status = 'paid';
        } elseif ($status === 'pending_payment') {
            $payment_status = 'pending';
        } elseif ($status === 'payment_failed') {
            $payment_status = 'failed';
        } elseif ($status === 'cancelled') {
            $payment_status = 'cancelled';
        }

        $wpdb->update(
            $this->table_name,
            array(
                'status' => $status,
                'payment_status' => $payment_status,
            ),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );

        $this->append_booking_log($booking_id, 'Booking status updated manually', array(
            'status' => $status,
            'payment_status' => $payment_status,
        ));

        wp_safe_redirect(admin_url('admin.php?page=cbe-bookings'));
        exit;
    }

}
