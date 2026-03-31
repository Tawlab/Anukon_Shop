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

    if ($prod_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสสินค้าไม่ถูกต้อง']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // การลบสินค้าที่มี Foreign Key (ON DELETE CASCADE ทำงานในตารางลูก แต่เราเช็คได้เผื่อเกิด error)
        $sql = "DELETE FROM products WHERE prod_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['status' => 'success']);
        } else {
            throw new Exception("ไม่พบสินค้าที่ต้องการลบ");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        // ถ้าติด Foreign Key Constraint ฝั่ง history ออเดอร์ (details_sales ไม่ได้ใส่ DELETE CASCADE ในบางกรณี)
        if ($conn->errno === 1451) {
            // ทำ Soft Delete แทนถง้าระบบมีเงื่อนไขห้ามลบประวัติการขาย
            $soft = $conn->prepare("UPDATE products SET status = 0 WHERE prod_id = ?");
            $soft->bind_param("i", $prod_id);
            $soft->execute();
            echo json_encode(['status' => 'success', 'message' => 'สินค้าถูกปรับซ่อนแทนการลบ เนื่องจากมีประวัติการขายผูกอยู่']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
