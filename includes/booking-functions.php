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

        if ($already_booked > 0) {
            echo '<p class="text-red-600 mt-4">❌ รถคันนี้ถูกจองในช่วงเวลาดังกล่าว</p>';
        } else {
            // แสดงรายละเอียดรถและลิงก์จอง
            $vehicle = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vehicles WHERE id = %d", $vehicle_id));
            $link = add_query_arg([
                'vehicle_id' => $vehicle_id,
                'route_id' => $route_id,
                'start_time' => $_GET['start_time'],
            ], site_url('/?car_booking_confirm=1'));

            echo '<div class="mt-6 border p-4 rounded shadow bg-white">';
            echo '🚗 <strong>' . esc_html($vehicle->name) . '</strong><br>';
            echo '📍 เส้นทาง: ' . esc_html($from) . ' → ' . esc_html($to) . '<br>';
            echo '<a href="' . esc_url($link) . '" class="inline-block mt-2 text-blue-600 hover:underline">จองรถคันนี้</a>';
            echo '</div>';
        }
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

        return '<div class="text-center p-6 text-green-600"><h2 class="text-xl font-bold">จองรถเรียบร้อยแล้ว! กรุณาตรวจสอบอีเมลของคุณ</h2></div>';
    }

    return '<div class="text-center p-6 text-red-600">กรุณาเข้าสู่ระบบเพื่อทำการจอง</div>';
}
