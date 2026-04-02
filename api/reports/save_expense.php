<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $exp_date = $_POST['exp_date'] ?? date('Y-m-d');
    $title = $_POST['title'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    
    if (empty($title) || $amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
        exit;
    }

    // ฟิกซ์ปัญหาตาราง expense_categories ว่างเปล่า
    $check_cate = $conn->query("SELECT exp_cate_id FROM expense_categories WHERE exp_cate_id = 1");
    if($check_cate->num_rows == 0) {
        $conn->query("INSERT INTO expense_categories (exp_cate_id, exp_cate_name, exp_cate_status) VALUES (1, 'ค่าใช้จ่ายทั่วไป', 1)");
    }

    $sql = "INSERT INTO expenses (exp_cate_id, user_id, amount, exp_date, remark) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    $cate = 1; 
    $stmt->bind_param("iidss", $cate, $user_id, $amount, $exp_date, $title);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'บันทึกค่าใช้จ่ายสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
    }
    $stmt->close();
}
?>