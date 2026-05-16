<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Frontend_Pages_Trait {

    public function append_booking_section_to_single_cabin($content) {
        static $injected = false;
        if (!is_singular('cabin') || is_admin() || $this->is_elementor_editor_request()) {
            return $content;
        }

        if ($injected) {
            return $content;
        }

        $settings = $this->get_settings();
        if (empty($settings['auto_embed_single_cabin'])) {
            return $content;
        }

        if (strpos($content, '[cabin_booking_form') !== false || strpos($content, 'cbe-booking-form') !== false) {
            return $content;
        }

        $cabin_id = $this->resolve_cabin_id_for_current_view();
        if ($cabin_id <= 0) {
            return $content;
        }

        $injected = true;
        return $content . do_shortcode('[cabin_booking_messages][cabin_booking_form cabin_id="' . (int) $cabin_id . '"]');
    }

    public function render_booking_engine_shortcode($atts) {
        return $this->render_messages_shortcode() . $this->render_booking_form_shortcode($atts);
    }

    public function render_cabin_listing_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'group' => '',
                'page'  => '',
                'title' => '',
            ),
            $atts,
            'cabin_listing'
        );

        $group = sanitize_title($atts['group']);
        $page_slug = sanitize_title($atts['page']);

        if ($group === '' && $page_slug !== '') {
            $group = $page_slug;
        }

        if ($group === '') {
            $group = $this->resolve_stay_group_for_current_view();
        }

        $page_context = $this->get_current_page_context();
        if ($group === '' && !empty($page_context['slug'])) {
            $group = sanitize_title($page_context['slug']);
        }

        if ($group === '' && !empty($page_context['title'])) {
            $group = sanitize_title($page_context['title']);
        }

        if ($group === '') {
            return '<div class="cbe-message cbe-error">' . esc_html__('Room listing requires a page or group slug.', 'cabin-booking-engine') . '</div>';
        }

        $cabins = $this->get_cabins_by_group($group, false);
        if (empty($cabins)) {
            return '<div class="cbe-message cbe-error">' . esc_html(sprintf(__('No rooms were found for the "%s" stay page yet.', 'cabin-booking-engine'), $group)) . '</div>';
        }

        wp_enqueue_style('cbe-frontend');
        wp_enqueue_script('cbe-frontend');

        $title = $atts['title'] !== '' ? $atts['title'] : (!empty($page_context['title']) ? $page_context['title'] : ucfirst(str_replace('-', ' ', $group)));

        ob_start();
        ?>
        <section class="cbe-cabin-listing" data-cbe-group="<?php echo esc_attr($group); ?>">
            <header class="cbe-cabin-listing-header">
                <h2><?php echo esc_html($title); ?></h2>
            </header>

            <div class="cbe-cabin-grid">
                <?php foreach ($cabins as $cabin) :
                    $cabin_id = (int) $cabin->ID;
                    $price_per_night = $this->get_cabin_price_per_night($cabin_id);
                    $bed_type = get_post_meta($cabin_id, '_cbe_bed_type', true);
                    $max_guests = (int) get_post_meta($cabin_id, '_cbe_max_guests', true);
                    $total_units = (int) get_post_meta($cabin_id, '_cbe_total_units', true);
                    $stay_group = (string) get_post_meta($cabin_id, '_cbe_stay_group', true);
                    $facilities = $this->get_cabin_facilities($cabin_id);
                    $excerpt = get_the_excerpt($cabin_id);
                    $image = get_the_post_thumbnail($cabin_id, 'large', array('class' => 'cbe-cabin-image'));
                    $detail_content = trim((string) get_post_field('post_content', $cabin_id));
                    $featured_image_id = (int) get_post_thumbnail_id($cabin_id);
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

                    $has_detail_specs = ($price_per_night > 0 || $bed_type !== '' || $max_guests > 0 || $total_units > 0 || $stay_group !== '' || !empty($facilities));
                    $has_detail_panel = ($detail_content !== '' || !empty($gallery_image_ids) || $has_detail_specs);

                    self::$modal_cabin_ids[$cabin_id] = true;
                    ?>
                    <article class="cbe-cabin-card">
                        <div class="cbe-cabin-card-media">
                            <?php echo $image ? $image : '<div class="cbe-cabin-image cbe-cabin-image-placeholder"></div>'; ?>
                        </div>

                        <div class="cbe-cabin-card-body">
                            <div class="cbe-cabin-card-top">
                                <div>
                                    <h3 class="cbe-cabin-card-title"><?php echo esc_html(get_the_title($cabin_id)); ?></h3>
                                    <?php if ($bed_type !== '') : ?>
                                        <p class="cbe-cabin-card-subtitle"><?php echo esc_html($bed_type); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="cbe-cabin-card-price">
                                    <strong><?php echo esc_html(number_format_i18n($price_per_night, 0)); ?></strong>
                                    <span><?php esc_html_e('/night', 'cabin-booking-engine'); ?></span>
                                </div>
                            </div>

                            <ul class="cbe-cabin-card-meta">
                                <?php if ($max_guests > 0) : ?>
                                    <li><?php echo esc_html(sprintf(__('Max %d guests', 'cabin-booking-engine'), $max_guests)); ?></li>
                                <?php endif; ?>
                                <?php if ($total_units > 0) : ?>
                                    <li><?php echo esc_html(sprintf(__('%d units available', 'cabin-booking-engine'), $total_units)); ?></li>
                                <?php endif; ?>
                            </ul>

                            <?php if ($excerpt !== '') : ?>
                                <div class="cbe-cabin-card-excerpt">
                                    <?php echo wp_kses_post(wpautop($excerpt)); ?>
                                </div>
                            <?php endif; ?>

                            <div class="cbe-cabin-card-actions">
                                <?php echo do_shortcode('[cabin_book_now_button cabin_id="' . $cabin_id . '" label="' . esc_attr__('Book Now', 'cabin-booking-engine') . '"]'); ?>
                                <?php if ($has_detail_panel) : ?>
                                    <button
                                        type="button"
                                        class="cbe-detail-btn"
                                        data-cbe-detail-id="<?php echo esc_attr($cabin_id); ?>"
                                        data-cbe-detail-title="<?php echo esc_attr(get_the_title($cabin_id)); ?>"
                                        aria-expanded="false"
                                    >
                                        <?php esc_html_e('View Details', 'cabin-booking-engine'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if ($has_detail_panel) : ?>
                                <div class="cbe-cabin-detail" id="cbe-cabin-detail-<?php echo esc_attr($cabin_id); ?>" hidden>
                                    <?php if ($has_detail_specs) : ?>
                                        <div class="cbe-cabin-detail-specs">
                                            <?php if ($price_per_night > 0) : ?>
                                                <div class="cbe-cabin-detail-spec-item">
                                                    <span class="cbe-cabin-detail-spec-label"><?php esc_html_e('Price per night', 'cabin-booking-engine'); ?></span>
                                                    <strong class="cbe-cabin-detail-spec-value"><?php echo esc_html(number_format_i18n($price_per_night, 0)); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($bed_type !== '') : ?>
                                                <div class="cbe-cabin-detail-spec-item">
                                                    <span class="cbe-cabin-detail-spec-label"><?php esc_html_e('Bed type', 'cabin-booking-engine'); ?></span>
                                                    <strong class="cbe-cabin-detail-spec-value"><?php echo esc_html($bed_type); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($max_guests > 0) : ?>
                                                <div class="cbe-cabin-detail-spec-item">
                                                    <span class="cbe-cabin-detail-spec-label"><?php esc_html_e('Max guests', 'cabin-booking-engine'); ?></span>
                                                    <strong class="cbe-cabin-detail-spec-value"><?php echo esc_html((string) $max_guests); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($total_units > 0) : ?>
                                                <div class="cbe-cabin-detail-spec-item">
                                                    <span class="cbe-cabin-detail-spec-label"><?php esc_html_e('Available units', 'cabin-booking-engine'); ?></span>
                                                    <strong class="cbe-cabin-detail-spec-value"><?php echo esc_html((string) $total_units); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($stay_group !== '') : ?>
                                                <div class="cbe-cabin-detail-spec-item">
                                                    <span class="cbe-cabin-detail-spec-label"><?php esc_html_e('Stay group', 'cabin-booking-engine'); ?></span>
                                                    <strong class="cbe-cabin-detail-spec-value"><?php echo esc_html($stay_group); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($facilities)) : ?>
                                        <div class="cbe-cabin-detail-facilities">
                                            <strong class="cbe-cabin-detail-facilities-title"><?php esc_html_e('Facilities', 'cabin-booking-engine'); ?></strong>
                                            <ul class="cbe-cabin-detail-facilities-list">
                                                <?php foreach ($facilities as $facility) : ?>
                                                    <li>
                                                        <?php echo $this->render_facility_icon_markup(isset($facility['icon']) ? (string) $facility['icon'] : '', 'cbe-facility-icon'); ?>
                                                        <span class="cbe-facility-label"><?php echo esc_html(isset($facility['label']) ? $facility['label'] : ''); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($excerpt !== '') : ?>
                                        <div class="cbe-cabin-detail-text">
                                            <?php echo wp_kses_post(wpautop($excerpt)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($detail_content !== '') : ?>
                                        <div class="cbe-cabin-detail-text">
                                            <?php echo wp_kses_post(wpautop($detail_content)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($gallery_image_ids)) : ?>
                                        <div class="cbe-cabin-detail-gallery">
                                            <?php foreach ($gallery_image_ids as $gallery_image_id) :
                                                $gallery_image_thumb_url = wp_get_attachment_image_url($gallery_image_id, 'medium');
                                                $gallery_image_full_url = wp_get_attachment_image_url($gallery_image_id, 'large');
                                                if (!$gallery_image_thumb_url) {
                                                    continue;
                                                }
                                                if (!$gallery_image_full_url) {
                                                    $gallery_image_full_url = $gallery_image_thumb_url;
                                                }
                                                ?>
                                                <a class="cbe-cabin-detail-thumb" href="<?php echo esc_url($gallery_image_full_url); ?>" data-full-image="<?php echo esc_url($gallery_image_full_url); ?>">
                                                    <img src="<?php echo esc_url($gallery_image_thumb_url); ?>" alt="" loading="lazy" />
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function append_cabin_listing_to_stay_page($content) {
        static $listing_injected = false;

        if (!is_singular('page') || is_admin()) {
            return $content;
        }

        if ($listing_injected) {
            return $content;
        }

        $stay_group = $this->resolve_stay_group_for_current_view();
        if ($stay_group === '') {
            return $content;
        }

        if (strpos($content, '[cabin_listing') !== false || strpos($content, 'cbe-cabin-listing') !== false) {
            return $content;
        }

        $listing_injected = true;
        return $content . do_shortcode('[cabin_listing group="' . esc_attr($stay_group) . '"]');
    }

    public function render_book_now_button_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'cabin_id' => 0,
                'label'    => __('Book Now', 'cabin-booking-engine'),
                'class'    => '',
            ),
            $atts,
            'cabin_book_now_button'
        );

        $cabin_id = (int) $atts['cabin_id'];
        if ($cabin_id <= 0 || get_post_type($cabin_id) !== 'cabin') {
            return '';
        }

        self::$modal_cabin_ids[$cabin_id] = true;

        wp_enqueue_style('cbe-frontend');
        wp_enqueue_script('cbe-frontend');

        $extra_class = $atts['class'] !== '' ? ' ' . sanitize_html_class($atts['class']) : '';

        return '<button type="button" class="cbe-book-now-btn' . esc_attr($extra_class) . '" data-cbe-cabin-id="' . esc_attr($cabin_id) . '">'
            . esc_html($atts['label'])
            . '</button>';
    }

    public function render_modal_footer() {
        if (empty(self::$modal_cabin_ids) || $this->is_elementor_editor_request()) {
            return;
        }

        wp_enqueue_style('cbe-frontend');
        wp_enqueue_script('cbe-frontend');

        $redirect_url = get_permalink(get_queried_object_id()) ?: home_url('/');

        echo '<div id="cbe-modal-overlay" class="cbe-modal-overlay" role="dialog" aria-modal="true" aria-label="' . esc_attr__('Booking Form', 'cabin-booking-engine') . '" hidden>';
        echo '<div class="cbe-modal-wrap">';
        echo '<button type="button" class="cbe-modal-close" aria-label="' . esc_attr__('Close', 'cabin-booking-engine') . '">&times;</button>';

        foreach (self::$modal_cabin_ids as $cabin_id => $_discard) {
            $cabin_title = get_the_title((int) $cabin_id);
            $bed_type    = get_post_meta((int) $cabin_id, '_cbe_bed_type', true);
            $max_guests  = (int) get_post_meta((int) $cabin_id, '_cbe_max_guests', true);

            echo '<div class="cbe-modal-panel" data-modal-cabin-id="' . esc_attr($cabin_id) . '" hidden>';
            echo '<div class="cbe-modal-cabin-info">';
            echo '<h2 class="cbe-modal-title">' . esc_html($cabin_title) . '</h2>';
            if ($bed_type !== '') {
                echo '<p class="cbe-modal-meta">&#128715; ' . esc_html($bed_type);
                if ($max_guests > 0) {
                    echo ' &nbsp;&middot;&nbsp; Max ' . esc_html($max_guests) . ' ' . esc_html__('guests', 'cabin-booking-engine');
                }
                echo '</p>';
            }
            echo '</div>';
            echo do_shortcode('[cabin_booking_messages][cabin_booking_form cabin_id="' . (int) $cabin_id . '" redirect_url="' . esc_url($redirect_url) . '"]');
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        echo '<div id="cbe-detail-overlay" class="cbe-detail-overlay" role="dialog" aria-modal="true" aria-label="' . esc_attr__('Informasi Kamar', 'cabin-booking-engine') . '" hidden>';
        echo '<div class="cbe-detail-wrap">';
        echo '<div class="cbe-detail-header-bar">';
        echo '<button type="button" class="cbe-detail-close" aria-label="' . esc_attr__('Tutup', 'cabin-booking-engine') . '">&times;</button>';
        echo '<h2 class="cbe-detail-title" id="cbe-detail-title">' . esc_html__('Informasi Kamar', 'cabin-booking-engine') . '</h2>';
        echo '</div>';
        echo '<div class="cbe-detail-body" id="cbe-detail-body"></div>';
        echo '</div>';
        echo '</div>';

        echo '<div id="cbe-image-viewer-overlay" class="cbe-image-viewer-overlay" role="dialog" aria-modal="true" aria-label="' . esc_attr__('Room Image', 'cabin-booking-engine') . '" hidden>';
        echo '<div class="cbe-image-viewer-wrap">';
        echo '<button type="button" class="cbe-image-viewer-close" aria-label="' . esc_attr__('Close image', 'cabin-booking-engine') . '">&times;</button>';
        echo '<button type="button" class="cbe-image-viewer-nav cbe-image-viewer-prev" aria-label="' . esc_attr__('Previous image', 'cabin-booking-engine') . '">&#10094;</button>';
        echo '<img id="cbe-image-viewer-image" class="cbe-image-viewer-image" src="" alt="" />';
        echo '<div id="cbe-image-viewer-counter" class="cbe-image-viewer-counter" aria-live="polite"></div>';
        echo '<button type="button" class="cbe-image-viewer-nav cbe-image-viewer-next" aria-label="' . esc_attr__('Next image', 'cabin-booking-engine') . '">&#10095;</button>';
        echo '</div>';
        echo '</div>';
    }

    public function render_booking_form_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'cabin_id'     => 0,
                'button_text'  => __('Book Now', 'cabin-booking-engine'),
                'redirect_url' => '',
            ),
            $atts,
            'cabin_booking_form'
        );

        $cabin_id = (int) $atts['cabin_id'];
        if ($cabin_id <= 0) {
            $cabin_id = $this->resolve_cabin_id_for_current_view();
        }

        if ($cabin_id <= 0 || get_post_type($cabin_id) !== 'cabin') {
            return '<div class="cbe-message cbe-error">' . esc_html__('Room is not valid.', 'cabin-booking-engine') . '</div>';
        }

        $price_per_night = $this->get_cabin_price_per_night($cabin_id);
        $payment_methods = $this->get_available_payment_methods();

        wp_enqueue_style('cbe-frontend');
        wp_enqueue_script('cbe-frontend');

        ob_start();
        ?>
        <form class="cbe-booking-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-price-per-night="<?php echo esc_attr($price_per_night); ?>">
            <input type="hidden" name="action" value="cbe_submit_booking" />
            <input type="hidden" name="cabin_id" value="<?php echo esc_attr($cabin_id); ?>" />
            <input type="hidden" name="redirect_url" value="<?php echo esc_url($atts['redirect_url'] ?: get_permalink(get_queried_object_id() ?: $cabin_id)); ?>" />
            <input type="hidden" name="price_per_night" value="<?php echo esc_attr($price_per_night); ?>" />
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>

            <div class="cbe-row">
                <label for="cbe_checkin_date"><?php esc_html_e('Check-in Date', 'cabin-booking-engine'); ?></label>
                <input type="date" id="cbe_checkin_date" name="checkin_date" required />
            </div>

            <div class="cbe-row">
                <label for="cbe_checkout_date"><?php esc_html_e('Check-out Date', 'cabin-booking-engine'); ?></label>
                <input type="date" id="cbe_checkout_date" name="checkout_date" required />
            </div>

            <div class="cbe-row">
                <label for="cbe_guest_name"><?php esc_html_e('Full Name', 'cabin-booking-engine'); ?></label>
                <input type="text" id="cbe_guest_name" name="guest_name" required />
            </div>

            <div class="cbe-row">
                <label for="cbe_guest_email"><?php esc_html_e('Email', 'cabin-booking-engine'); ?></label>
                <input type="email" id="cbe_guest_email" name="guest_email" required />
            </div>

            <div class="cbe-row">
                <label for="cbe_guest_phone"><?php esc_html_e('Phone Number', 'cabin-booking-engine'); ?></label>
                <input type="text" id="cbe_guest_phone" name="guest_phone" />
            </div>

            <div class="cbe-row">
                <label for="cbe_total_guests"><?php esc_html_e('Total Guests', 'cabin-booking-engine'); ?></label>
                <input type="number" id="cbe_total_guests" name="total_guests" min="1" step="1" value="1" required />
            </div>

            <div class="cbe-row">
                <label for="cbe_notes"><?php esc_html_e('Notes', 'cabin-booking-engine'); ?></label>
                <textarea id="cbe_notes" name="notes" rows="4"></textarea>
            </div>

            <div class="cbe-row">
                <label for="cbe_payment_method"><?php esc_html_e('Payment Method', 'cabin-booking-engine'); ?></label>
                <select id="cbe_payment_method" name="payment_method" required>
                    <?php foreach ($payment_methods as $payment_key => $payment_label) : ?>
                        <option value="<?php echo esc_attr($payment_key); ?>"><?php echo esc_html($payment_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cbe-price-box" aria-live="polite">
                <div class="cbe-price-line">
                    <span><?php esc_html_e('Price per night', 'cabin-booking-engine'); ?></span>
                    <strong class="cbe-price-per-night"><?php echo esc_html(number_format_i18n($price_per_night, 2)); ?></strong>
                </div>
                <div class="cbe-price-line">
                    <span><?php esc_html_e('Nights', 'cabin-booking-engine'); ?></span>
                    <strong class="cbe-total-nights">0</strong>
                </div>
                <div class="cbe-price-line cbe-price-total">
                    <span><?php esc_html_e('Total price', 'cabin-booking-engine'); ?></span>
                    <strong class="cbe-total-price"><?php echo esc_html(number_format_i18n(0, 2)); ?></strong>
                </div>
            </div>

            <div class="cbe-row">
                <button type="submit"><?php echo esc_html($atts['button_text']); ?></button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    private function resolve_cabin_id_for_current_view() {
        $queried = get_queried_object();
        if ($queried instanceof WP_Post && $queried->post_type === 'cabin') {
            return (int) $queried->ID;
        }

        if (!($queried instanceof WP_Post)) {
            return 0;
        }

        $post_slug = sanitize_title($queried->post_name);
        if ($post_slug !== '') {
            $matched_cabin = get_page_by_path($post_slug, OBJECT, 'cabin');
            if ($matched_cabin instanceof WP_Post) {
                return (int) $matched_cabin->ID;
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');
        if ($request_path === '') {
            return 0;
        }

        $segments = array_values(array_filter(explode('/', $request_path)));
        if (empty($segments)) {
            return 0;
        }

        $stay_index = array_search('stay', $segments, true);
        if ($stay_index === false || !isset($segments[$stay_index + 1])) {
            return 0;
        }

        $stay_slug = sanitize_title($segments[$stay_index + 1]);
        if ($stay_slug === '') {
            return 0;
        }

        $matched_cabin = get_page_by_path($stay_slug, OBJECT, 'cabin');
        if ($matched_cabin instanceof WP_Post) {
            return (int) $matched_cabin->ID;
        }

        return 0;
    }

    private function resolve_stay_group_for_current_view() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');
        if ($request_path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $request_path)));
        if (empty($segments)) {
            return '';
        }

        $stay_index = array_search('stay', $segments, true);
        if ($stay_index === false || !isset($segments[$stay_index + 1])) {
            return '';
        }

        return sanitize_title($segments[$stay_index + 1]);
    }

    private function get_cabins_by_group($group, $fallback_by_text_match = false) {
        $allowed_status = $this->is_elementor_editor_request()
            ? array('publish', 'draft', 'pending', 'private')
            : array('publish');

        if ($group === 'all') {
            $query = new WP_Query(array(
                'post_type' => 'cabin',
                'post_status' => $allowed_status,
                'posts_per_page' => -1,
                'orderby' => array(
                    'menu_order' => 'ASC',
                    'title' => 'ASC',
                ),
            ));

            return $query->posts;
        }

        $query = new WP_Query(array(
            'post_type' => 'cabin',
            'post_status' => $allowed_status,
            'posts_per_page' => -1,
            'orderby' => array(
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ),
            'meta_query' => array(
                array(
                    'key' => '_cbe_stay_group',
                    'value' => $group,
                    'compare' => '=',
                ),
            ),
        ));

        if (!empty($query->posts) || !$fallback_by_text_match) {
            return $query->posts;
        }

        $fallback_query = new WP_Query(array(
            'post_type' => 'cabin',
            'post_status' => $allowed_status,
            'posts_per_page' => -1,
            'orderby' => array(
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ),
        ));

        $matched = array();
        foreach ($fallback_query->posts as $cabin) {
            $needle = sanitize_title($group);
            $title = sanitize_title(get_the_title($cabin->ID));
            $slug = sanitize_title(get_post_field('post_name', $cabin->ID));

            if ($needle !== '' && (strpos($title, $needle) !== false || strpos($slug, $needle) !== false)) {
                $matched[] = $cabin;
            }
        }

        return $matched;
    }

    private function get_current_page_context() {
        $queried = get_queried_object();
        if (!($queried instanceof WP_Post)) {
            return array('slug' => '', 'title' => '');
        }

        return array(
            'slug' => (string) $queried->post_name,
            'title' => (string) get_the_title($queried->ID),
        );
    }

    private function is_elementor_editor_request() {
        if (!did_action('elementor/loaded')) {
            return false;
        }

        if (class_exists('Elementor\Plugin') && isset(
            \Elementor\Plugin::$instance,
            \Elementor\Plugin::$instance->editor
        ) && method_exists(\Elementor\Plugin::$instance->editor, 'is_edit_mode') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return true;
        }

        if (class_exists('Elementor\Plugin') && isset(
            \Elementor\Plugin::$instance,
            \Elementor\Plugin::$instance->preview
        ) && method_exists(\Elementor\Plugin::$instance->preview, 'is_preview_mode') && \Elementor\Plugin::$instance->preview->is_preview_mode()) {
            return true;
        }

        return false;
    }

}