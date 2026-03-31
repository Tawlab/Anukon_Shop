<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exp_type = trim($_POST['exp_type'] ?? '');
    $exp_amount = floatval($_POST['exp_amount'] ?? 0);

    if (empty($exp_type) || $exp_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบและถูกต้อง']);
        exit;
    }

    $sql = "INSERT INTO expenses (exp_type, exp_amount, exp_date) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $exp_type, $exp_amount);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกรายจ่ายได้: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
