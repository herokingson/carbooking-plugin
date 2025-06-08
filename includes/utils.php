<?php
function cbp_send_booking_email($to, $start, $end) {
    $subject = 'ยืนยันการจองรถ';
    $message = "คุณได้ทำการจองรถเรียบร้อยแล้ว\nเวลาเริ่มต้น: $start\nเวลาสิ้นสุด: $end";
    wp_mail($to, $subject, $message);
}
