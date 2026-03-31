<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    $exp_date = $_POST['exp_date'] ?? date('Y-m-d');
    
    if (empty($title) || $amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    $sql = "INSERT INTO expenses (title, amount, exp_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $title, $amount, $exp_date);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกค่าใช้จ่ายสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
}
?>
