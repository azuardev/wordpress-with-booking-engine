<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Admin_Pages_Trait {
    public function register_admin_page() {
        add_menu_page(
            __('Hotel Booking Engine', 'cabin-booking-engine'),
            __('Hotel Booking Engine', 'cabin-booking-engine'),
            'manage_options',
            'cbe-cabins',
            array($this, 'render_manage_cabins_page'),
            'dashicons-admin-home',
            28
        );

        add_submenu_page(
            'cbe-cabins',
            __('Manage Rooms', 'cabin-booking-engine'),
            __('Manage Rooms', 'cabin-booking-engine'),
            'manage_options',
            'cbe-cabins',
            array($this, 'render_manage_cabins_page')
        );

        add_submenu_page(
            'cbe-cabins',
            __('Stay Pages', 'cabin-booking-engine'),
            __('Stay Pages', 'cabin-booking-engine'),
            'manage_options',
            'cbe-pages',
            array($this, 'render_manage_stay_pages_page')
        );

        add_submenu_page(
            'cbe-cabins',
            __('Room Bookings', 'cabin-booking-engine'),
            __('Bookings', 'cabin-booking-engine'),
            'manage_options',
            'cbe-bookings',
            array($this, 'render_admin_bookings_page')
        );

        add_submenu_page(
            'cbe-cabins',
            __('Master Facilities', 'cabin-booking-engine'),
            __('Facilities', 'cabin-booking-engine'),
            'manage_options',
            'cbe-facilities',
            array($this, 'render_manage_facilities_page')
        );

        add_submenu_page(
            'cbe-cabins',
            __('Booking Settings', 'cabin-booking-engine'),
            __('Settings', 'cabin-booking-engine'),
            'manage_options',
            'cbe-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_manage_cabins_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_GET['action_type']) ? sanitize_key(wp_unslash($_GET['action_type'])) : '';
        $cabin_id = isset($_GET['cabin_id']) ? (int) wp_unslash($_GET['cabin_id']) : 0;
        $notice = isset($_GET['cbe_msg']) ? sanitize_key(wp_unslash($_GET['cbe_msg'])) : '';

        if ($action === 'new' || ($action === 'edit' && $cabin_id > 0)) {
            $this->render_cabin_custom_form($cabin_id, $notice);
            return;
        }

        $this->render_cabin_custom_list($notice);
    }

    public function render_manage_stay_pages_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_GET['action_type']) ? sanitize_key(wp_unslash($_GET['action_type'])) : '';
        $page_id = isset($_GET['page_id']) ? (int) wp_unslash($_GET['page_id']) : 0;
        $notice = isset($_GET['cbe_msg']) ? sanitize_key(wp_unslash($_GET['cbe_msg'])) : '';

        if ($action === 'new' || ($action === 'edit' && $page_id > 0)) {
            $this->render_stay_page_custom_form($page_id, $notice);
            return;
        }

        $this->render_stay_page_custom_list($notice);
    }

    public function render_manage_facilities_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notice = isset($_GET['cbe_msg']) ? sanitize_key(wp_unslash($_GET['cbe_msg'])) : '';
        $facility_catalog = $this->get_facility_icon_catalog();
        $icon_pool = $this->get_facility_icon_pool();

        echo '<div class="wrap cbe-facility-catalog-page">';
        echo '<h1>' . esc_html__('Master Facilities', 'cabin-booking-engine') . '</h1>';

        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Facilities saved successfully.', 'cabin-booking-engine') . '</p></div>';
        }

        echo '<p class="cbe-admin-help">' . esc_html__('Manage the master list of room facilities here. Room forms will use this list as a checklist, and existing room data will follow the latest icon and label for the same facility key.', 'cabin-booking-engine') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cbe-cabin-manager-form">';
        echo '<input type="hidden" name="action" value="cbe_save_facility_catalog" />';
        wp_nonce_field('cbe_save_facility_catalog', 'cbe_save_facility_catalog_nonce');

        echo '<div class="cbe-cabin-panel cbe-facility-catalog-panel">';
        echo '<div class="cbe-facility-header">';
        echo '<div>';
        echo '<h2>' . esc_html__('Master Data Facilities', 'cabin-booking-engine') . '</h2>';
        echo '<p class="description">' . esc_html__('Kelola data fasilitas ruangan.', 'cabin-booking-engine') . '</p>';
        echo '</div>';
        echo '<button type="button" class="button button-primary" id="cbe-add-facility-catalog-item">+ ' . esc_html__('Add Facility', 'cabin-booking-engine') . '</button>';
        echo '</div>';
        echo '<div id="cbe-facility-catalog-builder" class="cbe-facility-catalog-builder" data-icon-pool="' . esc_attr(wp_json_encode($icon_pool)) . '">';
        echo '<div class="cbe-facility-toolbar">';
        echo '<input type="search" id="cbe-facility-table-search" class="cbe-facility-table-search" placeholder="' . esc_attr__('Search facility...', 'cabin-booking-engine') . '" />';
        echo '</div>';
        echo '<div class="cbe-facility-table-wrap">';
        echo '<table class="cbe-facility-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('No.', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Icon', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Facility Name', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Actions', 'cabin-booking-engine') . '</th>';
        echo '</tr></thead>';
        echo '<tbody id="cbe-facility-catalog-list" class="cbe-facility-catalog-list">';
        foreach ($facility_catalog as $facility_key => $facility_meta) {
            $icon_value = isset($facility_meta['icon']) ? (string) $facility_meta['icon'] : '';

            echo '<tr class="cbe-facility-catalog-row">';
            echo '<td class="cbe-facility-row-no"></td>';
            echo '<td>';
            echo $this->render_facility_icon_markup($icon_value, 'cbe-facility-catalog-icon-preview');
            echo '<input type="hidden" name="cbe_facility_catalog_icons[]" class="cbe-facility-catalog-icon-input" value="' . esc_attr($icon_value) . '" />';
            echo '<div class="cbe-facility-icon-autocomplete">';
            echo '<input type="search" class="cbe-facility-icon-autocomplete-input" placeholder="' . esc_attr__('Search icon (wifi, pool, shower)...', 'cabin-booking-engine') . '" autocomplete="off" />';
            echo '<div class="cbe-facility-icon-autocomplete-results"></div>';
            echo '</div>';
            echo '</td>';
            echo '<td>';
            echo '<input type="hidden" name="cbe_facility_catalog_keys[]" value="' . esc_attr($facility_key) . '" class="cbe-facility-catalog-key-input" />';
            echo '<input type="text" name="cbe_facility_catalog_labels[]" value="' . esc_attr($facility_meta['label']) . '" placeholder="' . esc_attr__('Facility name', 'cabin-booking-engine') . '" class="cbe-facility-catalog-name-input" />';
            echo '</td>';
            echo '<td class="cbe-facility-actions-cell">';
            echo '<button type="button" class="button cbe-edit-facility-catalog-item" aria-label="' . esc_attr__('Edit facility', 'cabin-booking-engine') . '">✎</button> ';
            echo '<button type="button" class="button cbe-remove-facility-catalog-item" aria-label="' . esc_attr__('Delete facility', 'cabin-booking-engine') . '">🗑</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';

        echo '</div>';
        echo '</div>';

        submit_button(__('Save Facilities', 'cabin-booking-engine'));
        echo '</form>';
        echo '</div>';
    }

    private function render_cabin_custom_list($notice = '') {
        $cabins = get_posts(array(
            'post_type' => 'cabin',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Manage Rooms', 'cabin-booking-engine') . '</h1>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=cbe-cabins&action_type=new')) . '" class="page-title-action">' . esc_html__('Add New Room', 'cabin-booking-engine') . '</a>';

        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Room saved successfully.', 'cabin-booking-engine') . '</p></div>';
        } elseif ($notice === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Room moved to trash.', 'cabin-booking-engine') . '</p></div>';
        }

        if (empty($cabins)) {
            echo '<p>' . esc_html__('No rooms found yet.', 'cabin-booking-engine') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped cbe-cabin-manager-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Room', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Price/Night', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Units', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Max Guests', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Stay Group', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Status', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Action', 'cabin-booking-engine') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($cabins as $cabin) {
            $price = (float) get_post_meta($cabin->ID, '_cbe_price_per_night', true);
            $units = (int) get_post_meta($cabin->ID, '_cbe_total_units', true);
            $max_guests = (int) get_post_meta($cabin->ID, '_cbe_max_guests', true);
            $group = (string) get_post_meta($cabin->ID, '_cbe_stay_group', true);

            $edit_url = admin_url('admin.php?page=cbe-cabins&action_type=edit&cabin_id=' . (int) $cabin->ID);
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=cbe_delete_cabin_custom&cabin_id=' . (int) $cabin->ID),
                'cbe_delete_cabin_' . (int) $cabin->ID
            );

            echo '<tr>';
            echo '<td><strong>' . esc_html(get_the_title($cabin->ID)) . '</strong></td>';
            echo '<td>' . esc_html(number_format_i18n($price, 0)) . '</td>';
            echo '<td>' . esc_html($units > 0 ? (string) $units : '1') . '</td>';
            echo '<td>' . esc_html($max_guests > 0 ? (string) $max_guests : '2') . '</td>';
            echo '<td>' . esc_html($group !== '' ? $group : '-') . '</td>';
            echo '<td>' . esc_html($cabin->post_status) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url(get_permalink($cabin->ID)) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Move this room to trash?', 'cabin-booking-engine')) . '\');">' . esc_html__('Trash', 'cabin-booking-engine') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_stay_page_custom_list($notice = '') {
        $pages = get_posts(array(
            'post_type' => 'cbe_stay_page',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Stay Pages', 'cabin-booking-engine') . '</h1>';
        echo ' <a href="' . esc_url(admin_url('admin.php?page=cbe-pages&action_type=new')) . '" class="page-title-action">' . esc_html__('Add New Stay Page', 'cabin-booking-engine') . '</a>';

        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Stay page saved successfully.', 'cabin-booking-engine') . '</p></div>';
        } elseif ($notice === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Stay page moved to trash.', 'cabin-booking-engine') . '</p></div>';
        }

        if (empty($pages)) {
            echo '<p>' . esc_html__('No stay pages found yet.', 'cabin-booking-engine') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped cbe-cabin-manager-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Title', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Slug', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Rooms', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Status', 'cabin-booking-engine') . '</th>';
        echo '<th>' . esc_html__('Action', 'cabin-booking-engine') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($pages as $page) {
            $cabin_ids = $this->get_stay_page_cabin_ids($page->ID);
            $edit_url = admin_url('admin.php?page=cbe-pages&action_type=edit&page_id=' . (int) $page->ID);
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=cbe_delete_stay_page_custom&page_id=' . (int) $page->ID),
                'cbe_delete_stay_page_' . (int) $page->ID
            );

            echo '<tr>';
            echo '<td><strong>' . esc_html(get_the_title($page->ID)) . '</strong></td>';
            echo '<td>' . esc_html($page->post_name !== '' ? $page->post_name : '-') . '</td>';
            echo '<td>' . esc_html((string) count($cabin_ids)) . '</td>';
            echo '<td>' . esc_html($page->post_status) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url(get_permalink($page->ID)) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View', 'cabin-booking-engine') . '</a> <a class="button button-small" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Move this stay page to trash?', 'cabin-booking-engine')) . '\');">' . esc_html__('Trash', 'cabin-booking-engine') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_stay_page_custom_form($page_id = 0, $notice = '') {
        $is_edit = $page_id > 0;
        $post = $is_edit ? get_post($page_id) : null;

        if ($is_edit && (!$post || $post->post_type !== 'cbe_stay_page')) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Stay page not found.', 'cabin-booking-engine') . '</p></div></div>';
            return;
        }

        $title = $is_edit ? $post->post_title : '';
        $slug = $is_edit ? $post->post_name : '';
        $overview = $is_edit ? $post->post_content : '';
        $selected_cabin_ids = $is_edit ? $this->get_stay_page_cabin_ids($page_id) : array();

        $cabins = get_posts(array(
            'post_type' => 'cabin',
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => -1,
            'orderby' => array('menu_order' => 'ASC', 'title' => 'ASC'),
        ));
        $total_rooms = count($cabins);
        $selected_rooms = count($selected_cabin_ids);

        echo '<div class="wrap cbe-cabin-manager cbe-stay-page-manager">';
        echo '<h1>' . esc_html($is_edit ? __('Edit Stay Page', 'cabin-booking-engine') : __('Add New Stay Page', 'cabin-booking-engine')) . '</h1>';
        echo '<p class="cbe-stay-page-subtitle">' . esc_html__('Create a clean stay page with focused content and assigned rooms.', 'cabin-booking-engine') . '</p>';
        echo '<p><a class="cbe-stay-back-link" href="' . esc_url(admin_url('admin.php?page=cbe-pages')) . '">&larr; ' . esc_html__('Back to stay pages list', 'cabin-booking-engine') . '</a></p>';

        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Stay page saved successfully.', 'cabin-booking-engine') . '</p></div>';
        }

        echo '<style id="cbe-stay-page-modern-inline">';
        echo '.cbe-stay-page-manager{max-width:none;width:100%;padding-right:20px;box-sizing:border-box;}';
        echo '.cbe-stay-page-manager > h1{margin-bottom:6px;color:#10243f;font-size:30px;font-weight:700;letter-spacing:-.02em;line-height:1.15;}';
        echo '.cbe-stay-page-manager .cbe-stay-page-subtitle{margin:2px 0 12px;color:#667a94;font-size:13px;line-height:1.65;}';
        echo '.cbe-stay-page-manager .cbe-stay-back-link{display:inline-flex;align-items:center;gap:6px;color:#1f4d92;text-decoration:none;font-weight:600;font-size:12.5px;}';
        echo '.cbe-stay-page-manager .cbe-stay-back-link:hover{color:#163a70;text-decoration:underline;}';
        echo '.cbe-stay-page-manager .notice{margin:14px 0 10px;}';
        echo '.cbe-stay-page-manager .cbe-cabin-manager-form{width:100%;}';
        echo '.cbe-stay-page-manager .cbe-cabin-manager-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:18px;align-items:start;width:100%;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel{background:#ffffff;border:1px solid #dfe6ef;border-radius:16px;padding:22px;box-shadow:0 10px 28px rgba(16,31,55,.06);width:100%;box-sizing:border-box;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel h2{margin:0 0 14px;padding-bottom:12px;border-bottom:1px solid #edf2f7;color:#112744;font-size:18px;font-weight:700;letter-spacing:-.01em;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel p{margin:0 0 14px;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel label strong{display:inline-block;margin-bottom:6px;color:#27405f;font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel input[type=text]{display:block;width:100%;max-width:100%;min-height:44px;border:1px solid #d2dce9;border-radius:11px;padding:10px 12px;background:#fbfdff;color:#142b47;line-height:1.45;transition:border-color .18s ease,box-shadow .18s ease;}';
        echo '.cbe-stay-page-manager .cbe-cabin-panel input[type=text]:focus{border-color:#2d6fd1;box-shadow:0 0 0 3px rgba(45,111,209,.14);background:#fff;outline:none;}';
        echo '.cbe-stay-page-manager .cbe-overview-editor{margin-top:2px;}';
        echo '.cbe-stay-page-manager .cbe-overview-editor .wp-editor-container{border:1px solid #d2dce9;border-radius:0 0 10px 10px;overflow:hidden;}';
        echo '.cbe-stay-page-manager .cbe-overview-editor .mce-tinymce{border-radius:10px;overflow:hidden;border:1px solid #d2dce9;}';
        echo '.cbe-stay-page-manager .cbe-overview-editor .quicktags-toolbar,.cbe-stay-page-manager .cbe-overview-editor .mce-toolbar-grp{background:#f7f9fc;border-bottom:1px solid #e5ebf3;}';
        echo '.cbe-stay-page-manager .cbe-overview-editor textarea{min-height:280px;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-meta{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 12px;}';
        echo '.cbe-stay-page-manager .cbe-meta-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;background:#edf3fb;color:#2b4f7c;font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;}';
        echo '.cbe-stay-page-manager .cbe-meta-pill strong{font-size:12px;color:#163f71;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-list{max-height:540px;overflow:auto;display:grid;gap:10px;padding:10px;border:1px solid #dbe5f2;border-radius:12px;background:#f8fbff;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-item{display:flex;gap:12px;align-items:flex-start;padding:13px;border:1px solid #d6e2f2;border-radius:11px;background:#fff;transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-item:hover{border-color:#b9cfee;box-shadow:0 8px 16px rgba(24,63,118,.11);transform:translateY(-1px);}';
        echo '.cbe-stay-page-manager .cbe-room-picker-item.is-selected{border-color:#7ea8df;background:linear-gradient(180deg,#ffffff 0%,#f1f7ff 100%);box-shadow:0 10px 18px rgba(39,88,160,.14);}';
        echo '.cbe-stay-page-manager .cbe-room-picker-item input[type=checkbox]{margin-top:2px;accent-color:#1f63d2;transform:scale(1.05);}';
        echo '.cbe-stay-page-manager .cbe-room-picker-content{display:grid;gap:4px;color:#223751;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-content strong{font-size:13px;font-weight:700;line-height:1.4;letter-spacing:.2px;}';
        echo '.cbe-stay-page-manager .cbe-room-picker-price{display:inline-flex;width:fit-content;padding:3px 9px;border-radius:999px;background:#e8f0fc;color:#2e547f;font-size:10.5px;font-weight:700;letter-spacing:.28px;text-transform:uppercase;}';
        echo '.cbe-stay-page-manager .cbe-stay-page-actions{margin-top:18px;padding:12px;border:1px solid #dfe8f3;border-radius:13px;background:#fff;display:flex;justify-content:flex-end;}';
        echo '.cbe-stay-page-manager .cbe-stay-page-actions .button.button-primary{min-height:42px;border-radius:10px;padding:0 20px;font-weight:700;letter-spacing:.2px;background:linear-gradient(180deg,#2f7de0 0%,#1d63c6 100%);border-color:#1a5dbb;box-shadow:0 8px 18px rgba(30,98,191,.3);}';
        echo '.cbe-stay-page-manager .cbe-stay-page-actions .button.button-primary:hover{background:linear-gradient(180deg,#2775d6 0%,#1958b4 100%);}';
        echo '@media (max-width:1200px){.cbe-stay-page-manager{padding-right:12px;}.cbe-stay-page-manager > h1{font-size:28px;}}';
        echo '@media (max-width:980px){.cbe-stay-page-manager{padding-right:10px;}.cbe-stay-page-manager > h1{font-size:26px;}.cbe-stay-page-manager .cbe-cabin-manager-grid{grid-template-columns:1fr;gap:14px;}.cbe-stay-page-manager .cbe-cabin-panel{padding:16px 16px 18px;}.cbe-stay-page-manager .cbe-room-picker-list{max-height:440px;}.cbe-stay-page-manager .cbe-overview-editor textarea{min-height:220px;}.cbe-stay-page-manager .cbe-stay-page-actions{justify-content:stretch;}.cbe-stay-page-manager .cbe-stay-page-actions .button.button-primary{width:100%;justify-content:center;}}';
        echo '@media (max-width:640px){.cbe-stay-page-manager{padding-right:6px;}.cbe-stay-page-manager > h1{font-size:23px;line-height:1.2;}.cbe-stay-page-manager .cbe-stay-page-subtitle{font-size:12px;}.cbe-stay-page-manager .cbe-cabin-panel{padding:14px;}.cbe-stay-page-manager .cbe-cabin-panel h2{font-size:16px;}.cbe-stay-page-manager .cbe-cabin-panel label strong{font-size:11px;}.cbe-stay-page-manager .cbe-cabin-panel input[type=text]{min-height:40px;font-size:14px;}.cbe-stay-page-manager .cbe-overview-editor textarea{min-height:190px;}.cbe-stay-page-manager .cbe-room-picker-list{max-height:360px;padding:8px;}.cbe-stay-page-manager .cbe-room-picker-item{padding:11px;}.cbe-stay-page-manager .cbe-room-picker-content strong{font-size:12px;}.cbe-stay-page-manager .cbe-meta-pill{font-size:10px;padding:4px 8px;}}';
        echo '</style>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cbe-cabin-manager-form">';
        echo '<input type="hidden" name="action" value="cbe_save_stay_page_custom" />';
        echo '<input type="hidden" name="page_id" value="' . esc_attr($page_id) . '" />';
        wp_nonce_field('cbe_save_stay_page_custom', 'cbe_save_stay_page_custom_nonce');

        echo '<div class="cbe-cabin-manager-grid">';

        echo '<div class="cbe-cabin-panel">';
        echo '<h2>' . esc_html__('Stay Page Details', 'cabin-booking-engine') . '</h2>';
        echo '<p><label><strong>' . esc_html__('Page Name', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="page_title" class="regular-text" value="' . esc_attr($title) . '" required></p>';
        echo '<p><label><strong>' . esc_html__('Slug', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="page_slug" class="regular-text" value="' . esc_attr($slug) . '" placeholder="deluxe"></p>';
        echo '<p><label><strong>' . esc_html__('Overview (Page Description)', 'cabin-booking-engine') . '</strong></label></p>';
        echo '<div class="cbe-overview-editor">';
        if (function_exists('wp_editor')) {
            wp_editor($overview, 'cbe_stay_page_overview', array(
                'textarea_name' => 'page_overview',
                'textarea_rows' => 12,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            ));
        } else {
            echo '<textarea name="page_overview" rows="12" class="large-text">' . esc_textarea($overview) . '</textarea>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="cbe-cabin-panel">';
        echo '<h2>' . esc_html__('Select Rooms', 'cabin-booking-engine') . '</h2>';
        echo '<p class="cbe-room-picker-meta"><span class="cbe-meta-pill">' . esc_html__('Selected', 'cabin-booking-engine') . ': <strong id="cbe-selected-room-count">' . esc_html((string) $selected_rooms) . '</strong></span><span class="cbe-meta-pill">' . esc_html__('Total Rooms', 'cabin-booking-engine') . ': <strong>' . esc_html((string) $total_rooms) . '</strong></span></p>';

        if (empty($cabins)) {
            echo '<p>' . esc_html__('Create at least one room first.', 'cabin-booking-engine') . '</p>';
        } else {
            echo '<div class="cbe-room-picker-list">';
            foreach ($cabins as $cabin) {
                $price = (float) get_post_meta($cabin->ID, '_cbe_price_per_night', true);
                $checked = in_array((int) $cabin->ID, $selected_cabin_ids, true);
                echo '<label class="cbe-room-picker-item' . ($checked ? ' is-selected' : '') . '">';
                echo '<input class="cbe-room-checkbox" type="checkbox" name="cabin_ids[]" value="' . esc_attr((string) $cabin->ID) . '" ' . checked($checked, true, false) . ' />';
                echo '<span class="cbe-room-picker-content"><strong>' . esc_html(get_the_title($cabin->ID)) . '</strong>';
                echo '<span class="cbe-room-picker-price">' . esc_html(number_format_i18n($price, 0)) . ' / night</span></span>';
                echo '</label>';
            }
            echo '</div>';
        }

        echo '</div>';

        echo '</div>';

        echo '<div class="cbe-stay-page-actions">';
        submit_button(
            $is_edit ? __('Update Stay Page', 'cabin-booking-engine') : __('Create Stay Page', 'cabin-booking-engine'),
            'primary button-hero',
            'submit',
            false
        );
        echo '</div>';
        echo '<script id="cbe-stay-page-room-counter">(function(){var root=document.querySelector(".cbe-stay-page-manager");if(!root){return;}var items=root.querySelectorAll(".cbe-room-picker-item");var checks=root.querySelectorAll(".cbe-room-checkbox");var counter=root.querySelector("#cbe-selected-room-count");var sync=function(){var selected=0;checks.forEach(function(check){if(check.checked){selected+=1;}var item=check.closest(".cbe-room-picker-item");if(item){item.classList.toggle("is-selected",check.checked);}});if(counter){counter.textContent=String(selected);}};checks.forEach(function(check){check.addEventListener("change",sync);});sync();})();</script>';
        echo '</form>';
        echo '</div>';
    }

    private function render_cabin_custom_form($cabin_id = 0, $notice = '') {
        $is_edit = $cabin_id > 0;
        $post = $is_edit ? get_post($cabin_id) : null;

        if ($is_edit && (!$post || $post->post_type !== 'cabin')) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__('Room not found.', 'cabin-booking-engine') . '</p></div></div>';
            return;
        }

        $title = $is_edit ? $post->post_title : '';
        $slug = $is_edit ? $post->post_name : '';
        $description = $is_edit ? $post->post_content : '';
        $excerpt = $is_edit ? $post->post_excerpt : '';
        $status = $is_edit ? $post->post_status : 'publish';
        $price = $is_edit ? (float) get_post_meta($cabin_id, '_cbe_price_per_night', true) : 0;
        $units = $is_edit ? (int) get_post_meta($cabin_id, '_cbe_total_units', true) : 1;
        $bed_type = $is_edit ? (string) get_post_meta($cabin_id, '_cbe_bed_type', true) : '';
        $max_guests = $is_edit ? (int) get_post_meta($cabin_id, '_cbe_max_guests', true) : 2;
        $stay_group = $is_edit ? (string) get_post_meta($cabin_id, '_cbe_stay_group', true) : '';
        $facility_items = $is_edit ? $this->get_cabin_facilities($cabin_id) : array();
        $facility_icon_catalog = $this->get_facility_icon_catalog();
        $selected_facility_keys = array();
        foreach ($facility_items as $facility_item) {
            if (!is_array($facility_item) || empty($facility_item['icon_key'])) {
                continue;
            }

            $selected_facility_keys[] = sanitize_key((string) $facility_item['icon_key']);
        }
        $selected_facility_keys = array_values(array_unique($selected_facility_keys));
        $gallery = $is_edit ? (string) get_post_meta($cabin_id, '_cbe_gallery_ids', true) : '';
        $featured_image_id = $is_edit ? (int) get_post_thumbnail_id($cabin_id) : 0;

        $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'medium') : '';

        echo '<div class="wrap cbe-cabin-manager">';
        echo '<h1>' . esc_html($is_edit ? __('Edit Room', 'cabin-booking-engine') : __('Add New Room', 'cabin-booking-engine')) . '</h1>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=cbe-cabins')) . '">&larr; ' . esc_html__('Back to room list', 'cabin-booking-engine') . '</a></p>';

        if ($notice === 'saved') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Room saved successfully.', 'cabin-booking-engine') . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="cbe-cabin-manager-form">';
        echo '<input type="hidden" name="action" value="cbe_save_cabin_custom" />';
        echo '<input type="hidden" name="cabin_id" value="' . esc_attr($cabin_id) . '" />';
        wp_nonce_field('cbe_save_cabin_custom', 'cbe_save_cabin_custom_nonce');

        echo '<div class="cbe-cabin-manager-grid">';

        echo '<div class="cbe-cabin-panel">';
        echo '<h2>' . esc_html__('Room Content', 'cabin-booking-engine') . '</h2>';
        echo '<p><label><strong>' . esc_html__('Room Name', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="cabin_title" class="regular-text" value="' . esc_attr($title) . '" required></p>';
        echo '<p><label><strong>' . esc_html__('Slug', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="cabin_slug" class="regular-text" value="' . esc_attr($slug) . '" placeholder="deluxe-plus"></p>';
        echo '<p><label><strong>' . esc_html__('Short Description', 'cabin-booking-engine') . '</strong></label><br/><textarea name="cabin_excerpt" rows="3" class="large-text">' . esc_textarea($excerpt) . '</textarea></p>';
        echo '<p><label><strong>' . esc_html__('Full Description', 'cabin-booking-engine') . '</strong></label><br/><textarea name="cabin_description" rows="8" class="large-text">' . esc_textarea($description) . '</textarea></p>';
        echo '</div>';

        echo '<div class="cbe-cabin-panel">';
        echo '<h2>' . esc_html__('Booking Configuration', 'cabin-booking-engine') . '</h2>';
        echo '<p><label><strong>' . esc_html__('Price per night (IDR)', 'cabin-booking-engine') . '</strong></label><br/><input type="number" min="0" step="0.01" name="cbe_price_per_night" value="' . esc_attr($price) . '" required></p>';
        echo '<p><label><strong>' . esc_html__('Available units', 'cabin-booking-engine') . '</strong></label><br/><input type="number" min="1" step="1" name="cbe_total_units" value="' . esc_attr($units > 0 ? $units : 1) . '" required></p>';
        echo '<p><label><strong>' . esc_html__('Bed type', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="cbe_bed_type" class="regular-text" value="' . esc_attr($bed_type) . '" placeholder="King Bed"></p>';
        echo '<p><label><strong>' . esc_html__('Max guests per unit', 'cabin-booking-engine') . '</strong></label><br/><input type="number" min="1" step="1" name="cbe_max_guests" value="' . esc_attr($max_guests > 0 ? $max_guests : 2) . '" required></p>';
        echo '<p><label><strong>' . esc_html__('Stay page group slug', 'cabin-booking-engine') . '</strong></label><br/><input type="text" name="cbe_stay_group" class="regular-text" value="' . esc_attr($stay_group) . '" placeholder="deluxe"></p>';
        echo '<p><label><strong>' . esc_html__('Status', 'cabin-booking-engine') . '</strong></label><br/>';
        echo '<select name="cabin_status">';
        echo '<option value="publish" ' . selected($status, 'publish', false) . '>' . esc_html__('Published', 'cabin-booking-engine') . '</option>';
        echo '<option value="draft" ' . selected($status, 'draft', false) . '>' . esc_html__('Draft', 'cabin-booking-engine') . '</option>';
        echo '</select></p>';
        echo '</div>';

        echo '<div class="cbe-cabin-panel">';
        echo '<h2>' . esc_html__('Room Photos', 'cabin-booking-engine') . '</h2>';
        echo '<p><strong>' . esc_html__('Featured Photo', 'cabin-booking-engine') . '</strong></p>';
        echo '<input type="hidden" name="cbe_featured_image_id" id="cbe_featured_image_id" value="' . esc_attr($featured_image_id) . '">';
        echo '<div id="cbe-featured-preview" class="cbe-image-preview">';
        if ($featured_image_url) {
            echo '<img src="' . esc_url($featured_image_url) . '" alt="" />';
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="cbe-select-featured">' . esc_html__('Select Featured Photo', 'cabin-booking-engine') . '</button> <button type="button" class="button" id="cbe-remove-featured">' . esc_html__('Remove', 'cabin-booking-engine') . '</button></p>';

        echo '<hr/>';

        echo '<p><strong>' . esc_html__('Gallery Photos', 'cabin-booking-engine') . '</strong></p>';
        echo '<input type="hidden" name="cbe_gallery_ids" id="cbe_gallery_ids" value="' . esc_attr($gallery) . '">';
        echo '<div id="cbe-gallery-preview" class="cbe-gallery-preview" data-gallery-ids="' . esc_attr($gallery) . '"></div>';
        echo '<p><button type="button" class="button" id="cbe-select-gallery">' . esc_html__('Add Gallery Photos', 'cabin-booking-engine') . '</button> <button type="button" class="button" id="cbe-clear-gallery">' . esc_html__('Clear Gallery', 'cabin-booking-engine') . '</button></p>';
        echo '</div>';

        echo '<div class="cbe-cabin-panel cbe-cabin-panel-facilities">';
        echo '<h2>' . esc_html__('Facilities', 'cabin-booking-engine') . '</h2>';
        echo '<div id="cbe-facilities-builder" class="cbe-facilities-builder" data-icon-options="' . esc_attr(wp_json_encode($facility_icon_catalog)) . '">';
        echo '<input type="hidden" name="cbe_facility_keys_present" value="1" />';
        echo '<div id="cbe-facilities-list" class="cbe-facilities-list cbe-facilities-checklist cbe-facilities-checklist-wide">';
        foreach ($facility_icon_catalog as $icon_key => $icon_meta) {
            $is_checked = in_array($icon_key, $selected_facility_keys, true);
            echo '<label class="cbe-facility-check">';
            echo '<input type="checkbox" name="cbe_facility_keys[]" value="' . esc_attr($icon_key) . '" class="cbe-facility-checkbox" ' . checked($is_checked, true, false) . ' />';
            echo $this->render_facility_icon_markup(isset($icon_meta['icon']) ? (string) $icon_meta['icon'] : '', 'cbe-facility-check-icon');
            echo '<span class="cbe-facility-check-label">' . esc_html($icon_meta['label']) . '</span>';
            echo '</label>';
        }
        echo '</div>';
        echo '<input type="hidden" id="cbe_facilities_items" name="cbe_facilities_items" value="' . esc_attr(wp_json_encode($facility_items)) . '" />';
        echo '<p class="description">' . esc_html__('Choose which facilities are available in this room.', 'cabin-booking-engine') . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>';

        submit_button($is_edit ? __('Update Room', 'cabin-booking-engine') : __('Create Room', 'cabin-booking-engine'));
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_cabin_custom() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        if (!isset($_POST['cbe_save_cabin_custom_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cbe_save_cabin_custom_nonce'])), 'cbe_save_cabin_custom')) {
            wp_die(esc_html__('Invalid request', 'cabin-booking-engine'));
        }

        $cabin_id = isset($_POST['cabin_id']) ? (int) wp_unslash($_POST['cabin_id']) : 0;
        $title = isset($_POST['cabin_title']) ? sanitize_text_field(wp_unslash($_POST['cabin_title'])) : '';
        $slug = isset($_POST['cabin_slug']) ? sanitize_title(wp_unslash($_POST['cabin_slug'])) : '';
        $excerpt = isset($_POST['cabin_excerpt']) ? sanitize_textarea_field(wp_unslash($_POST['cabin_excerpt'])) : '';
        $description = isset($_POST['cabin_description']) ? wp_kses_post(wp_unslash($_POST['cabin_description'])) : '';
        $status = isset($_POST['cabin_status']) ? sanitize_key(wp_unslash($_POST['cabin_status'])) : 'publish';

        if ($title === '') {
            wp_die(esc_html__('Room name is required.', 'cabin-booking-engine'));
        }

        if (!in_array($status, array('publish', 'draft'), true)) {
            $status = 'publish';
        }

        $post_data = array(
            'post_type' => 'cabin',
            'post_title' => $title,
            'post_name' => $slug,
            'post_excerpt' => $excerpt,
            'post_content' => $description,
            'post_status' => $status,
        );

        if ($cabin_id > 0) {
            $post_data['ID'] = $cabin_id;
            $saved_id = wp_update_post($post_data, true);
        } else {
            $saved_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($saved_id) || !$saved_id) {
            wp_die(esc_html__('Failed to save room.', 'cabin-booking-engine'));
        }

        $saved_id = (int) $saved_id;

        $price = isset($_POST['cbe_price_per_night']) ? (float) sanitize_text_field(wp_unslash($_POST['cbe_price_per_night'])) : 0;
        $units = isset($_POST['cbe_total_units']) ? max(1, (int) sanitize_text_field(wp_unslash($_POST['cbe_total_units']))) : 1;
        $bed_type = isset($_POST['cbe_bed_type']) ? sanitize_text_field(wp_unslash($_POST['cbe_bed_type'])) : '';
        $max_guests = isset($_POST['cbe_max_guests']) ? max(1, (int) sanitize_text_field(wp_unslash($_POST['cbe_max_guests']))) : 2;
        $stay_group = isset($_POST['cbe_stay_group']) ? sanitize_title(wp_unslash($_POST['cbe_stay_group'])) : '';
        $facility_catalog = $this->get_facility_icon_catalog();
        $has_facility_key_input = isset($_POST['cbe_facility_keys_present']);
        $selected_facility_keys = $has_facility_key_input ? (array) wp_unslash($_POST['cbe_facility_keys']) : array();
        $raw_items = array();
        foreach ($selected_facility_keys as $selected_facility_key) {
            $facility_key = sanitize_key((string) $selected_facility_key);
            if ($facility_key === '' || !isset($facility_catalog[$facility_key])) {
                continue;
            }

            $raw_items[] = array(
                'icon_key' => $facility_key,
                'label' => $facility_catalog[$facility_key]['label'],
            );
        }
        // Fallback to JSON hidden input when checkbox data is unavailable.
        if (!$has_facility_key_input) {
            $facility_items_raw     = isset($_POST['cbe_facilities_items']) ? wp_unslash($_POST['cbe_facilities_items']) : '[]';
            $facility_items_decoded = json_decode((string) $facility_items_raw, true);
            $raw_items              = is_array($facility_items_decoded) ? $facility_items_decoded : array();
        }
        $facility_items = $this->normalize_facility_items($raw_items);
        $facility_labels = array();
        foreach ($facility_items as $facility_item) {
            $facility_labels[] = $facility_item['label'];
        }
        $facilities = implode("\n", $facility_labels);

        update_post_meta($saved_id, '_cbe_price_per_night', $price);
        update_post_meta($saved_id, '_cbe_total_units', $units);
        update_post_meta($saved_id, '_cbe_bed_type', $bed_type);
        update_post_meta($saved_id, '_cbe_max_guests', $max_guests);
        update_post_meta($saved_id, '_cbe_stay_group', $stay_group);
        update_post_meta($saved_id, '_cbe_facilities_items', wp_json_encode($facility_items));
        update_post_meta($saved_id, '_cbe_facilities', $facilities);

        $featured_image_id = isset($_POST['cbe_featured_image_id']) ? (int) wp_unslash($_POST['cbe_featured_image_id']) : 0;
        if ($featured_image_id > 0) {
            set_post_thumbnail($saved_id, $featured_image_id);
        } else {
            delete_post_thumbnail($saved_id);
        }

        $gallery_ids_raw = isset($_POST['cbe_gallery_ids']) ? (string) wp_unslash($_POST['cbe_gallery_ids']) : '';
        $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_ids_raw)));
        update_post_meta($saved_id, '_cbe_gallery_ids', implode(',', $gallery_ids));

        wp_safe_redirect(admin_url('admin.php?page=cbe-cabins&action_type=edit&cabin_id=' . $saved_id . '&cbe_msg=saved'));
        exit;
    }

    public function handle_save_facility_catalog() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        if (!isset($_POST['cbe_save_facility_catalog_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cbe_save_facility_catalog_nonce'])), 'cbe_save_facility_catalog')) {
            wp_die(esc_html__('Invalid request', 'cabin-booking-engine'));
        }

        $keys_raw = isset($_POST['cbe_facility_catalog_keys']) ? (array) wp_unslash($_POST['cbe_facility_catalog_keys']) : array();
        $icons_raw = isset($_POST['cbe_facility_catalog_icons']) ? (array) wp_unslash($_POST['cbe_facility_catalog_icons']) : array();
        $labels_raw = isset($_POST['cbe_facility_catalog_labels']) ? (array) wp_unslash($_POST['cbe_facility_catalog_labels']) : array();

        $catalog_items = array();

        foreach ($labels_raw as $index => $label_raw) {
            $catalog_items[] = array(
                'key' => isset($keys_raw[$index]) ? (string) $keys_raw[$index] : '',
                'icon' => isset($icons_raw[$index]) ? (string) $icons_raw[$index] : '',
                'label' => (string) $label_raw,
            );
        }

        $catalog = $this->sanitize_facility_catalog($catalog_items);
        cbe_update_option('facility_catalog', $catalog);

        wp_safe_redirect(admin_url('admin.php?page=cbe-facilities&cbe_msg=saved'));
        exit;
    }

    public function handle_save_stay_page_custom() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        if (!isset($_POST['cbe_save_stay_page_custom_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cbe_save_stay_page_custom_nonce'])), 'cbe_save_stay_page_custom')) {
            wp_die(esc_html__('Invalid request', 'cabin-booking-engine'));
        }

        $page_id = isset($_POST['page_id']) ? (int) wp_unslash($_POST['page_id']) : 0;
        $title = isset($_POST['page_title']) ? sanitize_text_field(wp_unslash($_POST['page_title'])) : '';
        $slug = isset($_POST['page_slug']) ? sanitize_title(wp_unslash($_POST['page_slug'])) : '';
        $overview = isset($_POST['page_overview']) ? wp_kses_post(wp_unslash($_POST['page_overview'])) : '';
        $cabin_ids = isset($_POST['cabin_ids']) ? array_map('intval', (array) wp_unslash($_POST['cabin_ids'])) : array();
        $cabin_ids = array_values(array_filter(array_unique($cabin_ids)));

        if ($title === '') {
            wp_die(esc_html__('Stay page title is required.', 'cabin-booking-engine'));
        }

        $status = 'publish';
        if ($page_id > 0) {
            $existing_page = get_post($page_id);
            if ($existing_page && in_array($existing_page->post_status, array('publish', 'draft', 'pending', 'private'), true)) {
                $status = $existing_page->post_status;
            }
        }

        $post_data = array(
            'post_type' => 'cbe_stay_page',
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $overview,
            'post_status' => $status,
        );

        if ($page_id > 0) {
            $post_data['ID'] = $page_id;
            $saved_id = wp_update_post($post_data, true);
        } else {
            $saved_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($saved_id) || !$saved_id) {
            wp_die(esc_html__('Failed to save stay page.', 'cabin-booking-engine'));
        }

        $saved_id = (int) $saved_id;
        update_post_meta($saved_id, '_cbe_page_cabin_ids', implode(',', $cabin_ids));

        wp_safe_redirect(admin_url('admin.php?page=cbe-pages&action_type=edit&page_id=' . $saved_id . '&cbe_msg=saved'));
        exit;
    }

    public function handle_delete_stay_page_custom() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        $page_id = isset($_GET['page_id']) ? (int) wp_unslash($_GET['page_id']) : 0;
        if ($page_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=cbe-pages'));
            exit;
        }

        check_admin_referer('cbe_delete_stay_page_' . $page_id);
        wp_trash_post($page_id);

        wp_safe_redirect(admin_url('admin.php?page=cbe-pages&cbe_msg=deleted'));
        exit;
    }

    public function handle_delete_cabin_custom() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized action', 'cabin-booking-engine'));
        }

        $cabin_id = isset($_GET['cabin_id']) ? (int) wp_unslash($_GET['cabin_id']) : 0;
        if ($cabin_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=cbe-cabins'));
            exit;
        }

        check_admin_referer('cbe_delete_cabin_' . $cabin_id);
        wp_trash_post($cabin_id);

        wp_safe_redirect(admin_url('admin.php?page=cbe-cabins&cbe_msg=deleted'));
        exit;
    }

}

