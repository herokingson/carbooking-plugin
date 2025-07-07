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
            'engine' => sanitize_text_field($_POST['vehicle_engine']),
            'seat' => sanitize_text_field($_POST['vehicle_seat']),
            'bag' => sanitize_text_field($_POST['vehicle_bag']),
            'image_id' => isset($_POST['vehicle_image']) ? intval($_POST['vehicle_image']) : null
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
        $vehicle_id = intval($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;
        $route_id = intval($_POST['route_id']) ? $_POST['route_id'] : null;
        $price = floatval($_POST['price']) ? $_POST['price'] : null;

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

    if (isset($_GET['delete_vehicle'])) {
        $vehicle_id = intval($_GET['delete_vehicle']);
        $wpdb->delete($vehicles, ['id' => $vehicle_id]);
        echo '<div class="notice notice-success"><p>ลบรถเรียบร้อยแล้ว</p></div>';
    }

    if (isset($_GET['delete_route'])) {
        $route_id = intval($_GET['delete_route']);
        $wpdb->delete($routes, ['id' => $route_id]);
        echo '<div class="notice notice-success"><p>ลบเส้นทางเดินรถเรียบร้อยแล้ว</p></div>';
    }

    if (isset($_GET['delete_vehicle_route'])) {
        $delete_id = intval($_GET['delete_vehicle_route']);
        $vehicle_routes_table = $wpdb->prefix . 'vehicle_routes';
        $wpdb->delete($vehicle_routes_table, ['id' => $delete_id]);
        echo '<div class="notice notice-success"><p>ลบราคาตามเส้นทางเรียบร้อยแล้ว</p></div>';
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
    echo '<input name="vehicle_engine" placeholder="ชื่อเครื่องยนต์" required class="w-full border p-2 rounded">';
    echo '<input name="vehicle_seat" placeholder="ที่นั่ง" required class="w-full border p-2 rounded">';
    echo '<input name="vehicle_bag" placeholder="กระเป๋า" required class="w-full border p-2 rounded">';

    // 🎯 เพิ่มส่วนอัปโหลดรูปภาพรถ
    echo '<div>';
    echo '<label class="block mb-2 font-medium">รูปภาพรถ</label>';
    echo '<input type="hidden" name="vehicle_image" id="vehicle_image">';
    echo '<button type="button" class="button bg-gray-200 px-3 py-1 rounded car_image_upload_button">เลือกรูปภาพ</button>';
    echo '<div class="car_image_preview mt-2"></div>';
    echo '</div>';

    echo '<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">เพิ่มรถ</button>';
    echo '</form>';
    echo '</div>';

    // ฟอร์มเพิ่มเส้นทาง
    echo '<div class="w-full bg-white p-6 rounded shadow ">';
    echo '<h2 class="text-xl font-semibold mb-4">เพิ่มเส้นทางเดินรถ</h2>';
    echo '<form method="post" class="space-y-4">';
    echo '<input type="hidden" name="action_type" value="add_route">';
    echo '<input name="from_location" placeholder="ต้นทาง" required class="w-full border p-2 rounded">';
    echo '<input name="to_location" placeholder="ปลายทาง" required class="w-full border p-2 rounded">';
    echo '<button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full">เพิ่มเส้นทาง</button>';
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
            <th class="p-2 border">เครื่องยนต์</th>
            <th class="p-2 border">ที่นั่ง</th>
            <th class="p-2 border">กระเป๋า</th>
            <th class="p-2 border">รูปภาพ</th>
            <th class="p-2 border">ลบ</th>
        </tr></thead><tbody>';
        
        foreach ($vehicle_list as $v) {
            echo '<tr>';
            echo '<td class="p-2 border">' . esc_html($v->name) . '</td>';
            echo '<td class="p-2 border">' . esc_html($v->engine) . '</td>';
            echo '<td class="p-2 border">' . esc_html($v->seat) . '</td>';
            echo '<td class="p-2 border">' . esc_html($v->bag) . '</td>';
    
            if (!empty($v->image_id)) {
                $image_url = wp_get_attachment_url($v->image_id);
                if ($image_url) {
                    echo '<td class="p-2 border"><img src="' . esc_url($image_url) . '" style="max-width:100px;"></td>';
                } else {
                    echo '<td class="p-2 border text-center text-gray-400">-</td>';
                }
            } else {
                echo '<td class="p-2 border text-center text-gray-400">-</td>';
            }

            echo '<td class="p-2 border text-center">
                <a href="?page=cbp-admin&delete_vehicle=' . intval($v->id) . '" 
                onclick="return confirm(\'ยืนยันการลบรถ?\')" 
                class="text-red-600 hover:underline">ลบ</a></td>';

            echo '</tr>';
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
            <th class="p-2 border text-center">ลบ</th>
        </tr></thead><tbody>';
        foreach ($route_list as $r) {
            echo '<tr><td class="p-2 border">' . esc_html($r->from_location) . '</td>
                  <td class="p-2 border">' . esc_html($r->to_location) . '</td>';
            echo '<td class="p-2 border text-center">
                <a href="?page=cbp-admin&delete_route=' . intval($r->id) . '" 
                onclick="return confirm(\'ยืนยันการลบเส้นทางเดินรถ?\')" 
                class="text-red-600 hover:underline">ลบ</a></td>';

            echo '</tr>';
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
    echo '<div class="flex flex-row mt-8 gap-5">';
    echo '<div class="bg-white p-6 rounded shadow">';
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
    SELECT 
        vr.id, 
        vr.price, 
        v.name AS vehicle_name, 
        r.from_location, 
        r.to_location,
        (
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bookings b 
            WHERE b.vehicle_id = vr.vehicle_id AND b.route_id = vr.route_id
        ) AS booking_count
    FROM $vehicle_routes_table vr
    LEFT JOIN $vehicles_table v ON vr.vehicle_id = v.id
    LEFT JOIN $routes_table r ON vr.route_id = r.id
    ORDER BY v.name, r.from_location, r.to_location
");

    echo '<div class="w-full bg-gray-50 p-6 rounded shadow">';
    echo '<h2 class="text-xl font-semibold mb-4">💰 รายการราคารถตามเส้นทาง</h2>';

    if ($pricing_list) {
        echo '<table class="w-full text-left border">';
        echo '<thead class="bg-gray-100"><tr>
        <th class="p-2 border">ชื่อรถ</th>
        <th class="p-2 border">ต้นทาง</th>
        <th class="p-2 border">ปลายทาง</th>
        <th class="p-2 border">ราคา (บาท)</th>
        <th class="p-2 border">สถานะ</th>
         <th class="p-2 border text-center">ลบ</th>
    </tr></thead><tbody>';

        foreach ($pricing_list as $item) {
            echo '<tr class="border-b hover:bg-gray-50">';
            echo '<td class="p-2 border">' . esc_html($item->vehicle_name) . '</td>';
            echo '<td class="p-2 border">' . esc_html($item->from_location) . '</td>';
            echo '<td class="p-2 border">' . esc_html($item->to_location) . '</td>';
            echo '<td class="p-2 border text-right">' . number_format($item->price, 2) . '</td>';
            echo '<td class="p-2 border text-center">';
            if ($item->booking_count > 0) {
                echo '<span class="text-green-600 font-semibold">ถูกจองเรียบร้อย</span>';
            } else {
                echo '<span class="text-gray-500">ไม่มีการจอง</span>';
            }
            echo '</td>';
            echo '<td class="p-2 border text-center">
                <a href="?page=cbp-admin&delete_vehicle_route=' . intval($item->id) . '" 
                onclick="return confirm(\'ยืนยันการลบราคานี้?\')" 
                class="text-red-600 hover:underline">ลบ</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p class="text-gray-500">ยังไม่มีการกำหนดราคารถสำหรับเส้นทางใด ๆ</p>';
    }

    echo '</div>';
    echo '</div>';
}