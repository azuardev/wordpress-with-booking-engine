<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Admin_Meta_Assets_Trait {
    private function get_material_symbols_stylesheet_url() {
        return 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0';
    }
    private function get_fontawesome_stylesheet_url() {
        return 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
    }
    private function get_bootstrap_icons_stylesheet_url() {
        return 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css';
    }
    private function get_iconify_script_url() {
        return 'https://code.iconify.design/iconify-icon/2.2.1/iconify-icon.min.js';
    }

    public function register_meta_boxes() {
        add_meta_box(
            'cbe_cabin_settings',
            __('Room Booking Settings', 'cabin-booking-engine'),
            array($this, 'render_cabin_settings_box'),
            'cabin',
            'normal',
            'high'
        );
    }

    public function render_cabin_settings_box($post) {
        wp_nonce_field('cbe_save_cabin_meta', 'cbe_cabin_meta_nonce');

        $price_per_night = get_post_meta($post->ID, '_cbe_price_per_night', true);
        $total_units     = get_post_meta($post->ID, '_cbe_total_units', true);
        $bed_type        = get_post_meta($post->ID, '_cbe_bed_type', true);
        $max_guests      = get_post_meta($post->ID, '_cbe_max_guests', true);
        $stay_group      = get_post_meta($post->ID, '_cbe_stay_group', true);
        $facilities      = get_post_meta($post->ID, '_cbe_facilities', true);

        if ($total_units === '') {
            $total_units = '1';
        }
        if ($max_guests === '') {
            $max_guests = '2';
        }

        echo '<div class="cbe-admin-help">';
        echo '<strong>' . esc_html__('Quick setup guide', 'cabin-booking-engine') . ':</strong> ';
        echo esc_html__('Fill the fields below, save the room, then use listing shortcode on your stay page.', 'cabin-booking-engine');
        if (!empty($stay_group)) {
            echo '<br><code>[cabin_listing page="' . esc_html($stay_group) . '"]</code>';
        }
        echo '</div>';

        echo '<table class="form-table cbe-admin-form-table" style="margin:0"><tbody>';

        echo '<tr>';
        echo '<th style="width:200px"><label for="cbe_price_per_night">' . esc_html__('Price per night (IDR)', 'cabin-booking-engine') . '</label></th>';
        echo '<td><input type="number" min="0" step="0.01" class="regular-text" id="cbe_price_per_night" name="cbe_price_per_night" value="' . esc_attr($price_per_night) . '" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="cbe_total_units">' . esc_html__('Available units', 'cabin-booking-engine') . '</label></th>';
        echo '<td><input type="number" min="1" step="1" class="small-text" id="cbe_total_units" name="cbe_total_units" value="' . esc_attr($total_units) . '" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="cbe_bed_type">' . esc_html__('Bed type', 'cabin-booking-engine') . '</label></th>';
        echo '<td><input type="text" class="regular-text" id="cbe_bed_type" name="cbe_bed_type" placeholder="' . esc_attr__('e.g. 1 King Bed or 2 Twin Beds', 'cabin-booking-engine') . '" value="' . esc_attr($bed_type) . '" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="cbe_max_guests">' . esc_html__('Max guests per unit', 'cabin-booking-engine') . '</label></th>';
        echo '<td><input type="number" min="1" step="1" class="small-text" id="cbe_max_guests" name="cbe_max_guests" value="' . esc_attr($max_guests) . '" /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="cbe_stay_group">' . esc_html__('Stay page group slug', 'cabin-booking-engine') . '</label></th>';
        echo '<td><input type="text" class="regular-text" id="cbe_stay_group" name="cbe_stay_group" placeholder="' . esc_attr__('e.g. deluxe', 'cabin-booking-engine') . '" value="' . esc_attr($stay_group) . '" /><p class="description">' . esc_html__('Rooms with the same group slug appear together on /stay/{slug}/ pages.', 'cabin-booking-engine') . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="cbe_facilities">' . esc_html__('Facilities', 'cabin-booking-engine') . '</label></th>';
        echo '<td><textarea class="large-text" rows="4" id="cbe_facilities" name="cbe_facilities" placeholder="' . esc_attr__('e.g. Free WiFi, Smart TV, Bathtub', 'cabin-booking-engine') . '">' . esc_textarea((string) $facilities) . '</textarea><p class="description">' . esc_html__('Use comma or new line between facilities.', 'cabin-booking-engine') . '</p></td>';
        echo '</tr>';

        echo '</tbody></table>';
    }

    public function save_cabin_meta($post_id) {
        if (!isset($_POST['cbe_cabin_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cbe_cabin_meta_nonce'])), 'cbe_save_cabin_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $price = isset($_POST['cbe_price_per_night']) ? (float) sanitize_text_field(wp_unslash($_POST['cbe_price_per_night'])) : 0;
        $units = isset($_POST['cbe_total_units']) ? (int) sanitize_text_field(wp_unslash($_POST['cbe_total_units'])) : 1;

        if ($units < 1) {
            $units = 1;
        }

        $bed_type   = isset($_POST['cbe_bed_type'])   ? sanitize_text_field(wp_unslash($_POST['cbe_bed_type']))   : '';
        $max_guests = isset($_POST['cbe_max_guests']) ? max(1, (int) sanitize_text_field(wp_unslash($_POST['cbe_max_guests']))) : 2;
        $stay_group = isset($_POST['cbe_stay_group']) ? sanitize_title(wp_unslash($_POST['cbe_stay_group'])) : '';
        $facilities = isset($_POST['cbe_facilities']) ? sanitize_textarea_field(wp_unslash($_POST['cbe_facilities'])) : '';

        // Parse plain-text facilities into structured items and sync _cbe_facilities_items
        $facility_items = array();
        if ($facilities !== '') {
            $normalized = str_replace(array("\r\n", "\r"), "\n", $facilities);
            $normalized = str_replace(',', "\n", $normalized);
            $lines = array_filter(array_map('trim', explode("\n", $normalized)));
            foreach (array_values(array_unique($lines)) as $line) {
                $facility_items[] = array(
                    'icon_key' => 'service',
                    'label'    => $line,
                );
            }
        }

        update_post_meta($post_id, '_cbe_price_per_night', $price);
        update_post_meta($post_id, '_cbe_total_units', $units);
        update_post_meta($post_id, '_cbe_bed_type', $bed_type);
        update_post_meta($post_id, '_cbe_max_guests', $max_guests);
        update_post_meta($post_id, '_cbe_stay_group', $stay_group);
        update_post_meta($post_id, '_cbe_facilities', $facilities);
        update_post_meta($post_id, '_cbe_facilities_items', wp_json_encode($facility_items));
    }

    public function register_shortcodes() {
        add_shortcode('cabin_booking_form', array($this, 'render_booking_form_shortcode'));
        add_shortcode('cabin_booking_messages', array($this, 'render_messages_shortcode'));
        add_shortcode('cabin_booking_engine', array($this, 'render_booking_engine_shortcode'));
        add_shortcode('cabin_book_now_button', array($this, 'render_book_now_button_shortcode'));
        add_shortcode('cabin_listing', array($this, 'render_cabin_listing_shortcode'));
        add_shortcode('cabin_booking_search', array($this, 'render_booking_search_shortcode'));
    }

    public function register_rest_routes() {
        register_rest_route(
            'cbe/v1',
            '/doku-notification',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'handle_doku_notification'),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function enqueue_assets() {
        $css_file = CBE_PLUGIN_DIR . 'assets/css/cbe.css';
        $js_file = CBE_PLUGIN_DIR . 'assets/js/cbe.js';

        $css_version = file_exists($css_file) ? (string) filemtime($css_file) : self::VERSION;
        $js_version = file_exists($js_file) ? (string) filemtime($js_file) : self::VERSION;

        wp_register_style(
            'cbe-material-symbols',
            $this->get_material_symbols_stylesheet_url(),
            array(),
            null
        );

        wp_register_style(
            'cbe-fontawesome',
            $this->get_fontawesome_stylesheet_url(),
            array(),
            null
        );

        wp_register_style(
            'cbe-bootstrap-icons',
            $this->get_bootstrap_icons_stylesheet_url(),
            array(),
            null
        );

        wp_register_style(
            'cbe-frontend',
            CBE_PLUGIN_URL . 'assets/css/cbe.css',
            array('cbe-material-symbols', 'cbe-fontawesome', 'cbe-bootstrap-icons'),
            $css_version
        );

        wp_register_script(
            'cbe-frontend',
            CBE_PLUGIN_URL . 'assets/js/cbe.js',
            array(),
            $js_version,
            true
        );

        wp_localize_script(
            'cbe-frontend',
            'cbeConfig',
            array(
                'facilityIconsUrl' => CBE_PLUGIN_URL . 'assets/icons/facilities/',
            )
        );
    }

    public function enqueue_admin_assets($hook) {
        $active_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $allowed_pages = array('cbe-cabins', 'cbe-bookings', 'cbe-facilities', 'cbe-settings', 'cbe-pages');

        if (
            $hook !== 'post.php'
            && $hook !== 'post-new.php'
            && $hook !== 'edit.php'
            && !in_array($active_page, $allowed_pages, true)
        ) {
            return;
        }

        $post_type = '';
        if (isset($_GET['post_type'])) {
            $post_type = sanitize_key(wp_unslash($_GET['post_type']));
        } elseif (isset($_GET['post'])) {
            $post_id = (int) wp_unslash($_GET['post']);
            $post_type = get_post_type($post_id);
        } elseif (isset($_POST['post_type'])) {
            $post_type = sanitize_key(wp_unslash($_POST['post_type']));
        }

        if ($post_type !== 'cabin' && $post_type !== 'cbe_stay_page' && !in_array($active_page, $allowed_pages, true)) {
            return;
        }

        $admin_css_file = CBE_PLUGIN_DIR . 'assets/css/cbe-admin.css';
        $admin_css_version = file_exists($admin_css_file) ? (string) filemtime($admin_css_file) : self::VERSION;

        wp_enqueue_style(
            'cbe-material-symbols',
            $this->get_material_symbols_stylesheet_url(),
            array(),
            null
        );

        wp_enqueue_style(
            'cbe-fontawesome',
            $this->get_fontawesome_stylesheet_url(),
            array(),
            null
        );

        wp_enqueue_style(
            'cbe-bootstrap-icons',
            $this->get_bootstrap_icons_stylesheet_url(),
            array(),
            null
        );

        wp_enqueue_style(
            'cbe-admin',
            CBE_PLUGIN_URL . 'assets/css/cbe-admin.css',
            array('cbe-material-symbols', 'cbe-fontawesome', 'cbe-bootstrap-icons'),
            $admin_css_version
        );

        if ($active_page === 'cbe-cabins' || $active_page === 'cbe-facilities' || $active_page === 'cbe-pages') {
            $admin_js_file = CBE_PLUGIN_DIR . 'assets/js/cbe-admin.js';
            $admin_js_version = file_exists($admin_js_file) ? (string) filemtime($admin_js_file) : self::VERSION;

            wp_enqueue_script(
                'cbe-iconify',
                $this->get_iconify_script_url(),
                array(),
                null,
                true
            );

            wp_enqueue_script(
                'cbe-admin-cabin-manager',
                CBE_PLUGIN_URL . 'assets/js/cbe-admin.js',
                array('jquery', 'cbe-iconify'),
                $admin_js_version,
                true
            );
        }

        if ($active_page === 'cbe-cabins' || $active_page === 'cbe-pages') {
            wp_enqueue_media();
        }
    }

    public function filter_cabin_admin_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['cbe_price'] = __('Price/Night', 'cabin-booking-engine');
                $new_columns['cbe_units'] = __('Units', 'cabin-booking-engine');
                $new_columns['cbe_guests'] = __('Max Guests', 'cabin-booking-engine');
                $new_columns['cbe_group'] = __('Stay Group', 'cabin-booking-engine');
            }
        }

        return $new_columns;
    }

    public function render_cabin_admin_column($column, $post_id) {
        if ($column === 'cbe_price') {
            $price = (float) get_post_meta($post_id, '_cbe_price_per_night', true);
            echo esc_html(number_format_i18n($price, 0));
            return;
        }

        if ($column === 'cbe_units') {
            $units = (int) get_post_meta($post_id, '_cbe_total_units', true);
            echo esc_html($units > 0 ? (string) $units : '1');
            return;
        }

        if ($column === 'cbe_guests') {
            $max_guests = (int) get_post_meta($post_id, '_cbe_max_guests', true);
            echo esc_html($max_guests > 0 ? (string) $max_guests : '2');
            return;
        }

        if ($column === 'cbe_group') {
            $group = (string) get_post_meta($post_id, '_cbe_stay_group', true);
            echo esc_html($group !== '' ? $group : '-');
            return;
        }
    }

    public function register_cabin_sortable_columns($columns) {
        $columns['cbe_price'] = 'cbe_price';
        $columns['cbe_units'] = 'cbe_units';
        $columns['cbe_guests'] = 'cbe_guests';

        return $columns;
    }

    public function handle_cabin_admin_sorting($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'cabin') {
            return;
        }

        $orderby = $query->get('orderby');
        if ($orderby === 'cbe_price') {
            $query->set('meta_key', '_cbe_price_per_night');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'cbe_units') {
            $query->set('meta_key', '_cbe_total_units');
            $query->set('orderby', 'meta_value_num');
        } elseif ($orderby === 'cbe_guests') {
            $query->set('meta_key', '_cbe_max_guests');
            $query->set('orderby', 'meta_value_num');
        }
    }
}
