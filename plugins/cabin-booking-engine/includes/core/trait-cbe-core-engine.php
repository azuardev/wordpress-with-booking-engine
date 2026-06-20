<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Core_Engine_Trait {
    public static function activate() {
        self::create_booking_table();
        update_option('cbe_db_version', self::VERSION);
        update_option('cbe_rewrite_version', self::VERSION);
        self::instance()->register_cpt();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    private static function create_booking_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_SUFFIX;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cabin_id BIGINT UNSIGNED NOT NULL,
            checkin_date DATE NOT NULL,
            checkout_date DATE NOT NULL,
            nights INT UNSIGNED NOT NULL DEFAULT 1,
            price_per_night DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'manual',
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            payment_invoice_number VARCHAR(100) NOT NULL DEFAULT '',
            payment_reference VARCHAR(100) NOT NULL DEFAULT '',
            payment_url TEXT,
            payment_log LONGTEXT,
            guest_name VARCHAR(190) NOT NULL,
            guest_email VARCHAR(190) NOT NULL,
            guest_phone VARCHAR(50) DEFAULT '',
            total_guests INT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY cabin_id (cabin_id),
            KEY dates (checkin_date, checkout_date),
            KEY status (status),
            KEY payment_invoice_number (payment_invoice_number)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function maybe_upgrade_schema() {
        $db_version = get_option('cbe_db_version', '0');
        if (version_compare($db_version, self::VERSION, '>=')) {
            return;
        }

        self::create_booking_table();
        update_option('cbe_db_version', self::VERSION);
    }

    public function register_cpt() {
        $labels = array(
            'name' => __('Rooms', 'cabin-booking-engine'),
            'singular_name' => __('Room', 'cabin-booking-engine'),
            'add_new' => __('Add New Room', 'cabin-booking-engine'),
            'add_new_item' => __('Add New Room', 'cabin-booking-engine'),
            'edit_item' => __('Edit Room', 'cabin-booking-engine'),
            'new_item' => __('New Room', 'cabin-booking-engine'),
            'view_item' => __('View Room', 'cabin-booking-engine'),
            'search_items' => __('Search Rooms', 'cabin-booking-engine'),
            'not_found' => __('No rooms found', 'cabin-booking-engine'),
            'menu_name' => __('Rooms', 'cabin-booking-engine'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-admin-home',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => 'cabins'),
        );

        register_post_type('cabin', $args);

        register_post_type('cbe_stay_page', array(
            'labels' => array(
                'name' => __('Stay Pages', 'cabin-booking-engine'),
                'singular_name' => __('Stay Page', 'cabin-booking-engine'),
                'add_new_item' => __('Add New Stay Page', 'cabin-booking-engine'),
                'edit_item' => __('Edit Stay Page', 'cabin-booking-engine'),
                'new_item' => __('New Stay Page', 'cabin-booking-engine'),
                'view_item' => __('View Stay Page', 'cabin-booking-engine'),
                'search_items' => __('Search Stay Pages', 'cabin-booking-engine'),
                'not_found' => __('No stay pages found', 'cabin-booking-engine'),
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => false,
            'show_in_menu' => false,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
            'has_archive' => false,
            'rewrite' => array('slug' => 'stay', 'with_front' => false),
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
        ));
    }

    public function register_rewrite_rules() {
    }

    public function register_query_vars($query_vars) {
        $query_vars[] = 'cbe_stay';
        $query_vars[] = 'cbe_stay_legacy';

        return $query_vars;
    }

    public function maybe_flush_rewrite_rules() {
        $rewrite_version = get_option('cbe_rewrite_version', '0');
        if ($rewrite_version === self::VERSION) {
            return;
        }

        flush_rewrite_rules();
        update_option('cbe_rewrite_version', self::VERSION);
    }

    public function ensure_default_results_page() {
        $saved_id = $this->get_saved_results_page_id();
        if ($saved_id > 0) {
            $saved_post = get_post($saved_id);
            if ($saved_post instanceof WP_Post && $saved_post->post_type === 'page' && $saved_post->post_status !== 'trash') {
                return;
            }
        }

        $existing_page = get_page_by_path('booking-results', OBJECT, 'page');
        if ($existing_page instanceof WP_Post && (int) $existing_page->ID > 0 && $existing_page->post_status !== 'trash') {
            $this->save_results_page_id((int) $existing_page->ID);
            return;
        }

        $new_page_id = wp_insert_post(
            array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => __('Booking Results', 'cabin-booking-engine'),
                'post_name' => 'booking-results',
                'post_content' => '[cabin_booking_search show_availability="1"]',
            ),
            true
        );

        if (!is_wp_error($new_page_id) && (int) $new_page_id > 0) {
            $this->save_results_page_id((int) $new_page_id);
        }
    }

    public function get_default_results_page_url() {
        $saved_id = $this->get_saved_results_page_id();
        if ($saved_id > 0) {
            $saved_url = get_permalink($saved_id);
            if ($saved_url) {
                return $saved_url;
            }
        }

        $existing_page = get_page_by_path('booking-results', OBJECT, 'page');
        if ($existing_page instanceof WP_Post && (int) $existing_page->ID > 0 && $existing_page->post_status !== 'trash') {
            $this->save_results_page_id((int) $existing_page->ID);
            $existing_url = get_permalink((int) $existing_page->ID);
            if ($existing_url) {
                return $existing_url;
            }
        }

        return home_url('/booking-results/');
    }

    private function get_saved_results_page_id() {
        $settings_raw = get_option(self::SETTINGS_KEY, array());
        $settings = is_array($settings_raw) ? $settings_raw : array();
        return isset($settings['results_page_id']) ? (int) $settings['results_page_id'] : 0;
    }

    private function save_results_page_id($page_id) {
        $settings_raw = get_option(self::SETTINGS_KEY, array());
        $settings = is_array($settings_raw) ? $settings_raw : array();
        $settings['results_page_id'] = (int) $page_id;
        update_option(self::SETTINGS_KEY, $settings);
    }

    public function load_virtual_templates($template) {
        if (is_singular('cbe_stay_page')) {
            $virtual_template = CBE_PLUGIN_DIR . 'templates/stay-page.php';
            if (file_exists($virtual_template)) {
                return $virtual_template;
            }
        }

        return $template;
    }

    public function render_virtual_stay_page() {
        $is_ajax = function_exists('wp_doing_ajax') ? wp_doing_ajax() : (defined('DOING_AJAX') && DOING_AJAX);
        $is_rest = function_exists('wp_doing_rest') ? wp_doing_rest() : (defined('REST_REQUEST') && REST_REQUEST);

        if (is_admin() || $is_ajax || $is_rest) {
            return;
        }

        if (is_singular('cbe_stay_page')) {
            return;
        }

        $stay_group = $this->resolve_stay_group_from_request();
        if ($stay_group === '') {
            return;
        }

        set_query_var('cbe_stay', $stay_group);
        set_query_var('cbe_stay_legacy', $stay_group);

        $template = CBE_PLUGIN_DIR . 'templates/stay-page.php';
        if (!file_exists($template)) {
            return;
        }

        status_header(200);
        nocache_headers();

        include $template;
        exit;
    }

    public function render_stay_page_body() {
        $queried = get_queried_object();

        if ($queried instanceof WP_Post && $queried->post_type === 'cbe_stay_page') {
            return $this->render_custom_stay_page_body($queried);
        }

        $legacy_group = sanitize_title((string) get_query_var('cbe_stay_legacy'));
        if ($legacy_group === '') {
            $legacy_group = $this->resolve_stay_group_from_request();
        }

        if ($legacy_group === '') {
            return '<div class="cbe-message cbe-error">' . esc_html__('Stay page not found.', 'cabin-booking-engine') . '</div>';
        }

        return do_shortcode('[cabin_listing group="' . esc_attr($legacy_group) . '"]');
    }

    private function render_custom_stay_page_body($page) {
        $selected_cabin_ids = $this->get_stay_page_cabin_ids((int) $page->ID);
        $page_facilities = (string) get_post_meta((int) $page->ID, '_cbe_page_facilities', true);
        $page_facility_items = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $page_facilities)));
        $page_overview = trim((string) $page->post_content);
        $page_overview_html = $page_overview !== '' ? apply_filters('the_content', $page_overview) : '';
        $room_count = count($selected_cabin_ids);
        $starting_price = 0.0;
        $hero_image_url = '';
        $page_gallery_ids_raw = (string) get_post_meta($page->ID, '_cbe_page_gallery_ids', true);
        $page_gallery_ids = array_filter(array_map('intval', explode(',', $page_gallery_ids_raw)));

        foreach ($selected_cabin_ids as $selected_cabin_id) {
            if ($selected_cabin_id <= 0 || get_post_type($selected_cabin_id) !== 'cabin') {
                continue;
            }

            $room_price = $this->get_cabin_price_per_night($selected_cabin_id);
            if ($room_price > 0 && ($starting_price <= 0 || $room_price < $starting_price)) {
                $starting_price = $room_price;
            }

            if ($hero_image_url === '') {
                $hero_image_url = (string) wp_get_attachment_image_url((int) get_post_thumbnail_id($selected_cabin_id), 'full');
            }
        }

        ob_start();
        ?>
        <main class="cbe-virtual-stay-page cbe-custom-stay-page">
            <section class="cbe-virtual-stay-hero<?php echo $hero_image_url !== '' ? ' cbe-virtual-stay-hero-has-image' : ''; ?>"<?php echo $hero_image_url !== '' ? ' style="--cbe-hero-image:url(' . esc_url($hero_image_url) . ');"' : ''; ?>>
                <div class="cbe-virtual-stay-hero-inner">
                    <h1 class="white-title">Indulge in our <?php echo esc_html(get_the_title($page->ID)); ?> Cabins</h1>
                </div>
            </section>

            <section class="cbe-virtual-stay-content">
                <?php if ($page_overview_html !== '') : ?>
                    <div class="cbe-custom-stay-intro">
                        <?php echo wp_kses_post($page_overview_html); ?>
                        <?php if ($room_count > 0 || $starting_price > 0) : ?>
                            <div class="cbe-virtual-stay-hero-meta cbe-custom-stay-overview-meta">
                                <?php if ($room_count > 0) : ?>
                                    <span><?php echo esc_html(sprintf(_n('%d Cabin Type', '%d Cabin Types', $room_count, 'cabin-booking-engine'), $room_count)); ?></span>
                                <?php endif; ?>
                                <?php if ($starting_price > 0) : ?>
                                    <span><?php echo esc_html(sprintf(__('From %s / night', 'cabin-booking-engine'), number_format_i18n($starting_price, 0))); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($page_gallery_ids)) : ?>
                    <div class="cbe-custom-stay-gallery">
                        <?php foreach ($page_gallery_ids as $gallery_image_id) :
                            $gallery_image_thumb_url = wp_get_attachment_image_url($gallery_image_id, 'medium');
                            $gallery_image_full_url = wp_get_attachment_image_url($gallery_image_id, 'large');
                            if (!$gallery_image_thumb_url) {
                                continue;
                            }
                            if (!$gallery_image_full_url) {
                                $gallery_image_full_url = $gallery_image_thumb_url;
                            }
                        ?>
                            <a class="cbe-custom-stay-gallery-thumb" href="<?php echo esc_url($gallery_image_full_url); ?>">
                                <img src="<?php echo esc_url($gallery_image_thumb_url); ?>" alt="<?php echo esc_attr(get_the_title($page->ID)); ?>" loading="lazy" />
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($selected_cabin_ids)) : ?>
                    <div class="cbe-message cbe-error"><?php esc_html_e('No rooms are assigned to this stay page yet.', 'cabin-booking-engine'); ?></div>
                <?php else : ?>
                    <div class="cbe-custom-stay-section-head">
                        <h4><?php esc_html_e('Cabins Type Available', 'cabin-booking-engine'); ?></h4>
                        <p><?php echo esc_html(sprintf(_n('%d curated room type for this stay.', '%d curated cabin types for this stay.', $room_count, 'cabin-booking-engine'), $room_count)); ?></p>
                    </div>
                    <div class="cbe-custom-stay-cabin-list">
                        <hr class="cbe-section-divider" />
                        <?php foreach ($selected_cabin_ids as $cabin_id) : ?>
                            <?php echo $this->render_stay_page_cabin_card($cabin_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }

    private function get_stay_page_cabin_ids($page_id) {
        $raw_ids = (string) get_post_meta($page_id, '_cbe_page_cabin_ids', true);
        $ids = array_filter(array_map('intval', explode(',', $raw_ids)));

        return array_values(array_unique($ids));
    }

    private function get_default_facility_icon_catalog() {
        return array(
            'wifi' => array('label' => __('WiFi', 'cabin-booking-engine'), 'icon' => 'ms:wifi'),
            'tv' => array('label' => __('Smart TV', 'cabin-booking-engine'), 'icon' => 'ms:tv'),
            'ac' => array('label' => __('Air Conditioning', 'cabin-booking-engine'), 'icon' => 'ms:ac_unit'),
            'bath' => array('label' => __('Private Bathroom', 'cabin-booking-engine'), 'icon' => 'ms:bathtub'),
            'shower' => array('label' => __('Hot Shower', 'cabin-booking-engine'), 'icon' => 'ms:shower'),
            'breakfast' => array('label' => __('Breakfast', 'cabin-booking-engine'), 'icon' => 'ms:breakfast_dining'),
            'pool' => array('label' => __('Swimming Pool', 'cabin-booking-engine'), 'icon' => 'ms:pool'),
            'parking' => array('label' => __('Parking', 'cabin-booking-engine'), 'icon' => 'ms:local_parking'),
            'service' => array('label' => __('Room Service', 'cabin-booking-engine'), 'icon' => 'ms:room_service'),
            'coffee' => array('label' => __('Coffee Maker', 'cabin-booking-engine'), 'icon' => 'ms:coffee_maker'),
        );
    }

    private function get_facility_icon_pool() {
        $pool = array();
        $added_values = array();

        foreach ($this->get_facility_icon_choices() as $icon_value => $icon_label) {
            if (!is_string($icon_value) || strpos($icon_value, 'ms:') !== 0) {
                continue;
            }

            if (isset($added_values[$icon_value])) {
                continue;
            }

            $pool[] = array(
                'value' => $icon_value,
                'label' => sprintf(
                    /* translators: %s icon name */
                    __('Material Symbols: %s', 'cabin-booking-engine'),
                    (string) $icon_label
                ),
            );
            $added_values[$icon_value] = true;
        }

        $extra_pool = array(
            array('value' => 'ms:phone_iphone', 'label' => __('Material Symbols: Phone', 'cabin-booking-engine')),
            array('value' => 'ms:laptop_mac', 'label' => __('Material Symbols: Laptop', 'cabin-booking-engine')),
            array('value' => 'ms:tablet_mac', 'label' => __('Material Symbols: Tablet', 'cabin-booking-engine')),
            array('value' => 'ms:chair', 'label' => __('Material Symbols: Chair', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-wifi', 'label' => __('Font Awesome: WiFi', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-tv', 'label' => __('Font Awesome: TV', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-snowflake', 'label' => __('Font Awesome: Air Conditioning', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-bath', 'label' => __('Font Awesome: Bathtub', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-shower', 'label' => __('Font Awesome: Shower', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-mug-saucer', 'label' => __('Font Awesome: Coffee', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-car', 'label' => __('Font Awesome: Parking', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-bell-concierge', 'label' => __('Font Awesome: Room Service', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-dumbbell', 'label' => __('Font Awesome: Fitness Center', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-shield-halved', 'label' => __('Font Awesome: Security', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-person-walking-luggage', 'label' => __('Font Awesome: Luggage', 'cabin-booking-engine')),
            array('value' => 'fa:fa-solid fa-umbrella-beach', 'label' => __('Font Awesome: Beach', 'cabin-booking-engine')),
            array('value' => 'bi:wifi', 'label' => __('Bootstrap Icons: WiFi', 'cabin-booking-engine')),
            array('value' => 'bi:tv', 'label' => __('Bootstrap Icons: TV', 'cabin-booking-engine')),
            array('value' => 'bi:snow', 'label' => __('Bootstrap Icons: Air Conditioning', 'cabin-booking-engine')),
            array('value' => 'bi:cup-hot', 'label' => __('Bootstrap Icons: Coffee', 'cabin-booking-engine')),
            array('value' => 'bi:building', 'label' => __('Bootstrap Icons: Building', 'cabin-booking-engine')),
            array('value' => 'bi:shield-check', 'label' => __('Bootstrap Icons: Security', 'cabin-booking-engine')),
            array('value' => 'bi:geo-alt', 'label' => __('Bootstrap Icons: Location', 'cabin-booking-engine')),
            array('value' => 'bi:check2-circle', 'label' => __('Bootstrap Icons: Check', 'cabin-booking-engine')),
            array('value' => 'bi:rocket-takeoff', 'label' => __('Bootstrap Icons: Shuttle', 'cabin-booking-engine')),
        );

        foreach ($extra_pool as $item) {
            $value = isset($item['value']) ? (string) $item['value'] : '';
            if ($value === '' || isset($added_values[$value])) {
                continue;
            }

            $pool[] = $item;
            $added_values[$value] = true;
        }

        return $pool;
    }

    private function get_facility_icon_choices() {
        return array(
            'ms:wifi' => __('WiFi', 'cabin-booking-engine'),
            'ms:tv' => __('TV', 'cabin-booking-engine'),
            'ms:desktop_windows' => __('Desktop', 'cabin-booking-engine'),
            'ms:smartphone' => __('Mobile Access', 'cabin-booking-engine'),
            'ms:ac_unit' => __('Air Conditioning', 'cabin-booking-engine'),
            'ms:air' => __('Fresh Air', 'cabin-booking-engine'),
            'ms:king_bed' => __('Bed', 'cabin-booking-engine'),
            'ms:bathtub' => __('Bathtub', 'cabin-booking-engine'),
            'ms:shower' => __('Shower', 'cabin-booking-engine'),
            'ms:hot_tub' => __('Hot Tub', 'cabin-booking-engine'),
            'ms:soap' => __('Toiletries', 'cabin-booking-engine'),
            'ms:dry_cleaning' => __('Dry Cleaning', 'cabin-booking-engine'),
            'ms:local_laundry_service' => __('Laundry', 'cabin-booking-engine'),
            'ms:breakfast_dining' => __('Breakfast', 'cabin-booking-engine'),
            'ms:restaurant' => __('Restaurant', 'cabin-booking-engine'),
            'ms:coffee_maker' => __('Coffee Maker', 'cabin-booking-engine'),
            'ms:kitchen' => __('Kitchen', 'cabin-booking-engine'),
            'ms:microwave' => __('Microwave', 'cabin-booking-engine'),
            'ms:room_service' => __('Room Service', 'cabin-booking-engine'),
            'ms:pool' => __('Swimming Pool', 'cabin-booking-engine'),
            'ms:fitness_center' => __('Fitness Center', 'cabin-booking-engine'),
            'ms:spa' => __('Spa', 'cabin-booking-engine'),
            'ms:beach_access' => __('Beach Access', 'cabin-booking-engine'),
            'ms:deck' => __('Terrace', 'cabin-booking-engine'),
            'ms:balcony' => __('Balcony', 'cabin-booking-engine'),
            'ms:landscape' => __('Garden View', 'cabin-booking-engine'),
            'ms:wb_sunny' => __('Sunlight', 'cabin-booking-engine'),
            'ms:water_drop' => __('Water', 'cabin-booking-engine'),
            'ms:local_parking' => __('Parking', 'cabin-booking-engine'),
            'ms:airport_shuttle' => __('Airport Shuttle', 'cabin-booking-engine'),
            'ms:elevator' => __('Elevator', 'cabin-booking-engine'),
            'ms:security' => __('Security', 'cabin-booking-engine'),
            'ms:lock' => __('Secure Access', 'cabin-booking-engine'),
            'ms:pets' => __('Pet Friendly', 'cabin-booking-engine'),
            'ms:child_care' => __('Family Friendly', 'cabin-booking-engine'),
            'ms:crib' => __('Baby Crib', 'cabin-booking-engine'),
            'ms:iron' => __('Iron', 'cabin-booking-engine'),
            'ms:charger' => __('Charger', 'cabin-booking-engine'),
            '📶' => __('Legacy Emoji WiFi', 'cabin-booking-engine'),
            '📺' => __('Legacy Emoji TV', 'cabin-booking-engine'),
            '❄️' => __('Legacy Emoji AC', 'cabin-booking-engine'),
            '🛁' => __('Legacy Emoji Bathtub', 'cabin-booking-engine'),
            '🚿' => __('Legacy Emoji Shower', 'cabin-booking-engine'),
            '🍳' => __('Legacy Emoji Breakfast', 'cabin-booking-engine'),
            '🏊' => __('Legacy Emoji Pool', 'cabin-booking-engine'),
            '🅿️' => __('Legacy Emoji Parking', 'cabin-booking-engine'),
            '🛎️' => __('Legacy Emoji Service', 'cabin-booking-engine'),
            '☕' => __('Legacy Emoji Coffee', 'cabin-booking-engine'),
        );
    }

    private function is_material_symbol_icon($icon) {
        return is_string($icon) && strpos($icon, 'ms:') === 0;
    }

    private function is_fontawesome_icon($icon) {
        return is_string($icon) && strpos($icon, 'fa:') === 0;
    }

    private function is_bootstrap_icon($icon) {
        return is_string($icon) && strpos($icon, 'bi:') === 0;
    }

    private function is_iconify_icon($icon) {
        if (!is_string($icon)) {
            return false;
        }

        if ($this->is_material_symbol_icon($icon) || $this->is_fontawesome_icon($icon) || $this->is_bootstrap_icon($icon)) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]+:[a-z0-9\-_]+$/i', $icon);
    }

    private function get_iconify_svg_url($icon) {
        $icon_name = rawurlencode((string) $icon);
        return 'https://api.iconify.design/' . $icon_name . '.svg';
    }

    private function get_material_symbol_name($icon) {
        $symbol_name = is_string($icon) ? substr($icon, 3) : '';
        $symbol_name = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $symbol_name));

        return $symbol_name;
    }

    private function get_fontawesome_class_name($icon) {
        $class_name = is_string($icon) ? substr($icon, 3) : '';
        $class_name = trim(preg_replace('/[^a-z0-9\-\s]/i', '', $class_name));

        return $class_name;
    }

    private function get_bootstrap_icon_class_name($icon) {
        $name = is_string($icon) ? substr($icon, 3) : '';
        $name = trim(preg_replace('/[^a-z0-9\-]/i', '', $name));

        return $name;
    }

    private function is_local_icon($icon) {
        return is_string($icon) && strpos($icon, 'local:') === 0;
    }

    private function get_local_icon_url($icon) {
        $filename = substr((string) $icon, 6);
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
        if ($filename === '') {
            return '';
        }

        return CBE_PLUGIN_URL . 'assets/icons/facilities/' . $filename;
    }

    private function render_facility_icon_markup($icon, $class_name = '') {
        $class_name = trim((string) $class_name);

        if ($this->is_local_icon($icon)) {
            $url = $this->get_local_icon_url($icon);
            if ($url !== '') {
                return '<img class="' . esc_attr(trim($class_name . ' cbe-local-icon')) . '" src="' . esc_url($url) . '" alt="" loading="lazy" decoding="async" />';
            }
        }

        if ($this->is_material_symbol_icon($icon)) {
            $symbol_name = $this->get_material_symbol_name($icon);
            if ($symbol_name !== '') {
                $classes = trim($class_name . ' cbe-material-symbol material-symbols-outlined');

                return '<span class="' . esc_attr($classes) . '" aria-hidden="true">' . esc_html($symbol_name) . '</span>';
            }
        }

        if ($this->is_fontawesome_icon($icon)) {
            $fa_class = $this->get_fontawesome_class_name($icon);
            if ($fa_class !== '') {
                return '<i class="' . esc_attr(trim($class_name . ' ' . $fa_class)) . '" aria-hidden="true"></i>';
            }
        }

        if ($this->is_bootstrap_icon($icon)) {
            $bi_class = $this->get_bootstrap_icon_class_name($icon);
            if ($bi_class !== '') {
                return '<i class="' . esc_attr(trim($class_name . ' bi bi-' . $bi_class)) . '" aria-hidden="true"></i>';
            }
        }

        if ($this->is_iconify_icon($icon)) {
            return '<img class="' . esc_attr(trim($class_name . ' cbe-iconify-icon')) . '" src="' . esc_url($this->get_iconify_svg_url($icon)) . '" alt="" loading="lazy" decoding="async" />';
        }

        return '<span class="' . esc_attr($class_name) . '" aria-hidden="true">' . esc_html($icon !== '' ? $icon : '•') . '</span>';
    }

    private function sanitize_facility_catalog($items) {
        $catalog = array();

        if (!is_array($items)) {
            return $catalog;
        }

        foreach ($items as $key => $item) {
            $facility_key = '';
            $label = '';
            $icon = '';

            if (is_array($item)) {
                $facility_key = isset($item['key']) ? sanitize_key((string) $item['key']) : sanitize_key((string) $key);
                $label = isset($item['label']) ? sanitize_text_field((string) $item['label']) : '';
                $icon = isset($item['icon']) ? sanitize_text_field((string) $item['icon']) : '';
            }

            if ($facility_key === '' && $label !== '') {
                $facility_key = sanitize_title($label);
            }

            if ($facility_key === '' || $label === '') {
                continue;
            }

            $catalog[$facility_key] = array(
                'label' => $label,
                'icon' => $icon !== '' ? $icon : 'ms:room_service',
            );
        }

        return $catalog;
    }

    private function get_facility_icon_catalog() {
        $saved_catalog = cbe_get_option('facility_catalog', null);

        if ($saved_catalog === null) {
            return array();
        }

        return $this->sanitize_facility_catalog($saved_catalog);
    }

    private function normalize_facility_items($items) {
        $catalog = $this->get_facility_icon_catalog();
        $normalized = array();

        if (!is_array($items)) {
            return $normalized;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $icon_key = isset($item['icon_key']) ? sanitize_key((string) $item['icon_key']) : '';
            $label = isset($item['label']) ? sanitize_text_field((string) $item['label']) : '';
            $icon = isset($item['icon']) ? sanitize_text_field((string) $item['icon']) : '🛎️';

            if ($icon_key !== '' && isset($catalog[$icon_key])) {
                $label = $catalog[$icon_key]['label'];
                $icon = $catalog[$icon_key]['icon'];
            }

            if ($label === '') {
                continue;
            }

            $normalized[] = array(
                'icon_key' => $icon_key !== '' ? $icon_key : 'service',
                'icon' => $icon,
                'label' => $label,
            );
        }

        return $normalized;
    }

    private function get_cabin_facilities($cabin_id) {
        // Try structured JSON first
        $json_raw = (string) get_post_meta($cabin_id, '_cbe_facilities_items', true);
        if ($json_raw !== '') {
            $decoded = json_decode($json_raw, true);
            if (is_array($decoded) && !empty($decoded)) {
                $normalized = $this->normalize_facility_items($decoded);
                if (!empty($normalized)) {
                    return $normalized;
                }
            }
        }

        // Fallback: parse plain-text _cbe_facilities
        $legacy_raw = (string) get_post_meta($cabin_id, '_cbe_facilities', true);
        if ($legacy_raw === '') {
            return array();
        }

        $legacy_normalized = str_replace(array("\r\n", "\r"), "\n", $legacy_raw);
        $legacy_normalized = str_replace(',', "\n", $legacy_normalized);
        $legacy_items = array_filter(array_map('trim', explode("\n", $legacy_normalized)));
        $result = array();

        foreach (array_values(array_unique($legacy_items)) as $legacy_item) {
            $result[] = array(
                'icon_key' => 'service',
                'icon' => '🛎️',
                'label' => $legacy_item,
            );
        }

        return $result;
    }

    private function render_stay_page_cabin_card($cabin_id) {
        if ($cabin_id <= 0 || get_post_type($cabin_id) !== 'cabin') {
            return '';
        }

        $price_per_night = $this->get_cabin_price_per_night($cabin_id);
        $bed_type = (string) get_post_meta($cabin_id, '_cbe_bed_type', true);
        $max_guests = (int) get_post_meta($cabin_id, '_cbe_max_guests', true);
        $total_units = (int) get_post_meta($cabin_id, '_cbe_total_units', true);
        $image = get_the_post_thumbnail($cabin_id, 'large', array('class' => 'cbe-cabin-image'));
        $featured_image_id = (int) get_post_thumbnail_id($cabin_id);
        $excerpt = get_the_excerpt($cabin_id);
        $content = trim((string) get_post_field('post_content', $cabin_id));
        $facilities = $this->get_cabin_facilities($cabin_id);
        $gallery_ids_raw = (string) get_post_meta($cabin_id, '_cbe_gallery_ids', true);
        $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_ids_raw)));
        $gallery_image_ids = array();

        if ($featured_image_id > 0) {
            $gallery_image_ids[] = $featured_image_id;
        }

        foreach ($gallery_ids as $gallery_id) {
            if ($gallery_id > 0 && !in_array($gallery_id, $gallery_image_ids, true)) {
                $gallery_image_ids[] = $gallery_id;
            }
        }

        // Prepare detail data for the modal
        $detail_data = array(
            'id' => (int) $cabin_id,
            'title' => get_the_title($cabin_id),
            'bedType' => $bed_type,
            'maxGuests' => $max_guests,
            'totalUnits' => $total_units,
            'pricePerNight' => $price_per_night,
            'excerpt' => $excerpt,
            'content' => $content,
            'facilities' => $facilities,
            'galleryImages' => array(),
        );

        // Add gallery image URLs
        foreach ($gallery_image_ids as $img_id) {
            $thumb_url = wp_get_attachment_image_url($img_id, 'medium');
            $full_url = wp_get_attachment_image_url($img_id, 'large');
            if ($thumb_url) {
                $detail_data['galleryImages'][] = array(
                    'thumb' => $thumb_url,
                    'full' => $full_url ?: $thumb_url,
                );
            }
        }

        $gallery_thumb_ids = array();
        if (count($gallery_image_ids) > 1) {
            $gallery_thumb_ids = array_slice($gallery_image_ids, 1, 3);
        }

        self::$modal_cabin_ids[$cabin_id] = true;

        ob_start();
        ?>
        <article class="cbe-stay-page-cabin-card" data-cabin-id="<?php echo (int) $cabin_id; ?>" data-cabin-details="<?php echo esc_attr(wp_json_encode($detail_data)); ?>">
            <div class="cbe-stay-page-cabin-media">
                <?php echo $image ? $image : '<div class="cbe-cabin-image cbe-cabin-image-placeholder"></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

                <?php if (!empty($gallery_thumb_ids)) : ?>
                    <div class="cbe-stay-page-cabin-gallery">
                        <?php foreach ($gallery_thumb_ids as $gallery_image_id) :
                            $gallery_thumb_url = wp_get_attachment_image_url($gallery_image_id, 'medium');
                            $gallery_full_url = wp_get_attachment_image_url($gallery_image_id, 'large') ?: $gallery_thumb_url;
                            if (!$gallery_thumb_url) {
                                continue;
                            }
                        ?>
                            <a class="cbe-stay-page-cabin-gallery-thumb" href="<?php echo esc_url($gallery_full_url); ?>" data-full-image="<?php echo esc_attr($gallery_full_url); ?>">
                                <img src="<?php echo esc_url($gallery_thumb_url); ?>" alt="<?php echo esc_attr(get_the_title($cabin_id)); ?>" loading="lazy" />
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cbe-stay-page-cabin-body">
                <div class="cbe-cabin-card-top">
                    <div>
                        <h2 class="cbe-cabin-card-title"><?php echo esc_html(get_the_title($cabin_id)); ?></h2>
                    </div>

                    <div class="cbe-cabin-card-price">
                        <strong><?php echo esc_html(number_format_i18n($price_per_night, 0)); ?></strong>
                        <span><?php esc_html_e('/night', 'cabin-booking-engine'); ?></span>
                    </div>
                </div>

                <div class="cbe-stay-card-meta-row">
                    <?php if ($bed_type !== '') : ?>
                        <span class="cbe-stay-card-meta-pill cbe-stay-card-meta-bed">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4v16"/><path d="M22 4v16"/><path d="M2 12h20"/><path d="M2 8h4a2 2 0 0 1 2 2v2H2V8z"/><path d="M16 8h4a2 2 0 0 1 2 2v2h-8v-2a2 2 0 0 1 2-2z"/></svg>
                            <?php echo esc_html($bed_type); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($max_guests > 0) : ?>
                        <span class="cbe-stay-card-meta-pill">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <?php echo esc_html(sprintf(__('Max %d guests', 'cabin-booking-engine'), $max_guests)); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($total_units > 0) : ?>
                        <span class="cbe-stay-card-meta-pill">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <?php echo esc_html(sprintf(__('%d units available', 'cabin-booking-engine'), $total_units)); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($excerpt !== '') : ?>
                    <p class="cbe-stay-card-excerpt"><?php echo esc_html(wp_trim_words($excerpt, 22, '…')); ?></p>
                <?php endif; ?>

                <?php if (!empty($facilities)) : ?>
                    <ul class="cbe-stay-card-facilities">
                        <?php foreach (array_slice($facilities, 0, 5) as $facility) : ?>
                            <li class="cbe-stay-card-facility-pill">
                                <?php echo $this->render_facility_icon_markup(isset($facility['icon']) ? (string) $facility['icon'] : '', 'cbe-stay-pill-icon'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <span><?php echo esc_html($facility['label']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($facilities) > 5) : ?>
                            <li class="cbe-stay-card-facility-more">+<?php echo (int) (count($facilities) - 5); ?> more</li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>

                <div class="cbe-cabin-card-actions">
                    <button class="cbe-view-details-btn" data-cbe-cabin-id="<?php echo (int) $cabin_id; ?>" type="button"><?php esc_html_e('View Details', 'cabin-booking-engine'); ?></button>
                    <?php echo do_shortcode('[cabin_book_now_button cabin_id="' . (int) $cabin_id . '" label="' . esc_attr__('Book Now', 'cabin-booking-engine') . '"]'); ?>
                </div>
            </div>
        </article>
        <?php

        return ob_get_clean();
    }

    private function resolve_stay_group_from_request() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');
        if ($request_path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $request_path)));
        if (count($segments) < 2) {
            return '';
        }

        $stay_index = array_search('stay', $segments, true);
        if ($stay_index === false || !isset($segments[$stay_index + 1])) {
            return '';
        }

        return sanitize_title($segments[$stay_index + 1]);
    }
}
