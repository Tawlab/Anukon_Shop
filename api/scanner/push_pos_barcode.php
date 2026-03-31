<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcode = $_POST['barcode'] ?? '';
    $user_id = $_SESSION['user_id']; // ใช้ User ID แทน Session ID เพื่อผูกคอมกับมือถือเครื่องเดียวกัน
    
    if (empty($barcode)) {
        echo json_encode(['status' => 'error', 'message' => 'No barcode provided']);
        exit;
    }

    // สร้างตาราง pos_scan_queue อัตโนมัติถ้ายังไม่มี
    $conn->query("CREATE TABLE IF NOT EXISTS pos_scan_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        is_processed TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $sql = "INSERT INTO pos_scan_queue (barcode, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $barcode, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Barcode sent to POS']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
    
    $stmt->close();
    $conn->close();
}
?>
