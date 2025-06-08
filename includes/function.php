<?php 

// includes/functions.php
function get_available_vehicles($from, $to, $desired_time) {
    global $wpdb;
    $vehicles_table = $wpdb->prefix . 'vehicles';
    $routes_table = $wpdb->prefix . 'routes';
    $bookings_table = $wpdb->prefix . 'bookings';

    $route_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $routes_table WHERE from_location = %s AND to_location = %s",
        $from, $to
    ));

    if (!$route_id) return [];

    $start = date('Y-m-d H:i:s', strtotime($desired_time));
    $end = date('Y-m-d H:i:s', strtotime($desired_time . ' +6 hours'));

    $booked_vehicle_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT vehicle_id FROM $bookings_table
         WHERE route_id = %d AND
         ((start_time <= %s AND end_time >= %s) OR (start_time <= %s AND end_time >= %s))
         AND status = 'confirmed'",
         $route_id, $start, $start, $end, $end
    ));

    $ids_placeholder = implode(',', array_map('intval', $booked_vehicle_ids));

    $available_vehicles = empty($ids_placeholder)
        ? $wpdb->get_results("SELECT * FROM $vehicles_table")
        : $wpdb->get_results("SELECT * FROM $vehicles_table WHERE id NOT IN ($ids_placeholder)");

    return $available_vehicles;
}


function cbp_render_search_form() {
    ob_start();
    ?>
    <form method="get">
        <input type="text" name="from" placeholder="ต้นทาง" required>
        <input type="text" name="to" placeholder="ปลายทาง" required>
        <input type="datetime-local" name="start_time" required>
        <button type="submit">ค้นหารถว่าง</button>
    </form>
    <?php
    if (!empty($_GET['from']) && !empty($_GET['to']) && !empty($_GET['start_time'])) {
        $vehicles = get_available_vehicles($_GET['from'], $_GET['to'], $_GET['start_time']);
        if (!empty($vehicles)) {
            echo '<ul>';
            foreach ($vehicles as $v) {
                echo '<li>' . esc_html($v->name) . ' - ' . esc_html($v->detail) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>ไม่พบรถว่างในช่วงเวลานี้</p>';
        }
    }
    return ob_get_clean();
}