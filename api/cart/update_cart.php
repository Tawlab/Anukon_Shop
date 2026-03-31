<?php
session_start();
require_once '../../config/db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = $_POST['cart_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    // ดึงจำนวนปัจจุบันและสต็อกมาก่อน
    $check_sql = "SELECT c.quantity, s.total_qty 
                  FROM cart_items c 
                  JOIN products p ON c.prod_id = p.prod_id
                  JOIN stocks s ON p.prod_id = s.prod_id
                  WHERE c.cart_id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $current_qty = $row['quantity'];
        $available_qty = $row['total_qty'];
        
        if ($action === 'plus') {
            if ($current_qty >= $available_qty) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเพิ่มจำนวนได้ สต็อกคงเหลือเพียง ' . $available_qty . ' ชิ้น']);
                exit;
            }
            $new_qty = $current_qty + 1;
        } else if ($action === 'minus') {
            $new_qty = $current_qty > 1 ? $current_qty - 1 : 1; // ไม่ให้ต่ำกว่า 1
        } else {
            echo json_encode(['status' => 'error', 'message' => 'คำสั่งไม่ถูกต้อง']);
            exit;
        }

        // อัปเดตจำนวนใหม่ลงฐานข้อมูล
        $update_sql = "UPDATE cart_items SET quantity = ? WHERE cart_id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ii", $new_qty, $cart_id);
        
        if ($stmt_update->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'อัปเดตไม่สำเร็จ']);
        }
    }
}
?>