<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: สิทธิ์ Admin เท่านั้น']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? 'working'; // initial or working
    $user_id = $_SESSION['user_id'];
    
    if ($amount < 0) {
        echo json_encode(['status' => 'error', 'message' => 'จำนวนเงินไม่ถูกต้อง']);
        exit;
    }
    
    if ($type === 'initial') {
        // บันทึกลง store_settings
        $chk = $conn->query("SELECT setting_id FROM store_settings WHERE setting_key = 'initial_capital'");
        if ($chk->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'initial_capital'");
            $stmt->bind_param("d", $amount);
        } else {
            $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES ('initial_capital', ?)");
            $stmt->bind_param("d", $amount);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'ตั้งค่าทุนเริ่มต้นร้านสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ตั้งค่าล้มเหลว ' . $conn->error]);
        }
        $stmt->close();
    } else {
        // บันทึกลง capital_history (ทุนหมุนเวียนอัดฉีด)
        $remark = $_POST['remark'] ?? 'อัดฉีดทุนร้านค้าเพิ่มเติม';
        $stmt = $conn->prepare("INSERT INTO capital_history (user_id, amount, remark) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $amount, $remark);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มประวัติทุนหมุนเวียนสำเร็จ']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'บันทึกไม่สำเร็จ ' . $conn->error]);
        }
        $stmt->close();
    }
}
?>