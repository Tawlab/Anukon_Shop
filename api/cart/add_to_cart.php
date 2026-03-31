<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

// เช็คว่าล็อกอินหรือยัง
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำรายการ']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $prod_id = $_POST['prod_id'] ?? 0;
    $qty = 1; // ค่าเริ่มต้นคือเพิ่มทีละ 1 ชิ้น

    if (empty($prod_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสสินค้า']);
        exit;
    }

    // --- เช็คสต็อกคงเหลือ ---
    $stock_sql = "SELECT total_qty, prod_name FROM stocks JOIN products ON stocks.prod_id = products.prod_id WHERE stocks.prod_id = ?";
    $stmt_stock = $conn->prepare($stock_sql);
    $stmt_stock->bind_param("i", $prod_id);
    $stmt_stock->execute();
    $stock_res = $stmt_stock->get_result();
    $stock_data = $stock_res->fetch_assoc();
    $stmt_stock->close();

    if (!$stock_data) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลสินค้านี้ในคลัง']);
        exit;
    }

    $available_qty = (int)$stock_data['total_qty'];
    if ($available_qty <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ขออภัย สินค้านี้หมดแล้ว']);
        exit;
    }

    // 1. เช็คว่ามีสินค้านี้ในตะกร้าของ user นี้หรือยัง
    $check_sql = "SELECT cart_id, quantity FROM cart_items WHERE user_id = ? AND prod_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $user_id, $prod_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // มีอยู่แล้ว -> อัปเดตจำนวนเพิ่มขึ้น 1
        $row = $result->fetch_assoc();
        
        if ($row['quantity'] >= $available_qty) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเพิ่มสินค้าได้ สต็อกคงเหลือเพียง ' . $available_qty . ' ชิ้น']);
            exit;
        }

        $new_qty = $row['quantity'] + 1;
        $cart_id = $row['cart_id'];

        $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ii", $new_qty, $cart_id);
        
        if ($stmt_update->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตจำนวนสินค้าในตะกร้าแล้ว']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
        }
        $stmt_update->close();

    } else {
        // ยังไม่มี -> เพิ่มรายการใหม่ลงตะกร้า
        $insert_sql = "INSERT INTO cart_items (user_id, prod_id, quantity) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("iii", $user_id, $prod_id, $qty);

        if ($stmt_insert->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อย']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
        }
        $stmt_insert->close();
    }

    $stmt_check->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>