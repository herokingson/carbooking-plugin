<?php
function car_booking_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $vehicles = $wpdb->prefix . 'vehicles';
    $routes = $wpdb->prefix . 'routes';
    $bookings = $wpdb->prefix . 'bookings';
    $vehicle_routes = $wpdb->prefix . 'vehicle_routes';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // ตารางรถ
    dbDelta("CREATE TABLE $vehicles (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        detail TEXT
    ) $charset_collate;");

    // ตารางเส้นทาง
    dbDelta("CREATE TABLE $routes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        from_location VARCHAR(255),
        to_location VARCHAR(255)
    ) $charset_collate;");

    // ตารางความสัมพันธ์ รถ + เส้นทาง + ราคา
    dbDelta("CREATE TABLE $vehicle_routes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id BIGINT,
        route_id BIGINT,
        price DECIMAL(10,2),
        UNIQUE KEY unique_vehicle_route (vehicle_id, route_id)
    ) $charset_collate;");

    // ตารางการจอง
    dbDelta("CREATE TABLE $bookings (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id BIGINT,
        route_id BIGINT,
        start_time DATETIME,
        end_time DATETIME,
        user_email VARCHAR(255),
        status ENUM('pending','confirmed','cancelled') DEFAULT 'pending'
    ) $charset_collate;");
}
