<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ดึง 1 แถวที่รออยู่
    $sql = "SELECT id, barcode FROM scan_queue WHERE status = 0 ORDER BY id ASC LIMIT 1";
    $result = $conn->query($sql);

    // สร้างตารางถ้ายังไม่มี
    if (!$result) {
        $create_table = "CREATE TABLE IF NOT EXISTS `scan_queue` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `barcode` varchar(100) NOT NULL,
          `status` tinyint(4) DEFAULT 0 COMMENT '0=รอใช้, 1=ใช้งานแล้ว',
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($create_table);
        $result = $conn->query($sql);
    }

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $scan_id = $row['id'];
        $barcode = $row['barcode'];

        // ค้นหาว่าเป็นสินค้าเก่าหรือของใหม่
        $prod_sql = "SELECT p.prod_id, p.prod_name, p.price, t.type_name 
                     FROM products p
                     LEFT JOIN prod_types t ON p.type_id = t.type_id
                     WHERE p.barcode = ?";
        $stmt_prod = $conn->prepare($prod_sql);
        $stmt_prod->bind_param("s", $barcode);
        $stmt_prod->execute();
        $prod_res = $stmt_prod->get_result();

        $product_data = null;
        if ($prod_res->num_rows > 0) {
            $product_data = $prod_res->fetch_assoc();
            $is_new = false;
        } else {
            $is_new = true;
        }
        $stmt_prod->close();

        // เปลี่ยนสถานะเป็น 1 (ใช้งานแล้ว) เพื่อไม่ให้เด้งซ้ำ
        $upd_sql = "UPDATE scan_queue SET status = 1 WHERE id = ?";
        $stmt_upd = $conn->prepare($upd_sql);
        $stmt_upd->bind_param("i", $scan_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        echo json_encode([
            'status' => 'success',
            'barcode' => $barcode,
            'is_new' => $is_new,
            'product' => $product_data
        ]);
    } else {
        echo json_encode(['status' => 'none']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
