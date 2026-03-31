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
    $prod_id = intval($_POST['prod_id'] ?? 0);
    $prod_name = trim($_POST['prod_name'] ?? '');
    $type_id = intval($_POST['type_id'] ?? 0);
    $barcode = trim($_POST['barcode'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $detail = trim($_POST['detail'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    if ($prod_id <= 0 || empty($prod_name) || $type_id <= 0 || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'โปรดกรอกข้อมูลสำคัญให้ครบถ้วน']);
        exit;
    }

    // แก้ไขข้อมูล (ไม่ได้ยุ่งกับรูปภาพในเวอร์ชันนี้ จะทำเป็นอัปเดตแยกถ้าระบบรองรับให้ง่ายขึ้น)
    $sql = "UPDATE products SET prod_name=?, type_id=?, barcode=?, price=?, detail=?, status=? WHERE prod_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisdsii", $prod_name, $type_id, $barcode, $price, $detail, $status, $prod_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        // เช็คกรณี barcode ซ้ำ
        if ($conn->errno === 1062) {
            echo json_encode(['status' => 'error', 'message' => 'รหัสบาร์โค้ดนี้ถูกใช้งานแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
        }
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
