<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $payment_type = $_POST['payment_type'] ?? 'COD';
    $shipping_type = $_POST['shipping_type'] ?? 'store_pickup';
    $total_price = $_POST['total_price'] ?? 0;
    $remark = $_POST['order_remark'] ?? '';
    
    // 1. ดึง address_id ของลูกค้าคนนี้มาก่อน
    $stmt_addr = $conn->prepare("SELECT address_id FROM users WHERE user_id = ?");
    $stmt_addr->bind_param("i", $user_id);
    $stmt_addr->execute();
    $address_id = $stmt_addr->get_result()->fetch_assoc()['address_id'] ?? null;
    $stmt_addr->close();

    if ($shipping_type === 'delivery' && !$address_id) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลที่อยู่จัดส่ง']);
        exit;
    }

    $final_address_id = ($shipping_type === 'delivery') ? $address_id : null;

    // จัดการอัปโหลดสลิป (ถ้าเลือกโอนเงิน)
    $slip_img_name = null;
    $sale_status = 1; // 1 = รอดำเนินการ/รอตรวจสอบสลิป

    if ($payment_type === 'Transfer' && isset($_FILES['slip_img']) && $_FILES['slip_img']['error'] === 0) {
        $upload_dir = '../../uploads/slips/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['slip_img']['name'], PATHINFO_EXTENSION);
        $slip_img_name = 'slip_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        
        if (!move_uploaded_file($_FILES['slip_img']['tmp_name'], $upload_dir . $slip_img_name)) {
            echo json_encode(['status' => 'error', 'message' => 'อัปโหลดสลิปไม่สำเร็จ']);
            exit;
        }
    }

    // ================= เริ่ม TRANSACTION =================
    $conn->begin_transaction();

    try {
        // Step 1: สร้างบิลใหม่ใน bill_sales
        $sql_bill = "INSERT INTO bill_sales (user_id, address_id, total_price, shipping_type, payment_type, sale_status, slip_img, remark) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_bill = $conn->prepare($sql_bill);
        $stmt_bill->bind_param("iidssiss", $user_id, $final_address_id, $total_price, $shipping_type, $payment_type, $sale_status, $slip_img_name, $remark);
        $stmt_bill->execute();
        $sale_id = $conn->insert_id; // ได้รหัสบิลมาแล้ว

        // Step 2: ดึงของจากตะกร้า
        $sql_cart = "SELECT c.prod_id, c.quantity, p.price 
                     FROM cart_items c 
                     JOIN products p ON c.prod_id = p.prod_id 
                     WHERE c.user_id = ?";
        $stmt_cart = $conn->prepare($sql_cart);
        $stmt_cart->bind_param("i", $user_id);
        $stmt_cart->execute();
        $cart_items = $stmt_cart->get_result();

        // Step 3: วนลูปย้ายข้อมูลลง details_sales และตัดสต็อก
        $sql_detail = "INSERT INTO details_sales (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_detail);

        $sql_stock = "UPDATE stocks SET total_qty = total_qty - ? WHERE prod_id = ? AND total_qty >= ?";
        $stmt_stock = $conn->prepare($sql_stock);

        while ($item = $cart_items->fetch_assoc()) {
            $subtotal = $item['quantity'] * $item['price'];
            
            // บันทึกรายละเอียดการขาย
            $stmt_detail->bind_param("iiidd", $sale_id, $item['prod_id'], $item['quantity'], $item['price'], $subtotal);
            $stmt_detail->execute();

            // ตัดสต็อก
            $stmt_stock->bind_param("iii", $item['quantity'], $item['prod_id'], $item['quantity']);
            $stmt_stock->execute();
            
            // ถ้าสต็อกไม่พอให้แจ้ง Error แล้ว Rollback ทันที
            if ($stmt_stock->affected_rows === 0) {
                throw new Exception("สินค้าบางรายการสต็อกไม่เพียงพอ");
            }
            
            // บันทึกประวัติการเคลือนไหวสต็อก (Stock Movements)
            $sql_mov = "INSERT INTO stock_movements (prod_id, movement_type, quantity, ref_id, remark) VALUES (?, 'OUT', ?, ?, 'ตัดสต็อกจากการสั่งซื้อหน้าร้านเว็บ')";
            $stmt_mov = $conn->prepare($sql_mov);
            $ref_id = 'ORD-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
            $stmt_mov->bind_param("iis", $item['prod_id'], $item['quantity'], $ref_id);
            $stmt_mov->execute();
            $stmt_mov->close();
        }

        // Step 4: ล้างตะกร้าสินค้า
        $sql_clear_cart = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt_clear = $conn->prepare($sql_clear_cart);
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();

        // ยืนยันการทำงานทั้งหมด
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'order_id' => 'ORD-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT)]);

    } catch (Exception $e) {
        // ถ้าระหว่างทางมีอะไรพัง ให้ย้อนกลับ (Rollback) ข้อมูลทั้งหมด
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    // ================= จบ TRANSACTION =================
    
    $conn->close();
}
?>