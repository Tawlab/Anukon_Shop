<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') exit;

$amount = $_POST['amount'];
$remark = $_POST['remark'];
$user_id = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // 1. บันทึกประวัติลงใน capital_history
    $stmt1 = $conn->prepare("INSERT INTO capital_history (user_id, amount, remark) VALUES (?, ?, ?)");
    $stmt1->bind_param("ids", $user_id, $amount, $remark);
    $stmt1->execute();

    // 2. อัปเดตยอดรวมใน store_settings (บวกจากของเดิม)
    $stmt2 = $conn->prepare("UPDATE store_settings SET setting_value = setting_value + ? WHERE setting_key = 'initial_capital'");
    $stmt2->bind_param("d", $amount);
    $stmt2->execute();

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'ปรับปรุงยอดเงินทุนเรียบร้อยแล้ว']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>