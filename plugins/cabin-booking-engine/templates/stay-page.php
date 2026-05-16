<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
echo Cabin_Booking_Engine::instance()->render_stay_page_body();
?>
<?php
get_footer();
