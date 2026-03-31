<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // รับค่าข้อมูลส่วนตัว - ป้องกัน XSS ด้วย trim
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $nick_name  = trim($_POST['nick_name'] ?? '');
    $phone_no   = trim($_POST['phone_no'] ?? '');
    $email      = trim($_POST['email'] ?? '');

    // รับค่าที่อยู่
    $home_no    = trim($_POST['home_no'] ?? '');
    $moo        = trim($_POST['moo'] ?? '');
    $soi        = trim($_POST['soi'] ?? '');
    $road       = trim($_POST['road'] ?? '');
    $village    = trim($_POST['village'] ?? '');
    $sub_dist_id = intval($_POST['sub_dist_id'] ?? 0);
    $remark     = trim($_POST['remark'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อและนามสกุล']);
        exit;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกอีเมลที่ถูกต้อง']);
        exit;
    }
    if (empty($home_no) || $sub_dist_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกที่อยู่ให้ครบถ้วน']);
        exit;
    }

    // เช็คอีเมลซ้ำ (ยกเว้นของตัวเอง)
    $check_sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'อีเมลนี้ถูกใช้งานแล้ว']);
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    $conn->begin_transaction();

    try {
        // ดึง address_id ปัจจุบันของ user
        $stmt_get = $conn->prepare("SELECT address_id FROM users WHERE user_id = ?");
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $current_addr_id = $stmt_get->get_result()->fetch_assoc()['address_id'] ?? null;
        $stmt_get->close();

        if ($current_addr_id) {
            // อัปเดตที่อยู่ที่มีอยู่แล้ว
            $sql_addr = "UPDATE addresses SET home_no=?, moo=?, soi=?, road=?, village=?, remark=?, sub_dist_id=? WHERE address_id=?";
            $stmt_addr = $conn->prepare($sql_addr);
            $stmt_addr->bind_param("ssssssii", $home_no, $moo, $soi, $road, $village, $remark, $sub_dist_id, $current_addr_id);
            $stmt_addr->execute();
            $stmt_addr->close();
        } else {
            // สร้างที่อยู่ใหม่
            $sql_addr = "INSERT INTO addresses (home_no, moo, soi, road, village, remark, sub_dist_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_addr = $conn->prepare($sql_addr);
            $stmt_addr->bind_param("ssssssi", $home_no, $moo, $soi, $road, $village, $remark, $sub_dist_id);
            $stmt_addr->execute();
            $current_addr_id = $conn->insert_id;
            $stmt_addr->close();
        }

        // อัปเดตข้อมูล user
        $sql_user = "UPDATE users SET first_name=?, last_name=?, nick_name=?, phone_no=?, email=?, address_id=? WHERE user_id=?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("sssssii", $first_name, $last_name, $nick_name, $phone_no, $email, $current_addr_id, $user_id);
        $stmt_user->execute();
        $stmt_user->close();

        // อัปเดต session name
        $_SESSION['full_name'] = $first_name;

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลสำเร็จ']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
