<?php
// ตั้งค่าการเชื่อมต่อ
$db_host = "localhost";
$db_user = "root";       
$db_pass = "";           
$db_name = "4744774_kaykong";

// เริ่มต้นการเชื่อมต่อด้วย MySQLi
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// ตรวจสอบความถูกต้อง (Security & Error Handling)
if ($conn->connect_error) {
    // ในระยะพัฒนา (XAMPP) ให้โชว์ Error
    // แต่ถ้าขึ้น Production จริง ควรเปลี่ยนเป็น die("Error: Please contact admin.");
    die("Database Connection Failed: " . $conn->connect_error);
}

// ตั้งค่า Encoding (สำคัญมากสำหรับข้อมูลภาษาไทยใน 21 ตารางของคุณ)
$conn->set_charset("utf8mb4");

// ตั้งค่า Timezone (เพื่อให้เวลาใน created_at ตรงกับเวลาไทย)
date_default_timezone_set('Asia/Bangkok');

// พร้อมใช้งานตัวแปร $conn ในไฟล์อื่นๆ ผ่านการ include_once
?>