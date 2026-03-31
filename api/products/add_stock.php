<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prod_id = intval($_POST['prod_id'] ?? 0);
    $qty = intval($_POST['qty'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? null;
    $detail_purchase_id = !empty($_POST['detail_purchase_id']) ? intval($_POST['detail_purchase_id']) : 0;

    if ($prod_id <= 0 || $qty <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // เพิ่มใน stock_movements
        $move_sql = "INSERT INTO stock_movements (prod_id, movement_type, quantity, ref_id, remark) VALUES (?, 'IN', ?, 'SCAN_RECEIVE', 'รับเข้าจากการสแกน')";
        $stmt_m = $conn->prepare($move_sql);
        $stmt_m->bind_param("ii", $prod_id, $qty);
        $stmt_m->execute();

        // เพิ่มใน product_batches (ถ้าระบุวันหมดอายุ)
        if ($expiry_date) {
            $batch_sql = "INSERT INTO product_batches (detail_purchase_id, product_id, lot_qty, expiry_date, received_date) VALUES (?, ?, ?, ?, CURDATE())";
            $stmt_b = $conn->prepare($batch_sql);
            $stmt_b->bind_param("iiis", $detail_purchase_id, $prod_id, $qty, $expiry_date);
            $stmt_b->execute();
        }

        // อัปเดตตาราง stocks หลัก
        $upd_sql = "UPDATE stocks SET total_qty = total_qty + ? WHERE prod_id = ?";
        $stmt_upd = $conn->prepare($upd_sql);
        $stmt_upd->bind_param("ii", $qty, $prod_id);
        $stmt_upd->execute();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'รับเข้าสต็อกเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'ข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
