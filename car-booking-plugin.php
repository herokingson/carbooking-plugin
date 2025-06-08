<?php
/*
Plugin Name: Car Booking Plugin
Description: ระบบจองรถจากต้นทางไปปลายทาง พร้อมระบบค้นหาและเช็คสถานะการจอง
Version: 1.1
Author: Developer herokingson
*/

defined('ABSPATH') or die('No script kiddies please!');

require_once plugin_dir_path(__FILE__) . 'includes/db-schema.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/booking-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils.php';

register_activation_hook(__FILE__, 'car_booking_create_tables');

function cbp_register_shortcodes() {
    add_shortcode('car_booking_search', 'cbp_render_search_form');
    add_shortcode('car_booking_confirm', 'cbp_render_booking_confirm');
}
add_action('init', 'cbp_register_shortcodes');

function cbp_enqueue_assets() {
    wp_enqueue_style('cbp-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
    wp_enqueue_style('cbp-style', plugins_url('dist/css/app.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'cbp_enqueue_assets');

function cbp_admin_enqueue_assets($hook) {
    // ตรวจสอบว่าอยู่ในหน้าของ plugin เราเท่านั้น (optional)
    if ($hook !== 'toplevel_page_cbp-admin') return;

    // โหลด Tailwind CDN
    wp_enqueue_style('cbp-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // โหลด style.css เพิ่มถ้ามี
    wp_enqueue_style('cbp-style', plugins_url('assets/css/style.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'cbp_admin_enqueue_assets');
// ... Remaining logic already covered ...
