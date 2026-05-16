<?php

if (!defined('ABSPATH')) {
    exit;
}

trait CBE_Booking_Messages_Trait {
    public function render_messages_shortcode() {
        $status = isset($_GET['cbe_status']) ? sanitize_key(wp_unslash($_GET['cbe_status'])) : '';
        $booking_id = isset($_GET['cbe_booking']) ? (int) wp_unslash($_GET['cbe_booking']) : 0;

        if ($booking_id > 0) {
            $booking = $this->get_booking($booking_id);
            if ($booking) {
                wp_enqueue_style('cbe-frontend');
                return $this->render_booking_status_panel($booking, $status);
            }
        }

        if ($status === '') {
            return '';
        }

        $map = array(
            'success' => array(
                'class' => 'cbe-success',
                'message' => __('Booking request submitted successfully. Our team will contact you shortly.', 'cabin-booking-engine'),
            ),
            'invalid_dates' => array(
                'class' => 'cbe-error',
                'message' => __('Invalid check-in/check-out dates.', 'cabin-booking-engine'),
            ),
            'not_available' => array(
                'class' => 'cbe-error',
                'message' => __('Sorry, this room is not available for selected dates.', 'cabin-booking-engine'),
            ),
            'invalid_cabin' => array(
                'class' => 'cbe-error',
                'message' => __('Room data is invalid.', 'cabin-booking-engine'),
            ),
            'failed' => array(
                'class' => 'cbe-error',
                'message' => __('Booking failed to save. Please try again.', 'cabin-booking-engine'),
            ),
            'doku_unavailable' => array(
                'class' => 'cbe-error',
                'message' => __('DOKU payment is currently unavailable. Please check booking settings.', 'cabin-booking-engine'),
            ),
            'doku_failed' => array(
                'class' => 'cbe-error',
                'message' => __('Failed to start DOKU payment. Please try again.', 'cabin-booking-engine'),
            ),
            'pending_payment' => array(
                'class' => 'cbe-success',
                'message' => __('Booking created. Please complete your DOKU payment to confirm this booking.', 'cabin-booking-engine'),
            ),
            'payment_success' => array(
                'class' => 'cbe-success',
                'message' => __('DOKU payment was received and your booking is now confirmed.', 'cabin-booking-engine'),
            ),
            'payment_failed' => array(
                'class' => 'cbe-error',
                'message' => __('DOKU payment failed or expired. Please create a new booking or contact admin.', 'cabin-booking-engine'),
            ),
        );

        if (!isset($map[$status])) {
            return '';
        }

        wp_enqueue_style('cbe-frontend');

        return '<div class="cbe-message ' . esc_attr($map[$status]['class']) . '">' . esc_html($map[$status]['message']) . '</div>';
    }
}
