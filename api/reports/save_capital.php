<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($amount < 0) {
        echo json_encode(['status' => 'error', 'message' => 'จำนวนเงินไม่ถูกต้อง']);
        exit;
    }

    $sql = "INSERT INTO store_settings (setting_key, setting_value) VALUES ('initial_capital', ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $amount, $amount);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตทุนเริ่มต้นวิเคราะห์ระบบแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
}
?>
