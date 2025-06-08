<?php
function cbp_admin_menu()
{
    add_menu_page('Car Booking', 'Car Booking', 'manage_options', 'cbp-admin', 'cbp_admin_page');
}
add_action('admin_menu', 'cbp_admin_menu');

function cbp_admin_page()
{
    global $wpdb;
    $vehicles = $wpdb->prefix . 'vehicles';
    $routes = $wpdb->prefix . 'routes';

    // เพิ่มรถ
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_vehicle') {
        $wpdb->insert($vehicles, [
            'name' => sanitize_text_field($_POST['vehicle_name']),
            'detail' => sanitize_text_field($_POST['vehicle_detail'])
        ]);
        echo '<div class="notice notice-success"><p>เพิ่มรถเรียบร้อยแล้ว</p></div>';
    }

    // เพิ่มเส้นทาง
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_route') {
        $wpdb->insert($routes, [
            'from_location' => sanitize_text_field($_POST['from_location']),
            'to_location' => sanitize_text_field($_POST['to_location'])
        ]);
        echo '<div class="notice notice-success"><p>เพิ่มเส้นทางเรียบร้อยแล้ว</p></div>';
    }

    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_vehicle_route') {
        $vehicle_id = intval($_POST['vehicle_id']);
        $route_id = intval($_POST['route_id']);
        $price = floatval($_POST['price']);

        $table = $wpdb->prefix . 'vehicle_routes';

        // ตรวจสอบก่อนว่า combination นี้มีอยู่แล้วหรือไม่
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE vehicle_id = %d AND route_id = %d",
            $vehicle_id,
            $route_id
        ));

        if ($exists) {
            echo '<div class="notice notice-error"><p>❌ คู่นี้มีอยู่แล้วในระบบ</p></div>';
        } else {
            $wpdb->insert($table, [
                'vehicle_id' => $vehicle_id,
                'route_id' => $route_id,
                'price' => $price,
            ]);
            echo '<div class="notice notice-success"><p>✅ เพิ่มราคารถสำเร็จ</p></div>';
        }
    }


    echo '<div class="wrap">';
    echo '<h1 class="text-2xl font-bold mb-4">จัดการระบบจองรถ</h1>';
    echo '<div class="w-full flex flex-col md:flex-row gap-5 pt-5 justify-items-stretch">';

    // ฟอร์มเพิ่มรถ
    echo '<div class="w-full bg-white p-6 rounded shadow">';
    echo '<h2 class="text-xl font-semibold mb-4">เพิ่มรถใหม่</h2>';
    echo '<form method="post" class="space-y-4">';
    echo '<input type="hidden" name="action_type" value="add_vehicle">';
    echo '<input name="vehicle_name" placeholder="ชื่อรถ" required class="w-full border p-2 rounded">';
    echo '<textarea name="vehicle_detail" placeholder="รายละเอียดรถ" class="w-full border p-2 rounded"></textarea>';
    echo '<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">เพิ่มรถ</button>';
    echo '</form>';
    echo '</div>';

    // ฟอร์มเพิ่มเส้นทาง
    echo '<div class="w-full bg-white p-6 rounded shadow ">';
    echo '<h2 class="text-xl font-semibold mb-4">เพิ่มเส้นทางเดินรถ</h2>';
    echo '<form method="post" class="space-y-4">';
    echo '<input type="hidden" name="action_type" value="add_route">';
    echo '<input name="from_location" placeholder="ต้นทาง" required class="w-full border p-2 rounded">';
    echo '<input name="to_location" placeholder="ปลายทาง" required class="w-full border p-2 rounded">';
    echo '<button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">เพิ่มเส้นทาง</button>';
    echo '</form>';
    echo '</div>';

    echo '</div>';
    echo '</div>';

    echo '<div class="w-full flex flex-col md:flex-row gap-5 pt-5 justify-items-stretch">';
    echo '<div class="w-full bg-gray-50 p-6 mt-8 rounded shadow">';
    echo '<h2 class="text-xl font-semibold mb-4">📋 รายการรถทั้งหมด</h2>';

    $vehicle_list = $wpdb->get_results("SELECT * FROM $vehicles ORDER BY id DESC");

    if ($vehicle_list) {
        echo '<table class="w-full border text-left"><thead><tr class="bg-gray-100">
            <th class="p-2 border">ชื่อรถ</th>
            <th class="p-2 border">รายละเอียด</th>
        </tr></thead><tbody>';
        foreach ($vehicle_list as $v) {
            echo '<tr><td class="p-2 border">' . esc_html($v->name) . '</td>
                  <td class="p-2 border">' . esc_html($v->detail) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="text-gray-500">ยังไม่มีข้อมูลรถ</p>';
    }
    echo '</div>';


    // === รายการเส้นทางทั้งหมด ===
    $route_list = $wpdb->get_results("SELECT * FROM $routes ORDER BY id DESC");

    echo '<div class="w-full bg-gray-50 p-6 mt-8 rounded shadow">';
    echo '<h2 class="text-xl font-semibold mb-4">🧭 รายการเส้นทางเดินรถ</h2>';

    if ($route_list) {
        echo '<table class="w-full border text-left"><thead><tr class="bg-gray-100">
            <th class="p-2 border">ต้นทาง</th>
            <th class="p-2 border">ปลายทาง</th>
        </tr></thead><tbody>';
        foreach ($route_list as $r) {
            echo '<tr><td class="p-2 border">' . esc_html($r->from_location) . '</td>
                  <td class="p-2 border">' . esc_html($r->to_location) . '</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="text-gray-500">ยังไม่มีข้อมูลเส้นทาง</p>';
    }
    echo '</div>';

    echo '</div>';


    // หลัง form เพิ่มรถ/เส้นทาง
    $vehicle_options = $wpdb->get_results("SELECT * FROM $vehicles");
    $route_options = $wpdb->get_results("SELECT * FROM $routes");

    echo '<div class="bg-white p-6 rounded shadow max-w-xl mt-6">';
    echo '<h2 class="text-xl font-semibold mb-4">กำหนดราคารถในเส้นทาง</h2>';
    echo '<form method="post" class="space-y-4">';
    echo '<input type="hidden" name="action_type" value="add_vehicle_route">';

    echo '<select name="vehicle_id" required class="w-full border p-2 rounded">';
    echo '<option value="">เลือกรถ</option>';
    foreach ($vehicle_options as $v) {
        echo '<option value="' . esc_attr($v->id) . '">' . esc_html($v->name) . '</option>';
    }
    echo '</select>';

    echo '<select name="route_id" required class="w-full border p-2 rounded">';
    echo '<option value="">เลือกเส้นทาง</option>';
    foreach ($route_options as $r) {
        echo '<option value="' . esc_attr($r->id) . '">' . esc_html($r->from_location . ' → ' . $r->to_location) . '</option>';
    }
    echo '</select>';

    echo '<input name="price" type="number" step="0.01" placeholder="ราคาบริการ (บาท)" required class="w-full border p-2 rounded">';
    echo '<button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded">เพิ่มราคาสำหรับเส้นทางนี้</button>';
    echo '</form>';
    echo '</div>';

    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add_vehicle') {
        $wpdb->insert($wpdb->prefix . 'vehicle', [
            'vehicle_id' => intval($_POST['vehicle_id']),
            'route_id' => intval($_POST['route_id']),
            'price' => floatval($_POST['price']),
        ]);
        echo '<div class="notice notice-success"><p>✅ เพิ่มราคารถสำเร็จ</p></div>';
    }

    $vehicle_routes_table = $wpdb->prefix . 'vehicle_routes';
    $vehicles_table = $wpdb->prefix . 'vehicles';
    $routes_table = $wpdb->prefix . 'routes';

    $pricing_list = $wpdb->get_results("
    SELECT vr.id, vr.price, v.name AS vehicle_name, r.from_location, r.to_location
    FROM $vehicle_routes_table vr
    LEFT JOIN $vehicles_table v ON vr.vehicle_id = v.id
    LEFT JOIN $routes_table r ON vr.route_id = r.id
    ORDER BY v.name, r.from_location, r.to_location
");

    echo '<div class="w-full bg-gray-50 p-6 mt-8 rounded shadow">';
    echo '<h2 class="text-xl font-semibold mb-4">💰 รายการราคารถตามเส้นทาง</h2>';

    if ($pricing_list) {
        echo '<table class="w-full text-left border">';
        echo '<thead class="bg-gray-100"><tr>
        <th class="p-2 border">ชื่อรถ</th>
        <th class="p-2 border">ต้นทาง</th>
        <th class="p-2 border">ปลายทาง</th>
        <th class="p-2 border">ราคา (บาท)</th>
    </tr></thead><tbody>';

        foreach ($pricing_list as $item) {
            echo '<tr class="border-b hover:bg-gray-50">';
            echo '<td class="p-2 border">' . esc_html($item->vehicle_name) . '</td>';
            echo '<td class="p-2 border">' . esc_html($item->from_location) . '</td>';
            echo '<td class="p-2 border">' . esc_html($item->to_location) . '</td>';
            echo '<td class="p-2 border text-right">' . number_format($item->price, 2) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="text-gray-500">ยังไม่มีการกำหนดราคารถสำหรับเส้นทางใด ๆ</p>';
    }

    echo '</div>';
}
