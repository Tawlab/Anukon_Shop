<?php
session_start();
require_once '../../config/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
        exit;
    }

    // 1. ค้นหาผู้ใช้จากชื่อ (Username)
    $sql = "SELECT user_id, password, first_name, role, user_status FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 2. ตรวจสอบสถานะการใช้งาน
        if ($user['user_status'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'บัญชีนี้ถูกระงับการใช้งาน']);
            exit;
        }

        // 3. ตรวจสอบรหัสผ่านที่เข้ารหัสไว้
        if (password_verify($password, $user['password'])) {
            // 4. สร้าง Session เพื่อจำสถานะการล็อกอิน
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];

            // 5. ระบบ Remember Me (ตั้งคุกกี้โดยใช้ Hash ที่อ้างอิงจากรหัสผ่านใน DB)
            if (isset($_POST['remember']) && $_POST['remember'] === 'true') {
                $hash = hash_hmac('sha256', $user['user_id'], $user['password']);
                $cookie_val = $user['user_id'] . ':' . $hash;
                // ตั้ง Cookie อายุ 30 วัน
                setcookie('vshop_remember', $cookie_val, time() + (86400 * 30), "/");
            }

            echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'รหัสผ่านไม่ถูกต้อง']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบชื่อผู้ใช้งานนี้']);
    }

    $stmt->close();
    $conn->close();
}
?>