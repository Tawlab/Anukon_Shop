<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'];
    $cart = $data['cart'] ?? [];
    $payment_type = $data['payment_type'] ?? 'Cash';
    $total_price = $data['total_price'] ?? 0;
    
    if (empty($cart)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีสินค้าในตะกร้า']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Step 1: สร้างบิล (POS คือสำเร็จทันที สถานะ 3 = เสร็จสิ้น)
        $sale_status = 3; 
        $shipping = 'store_pickup';
        $remark = 'ขายหน้าร้าน (POS)';
        
        $sql_bill = "INSERT INTO bill_sales (user_id, total_price, shipping_type, payment_type, sale_status, remark) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_bill = $conn->prepare($sql_bill);
        // user_id ตรงนี้คือ Admin ที่เป็นคนกดขาย 
        $stmt_bill->bind_param("idssis", $user_id, $total_price, $shipping, $payment_type, $sale_status, $remark);
        $stmt_bill->execute();
        $sale_id = $conn->insert_id;

        // Step 2 & 3: ตัดสต็อกและบันทึกรายละเอียด
        $sql_detail = "INSERT INTO details_sales (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);

        $sql_stock = "UPDATE stocks SET total_qty = total_qty - ? WHERE prod_id = ? AND total_qty >= ?";
        $stmt_stock = $conn->prepare($sql_stock);

        foreach ($cart as $item) {
            $qty = (int)$item['qty'];
            $price = (float)$item['price'];
            $subtotal = $qty * $price;
            $prod_id = (int)$item['prod_id'];

            // บันทึกรายละเอียดการขาย
            $stmt_detail->bind_param("iiidd", $sale_id, $prod_id, $qty, $price, $subtotal);
            $stmt_detail->execute();

            // ตัดสต็อก
            $stmt_stock->bind_param("iii", $qty, $prod_id, $qty);
            $stmt_stock->execute();
            
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("สินค้า '" . $item['prod_name'] . "' สต็อกไม่เพียงพอ");
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'ทำรายการขายหน้าร้านสำเร็จ (บิล #' . str_pad($sale_id, 4, '0', STR_PAD_LEFT) . ')']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
}
?>
