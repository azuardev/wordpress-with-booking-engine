<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Availability_Trait {
    /**
     * Check if a cabin is available for the given date range
     *
     * @param int $cabin_id Cabin post ID
     * @param string $checkin_date Date in Y-m-d format
     * @param string $checkout_date Date in Y-m-d format
     * @param int $required_units Number of units needed (default 1)
     * @return bool True if available, false otherwise
     */
    public function is_cabin_available_home($cabin_id, $checkin_date, $checkout_date, $required_units = 1) {
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

        $available_units = $total_units - $booked_units;
        return $available_units >= $required_units;
    }

    /**
     * Get availability details for a cabin
     *
     * @param int $cabin_id Cabin post ID
     * @param string $checkin_date Date in Y-m-d format
     * @param string $checkout_date Date in Y-m-d format
     * @return array Availability info
     */
    public function get_availability_details($cabin_id, $checkin_date, $checkout_date) {
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

        $available_units = max(0, $total_units - $booked_units);
        $bed_type = (string) get_post_meta($cabin_id, '_cbe_bed_type', true);
        $max_guests = (int) get_post_meta($cabin_id, '_cbe_max_guests', true);
        $stay_group = (string) get_post_meta($cabin_id, '_cbe_stay_group', true);
        $thumbnail_url = get_the_post_thumbnail_url($cabin_id, 'medium_large');
        $permalink = get_permalink($cabin_id);

        return array(
            'cabin_id' => $cabin_id,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'total_units' => $total_units,
            'booked_units' => $booked_units,
            'available_units' => $available_units,
            'is_available' => $available_units > 0,
            'price_per_night' => (float) $this->get_cabin_price_per_night($cabin_id),
            'cabin_name' => get_the_title($cabin_id),
            'bed_type' => $bed_type,
            'max_guests' => $max_guests,
            'stay_group' => $stay_group,
            'thumbnail_url' => is_string($thumbnail_url) ? $thumbnail_url : '',
            'detail_url' => is_string($permalink) ? $permalink : '',
        );
    }

    /**
     * Get availability for multiple cabins in a group
     *
     * @param string $group Cabin group name (optional)
     * @param string $checkin_date Date in Y-m-d format
     * @param string $checkout_date Date in Y-m-d format
     * @return array Array of availability data for each cabin
     */
    public function get_group_availability($group, $checkin_date, $checkout_date, $only_available = false) {
        $args = array(
            'post_type' => 'cabin',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        if ($group !== '' && $group !== 'all') {
            $normalized_group = sanitize_text_field($group);
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_cbe_stay_group',
                    'value' => $normalized_group,
                    'compare' => '=',
                ),
                array(
                    'key' => '_cbe_group',
                    'value' => $normalized_group,
                    'compare' => '=',
                ),
            );
        }

        $cabin_ids = get_posts($args);
        $availability = array();

        foreach ($cabin_ids as $cabin_id) {
            $details = $this->get_availability_details($cabin_id, $checkin_date, $checkout_date);
            if ($only_available && empty($details['is_available'])) {
                continue;
            }
            $availability[] = $details;
        }

        return $availability;
    }

    /**
     * REST API callback for checking availability
     */
    public function handle_availability_check($request) {
        $cabin_id = (int) $request->get_param('cabin_id');
        $group = sanitize_text_field($request->get_param('group'));
        $checkin_date = sanitize_text_field($request->get_param('checkin_date'));
        $checkout_date = sanitize_text_field($request->get_param('checkout_date'));
        $only_available = filter_var($request->get_param('only_available'), FILTER_VALIDATE_BOOLEAN);

        if ($cabin_id <= 0 && $group === '') {
            $group = 'all';
        }

        if ($checkin_date === '' || $checkout_date === '') {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Check-in and check-out dates are required.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        if (!$this->is_valid_date_range($checkin_date, $checkout_date)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Invalid date range.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        if ($cabin_id > 0 && get_post_type($cabin_id) === 'cabin') {
            $availability = $this->get_availability_details($cabin_id, $checkin_date, $checkout_date);
        } elseif ($group !== '') {
            $availability = $this->get_group_availability($group, $checkin_date, $checkout_date, $only_available);
        } else {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Either cabin_id or group parameter is required.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => $availability,
                'checkin_date' => $checkin_date,
                'checkout_date' => $checkout_date,
            ),
            200
        );
    }

    /**
     * REST API callback for getting price estimate
     */
    public function handle_price_estimate($request) {
        $cabin_id = (int) $request->get_param('cabin_id');
        $checkin_date = sanitize_text_field($request->get_param('checkin_date'));
        $checkout_date = sanitize_text_field($request->get_param('checkout_date'));

        if ($cabin_id <= 0 || get_post_type($cabin_id) !== 'cabin') {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Invalid cabin ID.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        if ($checkin_date === '' || $checkout_date === '') {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Check-in and check-out dates are required.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        if (!$this->is_valid_date_range($checkin_date, $checkout_date)) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Invalid date range.', 'cabin-booking-engine'),
                ),
                400
            );
        }

        $nights = $this->calculate_nights($checkin_date, $checkout_date);
        $price_per_night = $this->get_cabin_price_per_night($cabin_id);
        $total_price = round($nights * $price_per_night, 2);

        return new WP_REST_Response(
            array(
                'success' => true,
                'data' => array(
                    'cabin_id' => $cabin_id,
                    'cabin_name' => get_the_title($cabin_id),
                    'checkin_date' => $checkin_date,
                    'checkout_date' => $checkout_date,
                    'nights' => $nights,
                    'price_per_night' => $price_per_night,
                    'total_price' => $total_price,
                    'is_available' => $this->is_cabin_available_home($cabin_id, $checkin_date, $checkout_date),
                ),
            ),
            200
        );
    }
}
