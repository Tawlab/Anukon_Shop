<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $supplier = trim($_POST['supplier_name'] ?? '');
    $total_price = floatval($_POST['total_price'] ?? 0);
    $status = 1; // สำเร็จ เข้าระบบเลย
    
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $unit_costs = $_POST['unit_costs'] ?? [];

    if (empty($products) || $total_price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // 1. สร้างบิลสั่งซื้อเข้า
        $sql_bill = "INSERT INTO bill_purchases (user_id, total_price, supplier_name, status) VALUES (?, ?, ?, ?)";
        $stmt_bill = $conn->prepare($sql_bill);
        $stmt_bill->bind_param("idsi", $user_id, $total_price, $supplier, $status);
        $stmt_bill->execute();
        $purchase_id = $conn->insert_id;

        // เตรียม Query สำหรับเขียนรายละเอียดและอัปเดตสต็อก
        $sql_det = "INSERT INTO details_purchases (purchase_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)";
        $stmt_det = $conn->prepare($sql_det);

        $sql_stock = "UPDATE stocks SET total_qty = total_qty + ? WHERE prod_id = ?";
        $stmt_stock = $conn->prepare($sql_stock);

        foreach ($products as $index => $prod_id) {
            $qty = intval($quantities[$index] ?? 0);
            $cost = floatval($unit_costs[$index] ?? 0);

            if ($qty <= 0) continue;

            // บันทึกรายละเอียดการซื้อ
            $stmt_det->bind_param("iiid", $purchase_id, $prod_id, $qty, $cost);
            $stmt_det->execute();

            // เพิ่มลงสต็อก
            $stmt_stock->bind_param("ii", $qty, $prod_id);
            $stmt_stock->execute();
            
            // กรณีเป็นการซื้อของใหม่ที่ยังไม่เคยมีคิวในตาราง stocks (แต่อันนี้ตอนแอด product เราให้เค้าใส่ initial_stock ไปแล้ว มันน่าจะมีคิว 100%)
            if ($stmt_stock->affected_rows === 0) {
                // insert ให้ใหม่ถ้ายังไม่มีคิว
                $conn->query("INSERT INTO stocks (prod_id, total_qty) VALUES ($prod_id, $qty)");
            }
        }

        // 2. บันทึกลงตาราง expenses ด้วยว่าเป็นค่าใช้จ่ายของร้านประเภท "สั่งซื้อสินค้า"
        $expense_type = "สั่งซื้อสินค้าเข้าสต็อก (รหัส PUR-" . str_pad($purchase_id, 4, '0', STR_PAD_LEFT) . ")";
        $sql_exp = "INSERT INTO expenses (exp_type, exp_amount, exp_date) VALUES (?, ?, NOW())";
        $stmt_exp = $conn->prepare($sql_exp);
        $stmt_exp->bind_param("sd", $expense_type, $total_price);
        $stmt_exp->execute();

        $conn->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>
