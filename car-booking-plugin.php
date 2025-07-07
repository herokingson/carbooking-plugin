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

function cbp_register_shortcodes()
{
    add_shortcode('car_booking_search', 'cbp_render_search_form');
    add_shortcode('car_booking_confirm', 'cbp_render_booking_confirm');
}
add_action('init', 'cbp_register_shortcodes');

function cbp_enqueue_assets()
{
    wp_enqueue_style('cbp-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
    wp_enqueue_style('icon-font', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    wp_enqueue_style('cbp-style', plugins_url('dist/css/app.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'cbp_enqueue_assets');

function cbp_admin_enqueue_assets($hook)
{
    // ตรวจสอบว่าอยู่ในหน้าของ plugin เราเท่านั้น (optional)
    if ($hook !== 'toplevel_page_cbp-admin')
        return;

    // โหลด Tailwind CDN
    wp_enqueue_style('cbp-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    // โหลด style.css เพิ่มถ้ามี
    // wp_enqueue_style('cbp-style', plugins_url('assets/scss/app.scss', __FILE__));


}
add_action('admin_enqueue_scripts', 'cbp_admin_enqueue_assets');
// ... Remaining logic already covered ...

add_action('wp_enqueue_scripts', 'remove_hello_theme_style', 20);
function remove_hello_theme_style()
{
    wp_dequeue_style('hello-elementor'); // ชื่อ handle ของ style.css ของ Hello Theme
    wp_deregister_style('hello-elementor');
}

add_action('template_redirect', function () {
    // โหลด Tailwind CDN
    wp_enqueue_style('cbp-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

    if (
        isset($_POST['car_booking_confirm']) &&
        is_user_logged_in()
    ) {
        echo cbp_render_booking_confirm();
        exit;
    }
});

add_action('admin_enqueue_scripts', 'carbooking_admin_scripts');
function carbooking_admin_scripts($hook)
{
    wp_enqueue_media();
    // โหลด app.js เพิ่มถ้ามี
    wp_enqueue_script('carbooking-admin', plugins_url('assets/js/app.js', __FILE__));
    // wp_enqueue_script('carbooking-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), null, true);
}