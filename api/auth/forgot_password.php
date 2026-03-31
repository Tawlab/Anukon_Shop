<?php
session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';

    if (empty($username) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    // 1. ตรวจสอบว่ามีผู้ใช้รายนี้และอีเมลตรงกันหรือไม่
    $sql = "SELECT user_id FROM users WHERE username = ? AND email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 2. สร้างรหัสผ่านใหม่แบบสุ่ม 6 หลัก
        $new_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // 3. อัปเดตรหัสผ่านใหม่ลงฐานข้อมูล
        $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashed_password, $user['user_id']);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'รีเซ็ตรหัสผ่านสำเร็จ!<br><br>รหัสผ่านใหม่ของคุณคือ: <b>' . $new_password . '</b><br><small class="text-danger">กรุณาจดจำล็อกอินเข้าสู่ระบบและติดต่อ Admin เพื่อเปลี่ยนรหัสต่อไป</small>'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน']);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล หรืออีเมลไม่ตรงกับรายชื่อผู้ใช้งาน']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
