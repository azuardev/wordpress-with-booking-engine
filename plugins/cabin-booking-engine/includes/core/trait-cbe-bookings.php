<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Bookings_Trait {
    public function handle_booking_submission() {
        if (!isset($_POST[self::NONCE_FIELD]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD])), self::NONCE_ACTION)) {
            wp_die(esc_html__('Invalid request', 'cabin-booking-engine'));
        }

        $cabin_id = isset($_POST['cabin_id']) ? (int) $_POST['cabin_id'] : 0;
        $redirect_url = isset($_POST['redirect_url']) ? esc_url_raw(wp_unslash($_POST['redirect_url'])) : home_url('/');

        if ($cabin_id <= 0 || get_post_type($cabin_id) !== 'cabin') {
            $this->safe_redirect($redirect_url, 'invalid_cabin');
        }

        $checkin_date = isset($_POST['checkin_date']) ? sanitize_text_field(wp_unslash($_POST['checkin_date'])) : '';
        $checkout_date = isset($_POST['checkout_date']) ? sanitize_text_field(wp_unslash($_POST['checkout_date'])) : '';

        if (!$this->is_valid_date_range($checkin_date, $checkout_date)) {
            $this->safe_redirect($redirect_url, 'invalid_dates');
        }

        $guest_name = isset($_POST['guest_name']) ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : '';
        $guest_email = isset($_POST['guest_email']) ? sanitize_email(wp_unslash($_POST['guest_email'])) : '';
        $guest_phone = isset($_POST['guest_phone']) ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : '';
        $total_guests = isset($_POST['total_guests']) ? (int) sanitize_text_field(wp_unslash($_POST['total_guests'])) : 1;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        $payment_method = isset($_POST['payment_method']) ? sanitize_key(wp_unslash($_POST['payment_method'])) : 'manual';

        if ($guest_name === '' || $guest_email === '' || $total_guests < 1) {
            $this->safe_redirect($redirect_url, 'failed');
        }

        if (!in_array($payment_method, array('manual', 'doku'), true)) {
            $payment_method = 'manual';
        }

        if ($payment_method === 'doku' && !$this->is_doku_enabled()) {
            $this->safe_redirect($redirect_url, 'doku_unavailable');
        }

        $nights = $this->calculate_nights($checkin_date, $checkout_date);
        $selected_rooms_raw = isset($_POST['cbe_selected_rooms']) ? sanitize_text_field(wp_unslash($_POST['cbe_selected_rooms'])) : '';
        $requested_rooms = $this->parse_requested_rooms($selected_rooms_raw);
        if (empty($requested_rooms)) {
            $requested_rooms = array(
                $cabin_id => 1,
            );
        } elseif (!isset($requested_rooms[$cabin_id])) {
            $requested_rooms[$cabin_id] = 1;
        }

        foreach ($requested_rooms as $requested_cabin_id => $requested_units) {
            if ((int) $requested_cabin_id <= 0 || get_post_type((int) $requested_cabin_id) !== 'cabin') {
                $this->safe_redirect($redirect_url, 'invalid_cabin');
            }

            if (!$this->is_cabin_available((int) $requested_cabin_id, $checkin_date, $checkout_date, (int) $requested_units)) {
                $this->safe_redirect($redirect_url, 'not_available');
            }
        }

        // Keep DOKU flow simple and safe: multi-cabin selection is processed as manual booking.
        if (count($requested_rooms) > 1 && $payment_method === 'doku') {
            $payment_method = 'manual';
        }

        global $wpdb;
        $booking_ids = array();
        $aggregate_total_price = 0.0;
        $selected_room_lines = array();

        foreach ($requested_rooms as $requested_cabin_id => $requested_units) {
            $requested_cabin_id = (int) $requested_cabin_id;
            $requested_units = max(1, (int) $requested_units);
            $price_per_night = $this->get_cabin_price_per_night($requested_cabin_id);
            $single_total_price = round($nights * $price_per_night, 2);

            $selected_room_lines[] = $requested_units . 'x ' . get_the_title($requested_cabin_id);

            for ($unit_no = 1; $unit_no <= $requested_units; $unit_no++) {
                $inserted = $wpdb->insert(
                    $this->table_name,
                    array(
                        'cabin_id' => $requested_cabin_id,
                        'checkin_date' => $checkin_date,
                        'checkout_date' => $checkout_date,
                        'nights' => $nights,
                        'price_per_night' => $price_per_night,
                        'total_price' => $single_total_price,
                        'payment_method' => $payment_method,
                        'payment_status' => $payment_method === 'doku' ? 'pending' : 'unpaid',
                        'guest_name' => $guest_name,
                        'guest_email' => $guest_email,
                        'guest_phone' => $guest_phone,
                        'total_guests' => $total_guests,
                        'notes' => $notes,
                        'status' => $payment_method === 'doku' ? 'pending_payment' : 'pending',
                        'payment_log' => '',
                    ),
                    array('%d', '%s', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
                );

                if (!$inserted) {
                    $this->safe_redirect($redirect_url, 'failed');
                }

                $booking_ids[] = (int) $wpdb->insert_id;
                $aggregate_total_price += $single_total_price;
            }
        }

        if (empty($booking_ids)) {
            $this->safe_redirect($redirect_url, 'failed');
        }

        $booking_id = (int) $booking_ids[0];
        $booking_data = array(
            'id' => $booking_id,
            'cabin_id' => $cabin_id,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'nights' => $nights,
            'price_per_night' => $this->get_cabin_price_per_night($cabin_id),
            'total_price' => round($aggregate_total_price, 2),
            'payment_method' => $payment_method,
            'payment_status' => $payment_method === 'doku' ? 'pending' : 'unpaid',
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'guest_phone' => $guest_phone,
            'total_guests' => $total_guests,
            'notes' => trim($notes . "\n" . 'Cabins: ' . implode(', ', $selected_room_lines)),
            'status' => $payment_method === 'doku' ? 'pending_payment' : 'pending',
            'redirect_url' => $redirect_url,
        );

        $this->append_booking_log($booking_id, 'Booking created', array(
            'payment_method' => $payment_method,
            'total_price' => round($aggregate_total_price, 2),
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'selected_rooms' => implode(', ', $selected_room_lines),
            'booking_units' => count($booking_ids),
        ));

        $this->send_booking_notifications($booking_id, $booking_data);

        if ($payment_method === 'doku') {
            $doku_result = $this->start_doku_payment_flow($booking_id, $booking_data);
            if (is_wp_error($doku_result)) {
                $wpdb->update(
                    $this->table_name,
                    array(
                        'status' => 'payment_failed',
                        'payment_status' => 'failed',
                    ),
                    array('id' => $booking_id),
                    array('%s', '%s'),
                    array('%d')
                );

                $this->append_booking_log($booking_id, 'Failed to initialize DOKU payment');

                $this->safe_redirect($redirect_url, 'doku_failed');
            }

            wp_safe_redirect($doku_result['redirect']);
            exit;
        }

        $this->safe_redirect($redirect_url, 'success');
    }

    private function calculate_nights($checkin_date, $checkout_date) {
        $in = strtotime($checkin_date);
        $out = strtotime($checkout_date);
        if (!$in || !$out || $out <= $in) {
            return 1;
        }

        return max(1, (int) round(($out - $in) / DAY_IN_SECONDS));
    }

    private function get_cabin_price_per_night($cabin_id) {
        $price = (float) get_post_meta($cabin_id, '_cbe_price_per_night', true);
        return $price < 0 ? 0 : $price;
    }

    private function is_valid_date_range($checkin_date, $checkout_date) {
        $in = strtotime($checkin_date);
        $out = strtotime($checkout_date);
        $today = strtotime(date('Y-m-d'));

        if (!$in || !$out) {
            return false;
        }

        return $in >= $today && $out > $in;
    }

    public function is_cabin_available($cabin_id, $checkin_date, $checkout_date, $required_units = 1) {
        global $wpdb;

        $total_units = (int) get_post_meta($cabin_id, '_cbe_total_units', true);
        if ($total_units < 1) {
            $total_units = 1;
        }

        $booked_units = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$this->table_name}
                 WHERE cabin_id = %d
                   AND status IN ('pending', 'pending_payment', 'confirmed')
                   AND checkin_date < %s
                   AND checkout_date > %s",
                $cabin_id,
                $checkout_date,
                $checkin_date
            )
        );

        $required_units = max(1, (int) $required_units);
        $available_units = max(0, $total_units - $booked_units);
        return $available_units >= $required_units;
    }

    private function parse_requested_rooms($raw_rooms) {
        $raw = trim((string) $raw_rooms);
        if ($raw === '') {
            return array();
        }

        $pairs = array_filter(array_map('trim', explode(',', $raw)));
        $rooms = array();

        foreach ($pairs as $pair) {
            $parts = array_map('trim', explode(':', $pair));
            if (count($parts) !== 2) {
                continue;
            }

            $room_cabin_id = (int) $parts[0];
            $room_qty = max(1, (int) $parts[1]);
            if ($room_cabin_id <= 0) {
                continue;
            }

            if (!isset($rooms[$room_cabin_id])) {
                $rooms[$room_cabin_id] = 0;
            }
            $rooms[$room_cabin_id] += $room_qty;
        }

        return $rooms;
    }

    private function safe_redirect($url, $status) {
        wp_safe_redirect(add_query_arg('cbe_status', $status, $url));
        exit;
    }

    private function get_available_payment_methods() {
        $methods = array(
            'manual' => __('Pay on Arrival / Manual Confirmation', 'cabin-booking-engine'),
        );

        if ($this->is_doku_enabled()) {
            $methods['doku'] = __('Pay with DOKU', 'cabin-booking-engine');
        }

        return $methods;
    }

    private function is_doku_enabled() {
        $settings = $this->get_settings();
        return !empty($settings['doku_enabled']) && $this->has_doku_credentials($settings);
    }

    private function has_doku_credentials($settings) {
        if ($settings['doku_environment'] === 'production') {
            return $settings['doku_prod_client_id'] !== '' && $settings['doku_prod_shared_key'] !== '';
        }

        return $settings['doku_sandbox_client_id'] !== '' && $settings['doku_sandbox_shared_key'] !== '';
    }

    private function get_doku_credentials($settings) {
        if (!$this->has_doku_credentials($settings)) {
            return null;
        }

        if ($settings['doku_environment'] === 'production') {
            return array(
                'client_id' => $settings['doku_prod_client_id'],
                'shared_key' => $settings['doku_prod_shared_key'],
                'base_url' => 'https://api.doku.com',
            );
        }

        return array(
            'client_id' => $settings['doku_sandbox_client_id'],
            'shared_key' => $settings['doku_sandbox_shared_key'],
            'base_url' => 'https://api-sandbox.doku.com',
        );
    }

    private function generate_payment_invoice_number($booking_id) {
        return 'CBE-' . (int) $booking_id . '-' . gmdate('YmdHis');
    }

    private function start_doku_payment_flow($booking_id, $booking) {
        $settings = $this->get_settings();
        $credentials = $this->get_doku_credentials($settings);
        if (!$credentials) {
            $this->append_booking_log($booking_id, 'DOKU credentials missing');
            return new WP_Error('cbe_doku_config_missing', 'DOKU credentials are incomplete.');
        }

        $invoice_number = $this->generate_payment_invoice_number($booking_id);
        $payload = $this->build_doku_checkout_payload($booking_id, $booking, $invoice_number, $settings);
        $response = $this->request_doku_checkout($payload, $credentials);
        if (is_wp_error($response)) {
            $this->append_booking_log($booking_id, 'DOKU checkout request failed', array(
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        $redirect_url = $this->extract_doku_redirect_url($response);
        if ($redirect_url === '') {
            $this->append_booking_log($booking_id, 'DOKU did not return payment URL', $response);
            return new WP_Error('cbe_doku_redirect_missing', 'DOKU redirect URL was not returned.');
        }

        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'payment_invoice_number' => $invoice_number,
                'payment_reference' => $this->extract_doku_payment_reference($response),
                'payment_url' => $redirect_url,
            ),
            array('id' => (int) $booking_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        $this->append_booking_log($booking_id, 'DOKU checkout created', array(
            'invoice_number' => $invoice_number,
            'payment_reference' => $this->extract_doku_payment_reference($response),
            'payment_url' => $redirect_url,
        ));

        return array(
            'result' => 'success',
            'redirect' => $redirect_url,
        );
    }

    private function build_doku_checkout_payload($booking_id, $booking, $invoice_number, $settings) {
        $callback_url = add_query_arg(
            array(
                'cbe_status' => 'pending_payment',
                'cbe_booking' => (int) $booking_id,
            ),
            $booking['redirect_url']
        );
        $cancel_url = add_query_arg(
            array(
                'cbe_status' => 'payment_failed',
                'cbe_booking' => (int) $booking_id,
            ),
            $booking['redirect_url']
        );

        return array(
            'order' => array(
                'invoice_number' => $invoice_number,
                'line_items' => array(
                    array(
                        'id' => 'cabin-' . (int) $booking['cabin_id'],
                        'name' => get_the_title((int) $booking['cabin_id']),
                        'price' => (float) $booking['price_per_night'],
                        'quantity' => (int) $booking['nights'],
                    ),
                ),
                'amount' => (float) $booking['total_price'],
                'callback_url' => $callback_url,
                'callback_url_cancel' => $cancel_url,
                'currency' => 'IDR',
                'auto_redirect' => !empty($settings['doku_auto_redirect']),
            ),
            'payment' => array(
                'payment_due_date' => (int) $settings['doku_expiry_time'],
            ),
            'customer' => array(
                'name' => $booking['guest_name'],
                'email' => $booking['guest_email'],
                'phone' => $this->format_phone_number($booking['guest_phone']),
            ),
            'additional_info' => array(
                'integration' => array(
                    'name' => 'cabin-booking-engine',
                    'version' => self::VERSION,
                    'cms_version' => get_bloginfo('version'),
                ),
                'method' => 'Direct DOKU Checkout',
                'booking_id' => (string) $booking_id,
                'doku_wallet_notify_url' => rest_url('cbe/v1/doku-notification'),
            ),
        );
    }

    private function request_doku_checkout($payload, $credentials) {
        $target_path = '/checkout/v1/payment';
        $body = wp_json_encode($payload);
        $headers = array(
            'Client-Id' => $credentials['client_id'],
            'Request-Id' => $this->guidv4(),
            'Request-Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Request-Target' => $target_path,
            'Content-Type' => 'application/json',
        );
        $headers['Signature'] = $this->generate_doku_signature($headers, $body, $credentials['shared_key']);

        $response = wp_remote_post(
            $credentials['base_url'] . $target_path,
            array(
                'headers' => $headers,
                'body' => $body,
                'method' => 'POST',
                'timeout' => 45,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($status_code < 200 || $status_code >= 300 || !is_array($data)) {
            return new WP_Error('cbe_doku_api_error', 'DOKU API returned an invalid response.');
        }

        return $data;
    }

    private function extract_doku_redirect_url($response) {
        if (!empty($response['response']['payment']['url'])) {
            return (string) $response['response']['payment']['url'];
        }

        return '';
    }

    private function extract_doku_payment_reference($response) {
        if (!empty($response['response']['online_to_offline_info']['payment_code'])) {
            return (string) $response['response']['online_to_offline_info']['payment_code'];
        }
        if (!empty($response['response']['virtual_account_info']['virtual_account_number'])) {
            return (string) $response['response']['virtual_account_info']['virtual_account_number'];
        }

        return '';
    }

    private function format_phone_number($phone_number) {
        $phone_number = trim((string) $phone_number);
        if ($phone_number === '') {
            return '';
        }

        if (substr($phone_number, 0, 2) === '08') {
            return '62' . substr($phone_number, 1);
        }

        return $phone_number;
    }

    private function guidv4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generate_doku_signature($headers, $body, $shared_key) {
        $digest = base64_encode(hash('sha256', $body, true));
        $raw_signature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $headers['Request-Target'] . "\n"
            . "Digest:" . $digest;

        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $raw_signature, htmlspecialchars_decode($shared_key), true));
    }

    private function generate_doku_notification_signature($headers, $body, $shared_key, $request_target) {
        $digest = base64_encode(hash('sha256', $body, true));
        $raw_signature = "Client-Id:" . $this->get_notification_header_value($headers, 'client_id') . "\n"
            . "Request-Id:" . $this->get_notification_header_value($headers, 'request_id') . "\n"
            . "Request-Timestamp:" . $this->get_notification_header_value($headers, 'request_timestamp') . "\n"
            . "Request-Target:" . $request_target . "\n"
            . "Digest:" . $digest;

        return 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $raw_signature, htmlspecialchars_decode($shared_key), true));
    }

    private function get_notification_header_value($headers, $key) {
        if (!isset($headers[$key])) {
            return '';
        }

        return is_array($headers[$key]) ? (string) reset($headers[$key]) : (string) $headers[$key];
    }

    private function get_doku_notification_request_target() {
        $path = wp_parse_url(rest_url('cbe/v1/doku-notification'), PHP_URL_PATH);
        return is_string($path) ? $path : '/wp-json/cbe/v1/doku-notification';
    }

    private function find_booking_by_invoice_number($invoice_number) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE payment_invoice_number = %s LIMIT 1",
                $invoice_number
            ),
            ARRAY_A
        );
    }

    private function map_doku_transaction_to_booking_status($transaction_status) {
        $status = strtoupper((string) $transaction_status);
        if ($status === 'SUCCESS') {
            return array('status' => 'confirmed', 'payment_status' => 'paid');
        }
        if ($status === 'FAILED' || $status === 'EXPIRED') {
            return array('status' => 'payment_failed', 'payment_status' => 'failed');
        }
        if ($status === 'CANCELLED') {
            return array('status' => 'cancelled', 'payment_status' => 'cancelled');
        }

        return array('status' => 'pending_payment', 'payment_status' => 'pending');
    }

    private function extract_notification_payment_reference($payload) {
        if (!empty($payload['online_to_offline_info']['payment_code'])) {
            return (string) $payload['online_to_offline_info']['payment_code'];
        }
        if (!empty($payload['virtual_account_info']['virtual_account_number'])) {
            return (string) $payload['virtual_account_info']['virtual_account_number'];
        }

        return '';
    }

    public function handle_doku_notification(WP_REST_Request $request) {
        $payload = $request->get_json_params();
        if (!is_array($payload) || empty($payload['order']['invoice_number']) || empty($payload['transaction']['status'])) {
            return new WP_REST_Response(array('message' => 'Invalid payload'), 400);
        }

        $settings = $this->get_settings();
        $credentials = $this->get_doku_credentials($settings);
        if (!$credentials) {
            return new WP_REST_Response(array('message' => 'DOKU credentials missing'), 500);
        }

        $expected_signature = $this->generate_doku_notification_signature(
            $request->get_headers(),
            $request->get_body(),
            $credentials['shared_key'],
            $this->get_doku_notification_request_target()
        );
        $provided_signature = $this->get_notification_header_value($request->get_headers(), 'signature');
        if ($expected_signature !== $provided_signature) {
            $booking = $this->find_booking_by_invoice_number((string) $payload['order']['invoice_number']);
            if ($booking) {
                $this->append_booking_log((int) $booking['id'], 'DOKU notification rejected: invalid signature');
            }
            return new WP_REST_Response(array('message' => 'Invalid signature'), 400);
        }

        $booking = $this->find_booking_by_invoice_number((string) $payload['order']['invoice_number']);
        if (!$booking) {
            return new WP_REST_Response(array('message' => 'Booking not found'), 404);
        }

        $status_map = $this->map_doku_transaction_to_booking_status($payload['transaction']['status']);

        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'status' => $status_map['status'],
                'payment_status' => $status_map['payment_status'],
                'payment_reference' => $this->extract_notification_payment_reference($payload),
            ),
            array('id' => (int) $booking['id']),
            array('%s', '%s', '%s'),
            array('%d')
        );

        $this->append_booking_log((int) $booking['id'], 'DOKU notification received', array(
            'transaction_status' => $payload['transaction']['status'],
            'payment_reference' => $this->extract_notification_payment_reference($payload),
        ));

        return new WP_REST_Response(array('message' => 'OK'), 200);
    }

    public function handle_retry_payment() {
        $booking_id = isset($_REQUEST['booking_id']) ? (int) wp_unslash($_REQUEST['booking_id']) : 0;
        $redirect_url = isset($_REQUEST['redirect_url']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_url'])) : home_url('/');

        if ($booking_id <= 0 || !isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'cbe_retry_payment_' . $booking_id)) {
            wp_die(esc_html__('Invalid retry request', 'cabin-booking-engine'));
        }

        $booking = $this->get_booking($booking_id);
        if (!$booking || !$this->can_retry_booking($booking)) {
            $this->safe_redirect($redirect_url, 'payment_failed');
        }

        $this->append_booking_log($booking_id, 'Retry payment requested');

        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'status' => 'pending_payment',
                'payment_status' => 'pending',
            ),
            array('id' => $booking_id),
            array('%s', '%s'),
            array('%d')
        );

        $retry_result = $this->start_doku_payment_flow($booking_id, $this->map_booking_row_to_payment_request($booking, $redirect_url));
        if (is_wp_error($retry_result) || empty($retry_result['redirect'])) {
            $this->append_booking_log($booking_id, 'Retry payment failed', array(
                'error' => is_wp_error($retry_result) ? $retry_result->get_error_message() : 'Missing redirect URL',
            ));
            $this->safe_redirect(add_query_arg('cbe_booking', $booking_id, $redirect_url), 'doku_failed');
        }

        wp_safe_redirect($retry_result['redirect']);
        exit;
    }

    private function get_booking($booking_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d LIMIT 1", $booking_id),
            ARRAY_A
        );
    }

    private function can_retry_booking($booking) {
        return is_array($booking)
            && isset($booking['payment_method'], $booking['status'])
            && $booking['payment_method'] === 'doku'
            && in_array($booking['status'], array('pending_payment', 'payment_failed'), true);
    }

    private function map_booking_row_to_payment_request($booking, $redirect_url) {
        return array(
            'id' => (int) $booking['id'],
            'cabin_id' => (int) $booking['cabin_id'],
            'checkin_date' => $booking['checkin_date'],
            'checkout_date' => $booking['checkout_date'],
            'nights' => (int) $booking['nights'],
            'price_per_night' => (float) $booking['price_per_night'],
            'total_price' => (float) $booking['total_price'],
            'payment_method' => $booking['payment_method'],
            'payment_status' => $booking['payment_status'],
            'guest_name' => $booking['guest_name'],
            'guest_email' => $booking['guest_email'],
            'guest_phone' => $booking['guest_phone'],
            'total_guests' => (int) $booking['total_guests'],
            'notes' => $booking['notes'],
            'status' => $booking['status'],
            'redirect_url' => $redirect_url,
        );
    }

    private function append_booking_log($booking_id, $message, $context = array()) {
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            return;
        }

        $line = '[' . gmdate('Y-m-d H:i:s') . ' UTC] ' . $message;
        if (!empty($context)) {
            $line .= ' | ' . wp_json_encode($context);
        }

        $existing_log = isset($booking['payment_log']) ? (string) $booking['payment_log'] : '';
        $new_log = trim($existing_log . "\n" . $line);

        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array('payment_log' => $new_log),
            array('id' => $booking_id),
            array('%s'),
            array('%d')
        );
    }

    private function get_status_heading($booking) {
        if ($booking['status'] === 'confirmed') {
            return __('Booking Confirmed', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'pending_payment') {
            return __('Payment Pending', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'payment_failed') {
            return __('Payment Failed', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'cancelled') {
            return __('Booking Cancelled', 'cabin-booking-engine');
        }

        return __('Booking Submitted', 'cabin-booking-engine');
    }

    private function get_status_description($booking, $status_query) {
        if ($status_query === 'payment_success' || $booking['status'] === 'confirmed') {
            return __('Payment has been received and the booking is confirmed.', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'pending_payment') {
            return __('Your booking exists, but payment is still waiting to be completed or confirmed by DOKU.', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'payment_failed') {
            return __('The previous payment attempt failed or expired. You can retry payment below.', 'cabin-booking-engine');
        }
        if ($booking['status'] === 'cancelled') {
            return __('This booking is cancelled and can no longer be paid.', 'cabin-booking-engine');
        }

        return __('Your booking has been recorded successfully.', 'cabin-booking-engine');
    }

    private function render_retry_payment_button($booking) {
        if (!$this->can_retry_booking($booking)) {
            return '';
        }

        $retry_url = admin_url('admin-post.php');
        $nonce = wp_create_nonce('cbe_retry_payment_' . (int) $booking['id']);
        $redirect_url = get_permalink((int) $booking['cabin_id']);

        return '<form class="cbe-retry-form" method="post" action="' . esc_url($retry_url) . '">'
            . '<input type="hidden" name="action" value="cbe_retry_payment" />'
            . '<input type="hidden" name="booking_id" value="' . esc_attr((int) $booking['id']) . '" />'
            . '<input type="hidden" name="redirect_url" value="' . esc_url($redirect_url) . '" />'
            . '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '" />'
            . '<button type="submit" class="cbe-secondary-button">' . esc_html__('Pay Again', 'cabin-booking-engine') . '</button>'
            . '</form>';
    }

    private function render_booking_status_panel($booking, $status_query) {
        $status_class = 'cbe-status-card';
        if ($booking['status'] === 'confirmed') {
            $status_class .= ' cbe-status-card-success';
        } elseif ($booking['status'] === 'payment_failed' || $booking['status'] === 'cancelled') {
            $status_class .= ' cbe-status-card-error';
        }

        $output = '<div class="' . esc_attr($status_class) . '">';
        $output .= '<h3>' . esc_html($this->get_status_heading($booking)) . '</h3>';
        $output .= '<p>' . esc_html($this->get_status_description($booking, $status_query)) . '</p>';
        $output .= '<div class="cbe-status-grid">';
        $output .= '<div><span>' . esc_html__('Booking ID', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['id']) . '</strong></div>';
        $output .= '<div><span>' . esc_html__('Cabin', 'cabin-booking-engine') . '</span><strong>' . esc_html(get_the_title((int) $booking['cabin_id'])) . '</strong></div>';
        $output .= '<div><span>' . esc_html__('Check-in', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['checkin_date']) . '</strong></div>';
        $output .= '<div><span>' . esc_html__('Check-out', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['checkout_date']) . '</strong></div>';
        $output .= '<div><span>' . esc_html__('Total Price', 'cabin-booking-engine') . '</span><strong>' . esc_html(number_format_i18n((float) $booking['total_price'], 2)) . '</strong></div>';
        $output .= '<div><span>' . esc_html__('Payment Status', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['payment_status']) . '</strong></div>';
        if (!empty($booking['payment_invoice_number'])) {
            $output .= '<div><span>' . esc_html__('Invoice', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['payment_invoice_number']) . '</strong></div>';
        }
        if (!empty($booking['payment_reference'])) {
            $output .= '<div><span>' . esc_html__('Reference', 'cabin-booking-engine') . '</span><strong>' . esc_html($booking['payment_reference']) . '</strong></div>';
        }
        $output .= '</div>';
        $output .= $this->render_retry_payment_button($booking);
        $output .= '</div>';

        return $output;

    }
}
