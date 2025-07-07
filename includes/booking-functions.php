<?php
function get_available_vehicles($from, $to, $desired_time)
{
    global $wpdb;
    $vehicles_table = $wpdb->prefix . 'vehicles';
    $routes_table = $wpdb->prefix . 'routes';
    $bookings_table = $wpdb->prefix . 'bookings';

    $route_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $routes_table WHERE from_location = %s AND to_location = %s",
        $from,
        $to
    ));

    $start = date('Y-m-d H:i:s', strtotime($desired_time));
    $end = date('Y-m-d H:i:s', strtotime($desired_time . ' +6 hours'));

    $booked_vehicle_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT vehicle_id FROM $bookings_table 
         WHERE route_id = %d AND 
         ((start_time <= %s AND end_time >= %s) 
         OR (start_time <= %s AND end_time >= %s)) 
         AND status = 'confirmed'",
        $route_id,
        $start,
        $start,
        $end,
        $end
    ));

    $ids_str = implode(',', array_map('intval', $booked_vehicle_ids));
    $available_vehicles = $wpdb->get_results(
        "SELECT * FROM $vehicles_table WHERE id NOT IN ($ids_str)"
    );

    return $available_vehicles;
}

function cbp_render_search_form()
{
    ob_start();
    global $wpdb;

    // ดึงรายการรถและเส้นทางทั้งหมด
    $vehicles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vehicles ORDER BY name");
    $routes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}routes ORDER BY from_location, to_location");

    include plugin_dir_path(__FILE__) . '../templates/search-form.php';
    if (
        isset($_GET['vehicle_id'], $_GET['from'], $_GET['to'], $_GET['start_time']) &&
        $_GET['vehicle_id'] && $_GET['from'] && $_GET['to'] && $_GET['start_time']
    ) {
        global $wpdb;
        $vehicle_id = intval($_GET['vehicle_id']);
        $from = sanitize_text_field($_GET['from']);
        $to = sanitize_text_field($_GET['to']);
        $start = $_GET['start_time'];
        $end = date('Y-m-d H:i:s', strtotime($start . ' +6 hours'));

        $route_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}routes WHERE from_location = %s AND to_location = %s",
            $from,
            $to
        ));

        if (!$route_id) {
            echo '<p class="text-red-500 mt-4">❌ ไม่พบเส้นทางนี้ในระบบ</p>';
            return;
        }

        $vehicle_routes_table = $wpdb->prefix . 'vehicle_routes';
        $vehicles_table = $wpdb->prefix . 'vehicles';

        echo '<div class="grid md:grid-cols-2 gap-6 mt-6">';

        foreach ($vehicles as $vehicle) {
            $already_booked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bookings
             WHERE vehicle_id = %d AND route_id = %d
             AND ((start_time <= %s AND end_time >= %s)
             OR (start_time <= %s AND end_time >= %s))
             AND status = 'confirmed'",
                $vehicle->id,
                $route_id,
                $start,
                $start,
                $end,
                $end
            ));

            // 🔎 ดึงราคา
            $price = $wpdb->get_var($wpdb->prepare(
                "SELECT price FROM {$wpdb->prefix}vehicle_routes 
         WHERE vehicle_id = %d AND route_id = %d",
                $vehicle->id,
                $route_id
            ));
            if ($price === null) {
                continue; // ❌ ข้ามคันนี้ถ้าไม่มีราคาใน vehicle_routes
            }

            $already_booked = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bookings
         WHERE vehicle_id = %d AND route_id = %d
         AND ((start_time <= %s AND end_time >= %s) OR (start_time <= %s AND end_time >= %s))
         AND status = 'confirmed'",
                $vehicle_id,
                $route_id,
                $start,
                $start,
                $end,
                $end
            ));
            echo '<div class="border p-4 bg-white rounded shadow-sm">';
            echo '<h3 class="text-lg font-semibold mb-1">🚗 ' . esc_html($vehicle->name) . '</h3>';
            echo '<p>📍 เส้นทาง: ' . esc_html($from . ' → ' . $to) . '</p>';
            echo '<p>💰 ราคา: ' . ($price !== null ? number_format($price, 2) . ' บาท' : '<span class="text-gray-400">ไม่ระบุ</span>') . '</p>';

            if ($already_booked > 0) {
                echo '<p class="mt-2 text-red-600">❌ รถคันนี้ถูกจองแล้ว</p>';
            } else {
                $link = add_query_arg([
                    'vehicle_id' => $vehicle->id,
                    'route_id' => $route_id,
                    'start_time' => $_GET['start_time'],
                ], site_url('/?car_booking_confirm=1'));

                echo '<a href="' . esc_url($link) . '" class="mt-2 inline-block text-blue-600 hover:underline">✅ จองรถคันนี้</a>';
            }

            echo '</div>';
        }

        echo '</div>';
    }
    return ob_get_clean();
}

function cbp_render_booking_confirm()
{
    global $wpdb;
    $bookings = $wpdb->prefix . 'bookings';

    if (
        isset($_GET['vehicle_id'], $_GET['route_id'], $_GET['start_time']) &&
        is_user_logged_in()
    ) {
        $user = wp_get_current_user();
        $email = $user->user_email;

        $start = date('Y-m-d H:i:s', strtotime($_GET['start_time']));
        $end = date('Y-m-d H:i:s', strtotime($_GET['start_time'] . ' +6 hours'));

        // ตรวจสอบการจองซ้ำ (ไม่ให้จองซ้อนกัน)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings
             WHERE vehicle_id = %d AND route_id = %d
             AND ((start_time <= %s AND end_time >= %s)
             OR (start_time <= %s AND end_time >= %s))
             AND status = 'confirmed'",
            $_GET['vehicle_id'],
            $_GET['route_id'],
            $start,
            $start,
            $end,
            $end
        ));

        if ($existing > 0) {
            return '<div class="text-center text-red-600 p-6">รถคันนี้ถูกจองไปแล้วในช่วงเวลาดังกล่าว</div>';
        }

        // บันทึกการจอง
        $wpdb->insert($bookings, [
            'vehicle_id' => intval($_GET['vehicle_id']),
            'route_id' => intval($_GET['route_id']),
            'start_time' => $start,
            'end_time' => $end,
            'user_email' => sanitize_email($email),
            'status' => 'confirmed'
        ]);

        // ส่งอีเมลแจ้งเตือน
        cbp_send_booking_email($email, $start, $end);

        return '
        <div class="p-6 text-center text-green-700 bg-green-50 rounded-lg shadow-md">
            ✅ การจองของคุณสำเร็จแล้ว!<br>
            📅 วันที่เดินทาง: ' . esc_html(date('d/m/Y H:i', strtotime($start))) . '<br>
            📧 อีเมลยืนยันส่งไปที่: ' . esc_html($email) . '<br>
            🚗 หมายเลขรถ: ' . intval($_GET['vehicle_id']) . '
        </div>';
    }

    return '<div class="text-center p-6 text-red-600">กรุณาเข้าสู่ระบบเพื่อทำการจอง</div>';
}