<?php
function cbp_render_booking_history() {
    if (!is_user_logged_in()) {
        return '<p class="text-red-500">กรุณาเข้าสู่ระบบเพื่อดูประวัติการจอง</p>';
    }

    global $wpdb;
    $user = wp_get_current_user();
    $bookings = $wpdb->prefix . 'bookings';
    $vehicles = $wpdb->prefix . 'vehicles';
    $routes = $wpdb->prefix . 'routes';

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, v.name AS vehicle_name, r.from_location, r.to_location
         FROM $bookings b
         LEFT JOIN $vehicles v ON b.vehicle_id = v.id
         LEFT JOIN $routes r ON b.route_id = r.id
         WHERE b.user_email = %s
         ORDER BY b.start_time DESC",
        $user->user_email
    ));

    ob_start();
    echo '<div class="max-w-3xl mx-auto p-4">
        <h2 class="text-2xl font-bold mb-4">ประวัติการจองของคุณ</h2>';

    if (empty($results)) {
        echo '<p class="text-gray-500">คุณยังไม่มีประวัติการจอง</p>';
    } else {
        echo '<table class="w-full text-left border">
        <thead><tr class="bg-gray-100">
            <th class="p-2">รถ</th>
            <th class="p-2">ต้นทาง</th>
            <th class="p-2">ปลายทาง</th>
            <th class="p-2">เริ่ม</th>
            <th class="p-2">สิ้นสุด</th>
            <th class="p-2">สถานะ</th>
        </tr></thead><tbody>';
        foreach ($results as $row) {
            echo '<tr class="border-b">
                <td class="p-2">' . esc_html($row->vehicle_name) . '</td>
                <td class="p-2">' . esc_html($row->from_location) . '</td>
                <td class="p-2">' . esc_html($row->to_location) . '</td>
                <td class="p-2">' . esc_html($row->start_time) . '</td>
                <td class="p-2">' . esc_html($row->end_time) . '</td>
                <td class="p-2">' . esc_html($row->status) . '</td>
            </tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
    return ob_get_clean();
}
