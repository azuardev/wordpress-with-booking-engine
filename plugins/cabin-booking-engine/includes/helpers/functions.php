<?php
/**
 * Hotel Booking Engine - Helper Functions
 *
 * @package HotelBookingEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the main plugin instance
 *
 * @return Cabin_Booking_Engine
 */
function cbe() {
    return Cabin_Booking_Engine::instance();
}

/**
 * Get plugin option
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function cbe_get_option($key, $default = '') {
    $settings = get_option(Cabin_Booking_Engine::SETTINGS_KEY, array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Update plugin option
 *
 * @param string $key
 * @param mixed  $value
 * @return bool
 */
function cbe_update_option($key, $value) {
    $settings = get_option(Cabin_Booking_Engine::SETTINGS_KEY, array());

    if (!is_array($settings)) {
        $settings = array();
    }

    $settings[$key] = $value;

    return update_option(Cabin_Booking_Engine::SETTINGS_KEY, $settings);
}

/**
 * Get the price per night for a cabin
 *
 * @param int $cabin_id
 * @return int
 */
function cbe_get_cabin_price($cabin_id) {
    return (int) get_post_meta((int) $cabin_id, '_cbe_price_per_night', true);
}

/**
 * Get the database table name
 *
 * @return string
 */
function cbe_get_bookings_table() {
    global $wpdb;
    return $wpdb->prefix . Cabin_Booking_Engine::TABLE_SUFFIX;
}
