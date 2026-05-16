Cabin Booking Engine (Custom)

How to use:
1. Activate plugin "Cabin Booking Engine" in WordPress admin.
2. Create cabin data from menu "Cabins".
3. On each cabin edit page, set:
   - Price per night
   - Total units
4. Open Cabins > Settings and configure DOKU if you want online payment:
    - Enable DOKU
    - Sandbox or Production environment
    - Client ID and Shared Key
    - Payment due minutes
5. In your cabin detail page/template, add shortcode:
   [cabin_booking_messages]
   [cabin_booking_form]

Elementor / template integration:
- The plugin auto-injects booking section on single cabin page content.
- This is enabled by default and can be changed in Cabins > Settings.
- If you want manual placement in Elementor, disable auto embed then use shortcode block/widget:
   [cabin_booking_engine]

If using a non-cabin page, pass cabin ID manually:
[cabin_booking_form cabin_id="123" button_text="Book This Cabin"]

Price calculation:
- Total nights and total price are calculated automatically on the form.
- On submit, server recalculates nights and total price to prevent manipulation.

DOKU direct payment integration:
- Booking form now has payment method selector:
   - Pay on Arrival / Manual Confirmation
   - Pay with DOKU
- DOKU flow is handled directly by this plugin without WooCommerce.
- On submit, plugin will:
   - Create booking record
   - Generate DOKU invoice number
   - Request checkout URL from DOKU API
   - Redirect customer to DOKU payment page
   - Receive DOKU notification on REST endpoint and update booking status

DOKU notification endpoint:
- Available in Cabins > Settings
- Default route:
   /wp-json/cbe/v1/doku-notification

Retry payment:
- If a DOKU payment is still pending or failed, the customer-facing booking status card shows a Pay Again button.
- Pay Again generates a fresh DOKU checkout session and appends the event to the booking log.

Admin bookings:
- Open Cabins > Bookings
- Review pending bookings
- Change status to Confirmed or Cancelled
- You can monitor payment method, payment status, and DOKU invoice number.
- Click View to open booking detail with payment summary and DOKU log history.

Notifications:
- Email notification sent to address in Cabins > Settings > Notification Email.
- Optional WhatsApp webhook notification:
   - Fill Cabins > Settings > WhatsApp Webhook URL.
   - Plugin sends JSON payload on each new booking.
   - Optional secret key is sent via X-CBE-Secret header.

Notes:
- Availability check blocks overlapping dates when all units are already booked.
- New bookings with DOKU are saved as pending payment until DOKU notification confirms the payment.
