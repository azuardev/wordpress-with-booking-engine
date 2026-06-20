<?php

final class Cabin_Booking_Engine {
    use CBE_Core_Engine_Trait;
    use CBE_Bookings_Trait;
    use CBE_Availability_Trait;
    use CBE_Booking_Messages_Trait;
    use CBE_Admin_Pages_Trait;
    use CBE_Admin_Meta_Assets_Trait;
    use CBE_Admin_Settings_Bookings_Trait;
    use CBE_Frontend_Pages_Trait;

    const VERSION = '1.5.3';
    const TABLE_SUFFIX = 'cbe_bookings';
    const NONCE_ACTION = 'cbe_submit_booking';
    const NONCE_FIELD = 'cbe_nonce';
    const SETTINGS_KEY = 'cbe_settings';

    /** @var Cabin_Booking_Engine|null */
    private static $instance = null;

    /** @var string */
    private $table_name;

    /** @var array<int,bool> Cabin IDs that need a modal rendered in footer */
    private static $modal_cabin_ids = array();

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . self::TABLE_SUFFIX;

        add_action('init', array($this, 'maybe_upgrade_schema'));
        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'maybe_flush_rewrite_rules'));
        add_action('init', array($this, 'ensure_default_results_page'), 20);
        add_action('init', array($this, 'register_shortcodes'));
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post_cabin', array($this, 'save_cabin_meta'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_filter('template_include', array($this, 'load_virtual_templates'));
        add_action('template_redirect', array($this, 'render_virtual_stay_page'));
        add_filter('the_content', array($this, 'append_booking_section_to_single_cabin'));
        add_filter('elementor/frontend/the_content', array($this, 'append_booking_section_to_single_cabin'));
        add_action('wp_footer', array($this, 'render_modal_footer'), 20);
        add_filter('manage_cabin_posts_columns', array($this, 'filter_cabin_admin_columns'));
        add_action('manage_cabin_posts_custom_column', array($this, 'render_cabin_admin_column'), 10, 2);
        add_filter('manage_edit-cabin_sortable_columns', array($this, 'register_cabin_sortable_columns'));
        add_action('pre_get_posts', array($this, 'handle_cabin_admin_sorting'));

        add_action('admin_post_cbe_submit_booking', array($this, 'handle_booking_submission'));
        add_action('admin_post_nopriv_cbe_submit_booking', array($this, 'handle_booking_submission'));
        add_action('admin_post_cbe_retry_payment', array($this, 'handle_retry_payment'));
        add_action('admin_post_nopriv_cbe_retry_payment', array($this, 'handle_retry_payment'));
        add_action('admin_post_cbe_save_cabin_custom', array($this, 'handle_save_cabin_custom'));
        add_action('admin_post_cbe_delete_cabin_custom', array($this, 'handle_delete_cabin_custom'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_admin_page'));
        add_action('admin_post_cbe_save_stay_page_custom', array($this, 'handle_save_stay_page_custom'));
        add_action('admin_post_cbe_delete_stay_page_custom', array($this, 'handle_delete_stay_page_custom'));
        add_action('admin_post_cbe_save_facility_catalog', array($this, 'handle_save_facility_catalog'));
        add_action('admin_post_cbe_update_booking_status', array($this, 'update_booking_status'));
    }

}

