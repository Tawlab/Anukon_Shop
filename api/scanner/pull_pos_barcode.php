<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึง Barcode ใหม่สุดที่ยังไม่โดนหยิบ
$sql = "SELECT id, barcode FROM pos_scan_queue WHERE user_id = ? AND is_processed = 0 ORDER BY id ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $id = $row['id'];
    $barcode = $row['barcode'];

    // อัปเดตสถานะให้เป็น "ถูกดึงไปใช้แล้ว" ทันที เพื่อไม่ให้ Loop ค้าง
    $upd_sql = "UPDATE pos_scan_queue SET is_processed = 1 WHERE id = ?";
    $upd_stmt = $conn->prepare($upd_sql);
    $upd_stmt->bind_param("i", $id);
    $upd_stmt->execute();
    $upd_stmt->close();

    // ดึงข้อมูลสินค้าจากฐานข้อมูล (พร้อมยอดคงเหลือ เพื่อเตือนถ้าของหมด)
    $prod_sql = "SELECT p.prod_id, p.prod_name, p.price, COALESCE(s.total_qty, 0) as stock_qty 
                 FROM products p 
                 LEFT JOIN stocks s ON p.prod_id = s.prod_id 
                 WHERE p.barcode = ?";
    $prod_stmt = $conn->prepare($prod_sql);
    $prod_stmt->bind_param("s", $barcode);
    $prod_stmt->execute();
    $prod_res = $prod_stmt->get_result();

    if ($prod_res->num_rows > 0) {
        $product = $prod_res->fetch_assoc();
        echo json_encode([
            'status' => 'success', 
            'product' => $product,
            'barcode' => $barcode
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบสินค้าที่มีบาร์โค้ด ' . $barcode]);
    }
    $prod_stmt->close();
} else {
    // ไม่มีแสกนใหม่
    echo json_encode(['status' => 'empty']);
}

$stmt->close();
$conn->close();
?>
