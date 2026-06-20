<?php
/**
 * Plugin Name: Hotel Booking Engine
 * Description: Custom booking engine for hotels with availability checks, direct DOKU payments, and booking management.
 * Version: 1.5.3
 * Author: Local Dev
 * Text Domain: cabin-booking-engine
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CBE_PLUGIN_FILE', __FILE__);
define('CBE_PLUGIN_DIR', plugin_dir_path(CBE_PLUGIN_FILE));
define('CBE_PLUGIN_URL', plugin_dir_url(CBE_PLUGIN_FILE));
define('CBE_PLUGIN_VERSION', '1.5.3');
define('CBE_INCLUDES_DIR', CBE_PLUGIN_DIR . 'includes/');

require_once CBE_INCLUDES_DIR . 'helpers/functions.php';
require_once CBE_INCLUDES_DIR . 'core/trait-cbe-core-engine.php';
require_once CBE_INCLUDES_DIR . 'core/trait-cbe-bookings.php';
require_once CBE_INCLUDES_DIR . 'core/trait-cbe-availability.php';
require_once CBE_INCLUDES_DIR . 'core/trait-cbe-booking-messages.php';
require_once CBE_INCLUDES_DIR . 'admin/trait-cbe-admin-pages.php';
require_once CBE_INCLUDES_DIR . 'admin/trait-cbe-admin-meta-assets.php';
require_once CBE_INCLUDES_DIR . 'admin/trait-cbe-admin-settings-bookings.php';
require_once CBE_INCLUDES_DIR . 'frontend/trait-cbe-frontend-pages.php';
require_once CBE_INCLUDES_DIR . 'class-cabin-booking-engine.php';

register_activation_hook(CBE_PLUGIN_FILE, array('Cabin_Booking_Engine', 'activate'));
register_deactivation_hook(CBE_PLUGIN_FILE, array('Cabin_Booking_Engine', 'deactivate'));

Cabin_Booking_Engine::instance();
