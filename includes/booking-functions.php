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

    $vehicles = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}vehicles ORDER BY name");
    $routes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}routes ORDER BY from_location, to_location");

    include plugin_dir_path(__FILE__) . '../templates/search-form.php';

    if (
        isset($_GET['vehicle_id'], $_GET['from'], $_GET['to'], $_GET['start_time']) &&
        $_GET['vehicle_id'] && $_GET['from'] && $_GET['to'] && $_GET['start_time']
    ) {
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

            $price = $wpdb->get_var($wpdb->prepare(
                "SELECT price FROM {$wpdb->prefix}vehicle_routes 
                 WHERE vehicle_id = %d AND route_id = %d",
                $vehicle->id,
                $route_id
            ));
            if ($price === null)
                continue;

            echo '<div class="border p-4 bg-white rounded shadow-sm">';
            echo '<h3 class="text-lg font-semibold mb-1">🚗 ' . esc_html($vehicle->name) . '</h3>';
            echo '<p>📍  เส้นทาง: ' . esc_html($from . ' → ' . $to) . '</p>';
            echo '<p>💰  ราคา: ' . number_format($price, 2) . ' บาท</p>';

            if ($already_booked > 0) {
                echo '<p class="mt-2 text-red-600">❌ รถคันนี้ถูกจองแล้ว</p>';
            } else {
                $link = add_query_arg([
                    'vehicle_id' => $vehicle->id,
                    'route_id' => $route_id,
                    'start_time' => $_GET['start_time'],
                    'car_booking_form' => 1
                ], site_url('/กรอกข้อมูล/'));

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
        isset($_POST['vehicle_id'], $_POST['route_id'], $_POST['start_time'], $_POST['full_name']) &&
        is_user_logged_in()
    ) {
        $user = wp_get_current_user();
        $email = $user->user_email;

        $start = date('Y-m-d H:i:s', strtotime($_POST['start_time']));
        $end = date('Y-m-d H:i:s', strtotime($_POST['start_time'] . ' +6 hours'));

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings
             WHERE vehicle_id = %d AND route_id = %d
             AND ((start_time <= %s AND end_time >= %s)
             OR (start_time <= %s AND end_time >= %s))
             AND status = 'confirmed'",
            $_POST['vehicle_id'],
            $_POST['route_id'],
            $start,
            $start,
            $end,
            $end
        ));

        if ($existing > 0) {
            return '<div class="text-center text-red-600 p-6">รถคันนี้ถูกจองไปแล้วในช่วงเวลาดังกล่าว</div>';
        }

        $wpdb->insert($bookings, [
            'vehicle_id' => intval($_POST['vehicle_id']),
            'route_id' => intval($_POST['route_id']),
            'start_time' => $start,
            'end_time' => $end,
            'user_email' => sanitize_email($email),
            'status' => 'confirmed',
            'full_name' => sanitize_text_field($_POST['full_name']),
            'country' => sanitize_text_field($_POST['country']),
            'phone' => sanitize_text_field($_POST['phone']),
            'whatsapp' => sanitize_text_field($_POST['whatsapp']),
            'wechat' => sanitize_text_field($_POST['wechat']),
            'line' => sanitize_text_field($_POST['line'])
        ]);

        cbp_send_booking_email($email, $start, $end);

        return '<div class="box-complete">
                    ✅ การจองของคุณสำเร็จแล้ว!<br>
                    📅 วันที่เดินทาง: ' . esc_html(date('d/m/Y H:i', strtotime($start))) . '<br>
                    📧 อีเมลยืนยันส่งไปที่: ' . esc_html($email) . '<br>
                    🚗 หมายเลขรถ: ' . intval($_POST['vehicle_id']) . '<br><br>
                    🔄 กำลังนำคุณกลับไปยังหน้าหลัก...
                </div>
                <style>
                    .box-complete{
                        display: flex;
                        justify-content: center;
                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                        max-width: 600px;
                        margin: auto;
                        margin-top: 50px;
                        padding: 40px 20px;
                        border-radius: 10px;
                    }
                </style>
                <script>
                    setTimeout(function() {
                        window.location.href = "' . site_url('/carbooking/') . '";
                    }, 5000); // รอ 4 วินาทีแล้วค่อยเปลี่ยนหน้า
                </script>';
    }

    return '<div class="text-center p-6 text-red-600">กรุณาเข้าสู่ระบบเพื่อทำการจอง</div>';
}

add_shortcode('car_booking_form', 'cbp_render_booking_form');
function cbp_render_booking_form()
{
    if (!isset($_GET['car_booking_form']))
        return;
    if (!is_user_logged_in())
        return '<div class="text-red-600 p-4">กรุณาเข้าสู่ระบบก่อนทำการจอง</div>';

    $vehicle_id = intval($_GET['vehicle_id']);
    $route_id = intval($_GET['route_id']);
    $start_time = sanitize_text_field($_GET['start_time']);

    ob_start();
    echo '<div class="bg-white p-6 rounded shadow-md max-w-xl mx-auto">';
    echo '<h2 class="text-xl font-bold mb-4">📄 กรอกข้อมูลผู้จอง</h2>';
    echo '<form method="post">';
    echo '<input type="hidden" name="car_booking_confirm" value="1">';
    echo '<input type="hidden" name="vehicle_id" value="' . $vehicle_id . '">';
    echo '<input type="hidden" name="route_id" value="' . $route_id . '">';
    echo '<input type="hidden" name="start_time" value="' . esc_attr($start_time) . '">';
    echo '<label>ชื่อ - สกุล</label><input name="full_name" class="w-full border p-2 mb-3 rounded" required>';
    echo '<label>ประเทศ</label><input name="country" class="w-full border p-2 mb-3 rounded">';
    echo '<label>เบอร์โทรติดต่อ</label><input name="phone" class="w-full border p-2 mb-3 rounded">';
    echo '<label>WhatsApp</label><input name="whatsapp" class="w-full border p-2 mb-3 rounded">';
    echo '<label>WeChat</label><input name="wechat" class="w-full border p-2 mb-3 rounded">';
    echo '<label>Line</label><input name="line" class="w-full border p-2 mb-3 rounded">';
    echo '<button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full">✅ ยืนยันการจอง</button>';
    echo '</form></div>';
    return ob_get_clean();
}